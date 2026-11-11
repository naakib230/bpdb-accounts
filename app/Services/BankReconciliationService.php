<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\CostCenter;
use App\Models\VoucherEntry;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    /**
     * Calculate Balance as per Cash Book up to a given date.
     *
     * @param BankAccount $bankAccount
     * @param CostCenter|null $costCenter
     * @param string $toDate
     * @return float
     */
    public function calculateCashBookBalance(BankAccount $bankAccount, ?CostCenter $costCenter, string $toDate): float
    {
        $coaId       = $bankAccount->chart_of_account_id;
        $baseOpening = (float) $bankAccount->opening_balance;

        $transQuery = VoucherEntry::whereHas('voucher', function ($q) use ($toDate) {
            $q->where('status', 'posted')->where('date', '<=', $toDate);
        });

        if ($coaId) {
            $transQuery->where('chart_of_account_id', $coaId);
        }

        if ($costCenter && $bankAccount->cost_center_id == $costCenter->id) {
            $transQuery->where(function ($q) use ($costCenter) {
                $q->where('cost_center_id', $costCenter->id)
                  ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenter->id));
            });
        }

        $dr = (float) (clone $transQuery)->sum('debit_amount');
        $cr = (float) (clone $transQuery)->sum('credit_amount');

        return $baseOpening + $dr - $cr;
    }

    /**
     * Calculate Schedule Totals and Reconciled Balance.
     *
     * @param array $items
     * @param float $cashBookBalance
     * @return array
     */
    public function calculateScheduleTotals(array $items, float $cashBookBalance): array
    {
        $totalA1 = 0.0;
        $totalA2 = 0.0;
        $totalB1 = 0.0;
        $totalB2 = 0.0;

        foreach ($items as $item) {
            $amt = (float) ($item['amount'] ?? 0);
            if ($amt <= 0) {
                continue;
            }

            $sch = $item['schedule'] ?? 'A1';
            if ($sch === 'A1') {
                $totalA1 += $amt;
            } elseif ($sch === 'A2') {
                $totalA2 += $amt;
            } elseif ($sch === 'B1') {
                $totalB1 += $amt;
            } elseif ($sch === 'B2') {
                $totalB2 += $amt;
            }
        }

        $totalAdd          = $totalA1 + $totalA2;
        $subtotal          = $cashBookBalance + $totalAdd;
        $totalLess         = $totalB1 + $totalB2;
        $reconciledBalance = $subtotal - $totalLess;

        return compact(
            'totalA1',
            'totalA2',
            'totalAdd',
            'subtotal',
            'totalB1',
            'totalB2',
            'totalLess',
            'reconciledBalance'
        );
    }

    /**
     * Save or update a Bank Reconciliation and its schedule items.
     *
     * @param array $data
     * @param int $userId
     * @return BankReconciliation
     */
    public function saveReconciliation(array $data, int $userId): BankReconciliation
    {
        return DB::transaction(function () use ($data, $userId) {
            $itemsData = $data['items'] ?? [];
            $cashBookBalance = (float) $data['cash_book_balance'];

            $totals = $this->calculateScheduleTotals($itemsData, $cashBookBalance);
            $reconciledBalance = $totals['reconciledBalance'];
            $diff = round((float) $data['bank_statement_balance'] - $reconciledBalance, 2);
            $status = abs($diff) < 0.01 ? 'reconciled' : 'draft';

            $rec = BankReconciliation::updateOrCreate(
                [
                    'bank_account_id' => $data['bank_account_id'],
                    'statement_date'  => $data['to_date'],
                ],
                [
                    'cost_center_id'         => $data['cost_center_id'] ?? null,
                    'from_date'              => $data['from_date'],
                    'to_date'                => $data['to_date'],
                    'statement_date'         => $data['to_date'],
                    'bank_statement_balance' => $data['bank_statement_balance'],
                    'cash_book_balance'      => $cashBookBalance,
                    'reconciled_balance'     => $reconciledBalance,
                    'difference'             => $diff,
                    'status'                 => $status,
                    'notes'                  => $data['notes'] ?? null,
                    'prepared_by_id'         => $userId,
                    'reconciled_at'          => $status === 'reconciled' ? now() : null,
                ]
            );

            // Re-sync items
            $rec->items()->delete();
            foreach ($itemsData as $item) {
                $amt = (float) ($item['amount'] ?? 0);
                if ($amt <= 0) {
                    continue;
                }

                $sch = $item['schedule'] ?? 'A1';
                $type = match ($sch) {
                    'A1'    => 'outstanding_cheque',
                    'A2'    => 'interest_credited',
                    'B1'    => 'deposit_in_transit',
                    'B2'    => 'bank_charge',
                    default => 'other_adjustment',
                };
                $effect = in_array($sch, ['A1', 'A2'], true) ? 'add' : 'less';

                $rec->items()->create([
                    'schedule'     => $sch,
                    'type'         => $type,
                    'item_date'    => ! empty($item['item_date']) ? $item['item_date'] : $data['to_date'],
                    'reference_no' => $item['reference_no'] ?? null,
                    'description'  => ! empty($item['description']) ? $item['description'] : $type,
                    'amount'       => $amt,
                    'effect'       => $effect,
                ]);
            }

            AuditLog::log(
                'SAVE_BANK_RECONCILIATION',
                "Bank Reconciliation Statement saved. Account ID: {$data['bank_account_id']}, Status: {$status}"
            );

            return $rec;
        });
    }
}
