<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\FiscalYear;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherPostingService
{
    /**
     * Create and post or save as draft a Cash Voucher.
     *
     * @param array $data
     * @param int $userId
     * @return Voucher
     * @throws ValidationException
     */
    public function createCashVoucher(array $data, int $userId): Voucher
    {
        $currentFy = FiscalYear::current();
        if (! $currentFy) {
            throw ValidationException::withMessages(['date' => 'কোনো সক্রিয় অর্থবছর পাওয়া যায়নি।']);
        }

        // Determine cash or bank account head
        $cashHead = null;
        if (str_starts_with($data['cash_type'], 'cash_')) {
            $cashHead = ChartOfAccount::where('account_code', '101010')->first();
        } else {
            $bankAcc = BankAccount::with('chartOfAccount')->find($data['bank_account_id'] ?? null);
            $cashHead = $bankAcc?->chartOfAccount;
        }

        if (! $cashHead) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'সংশ্লিষ্ট ব্যাংক বা ক্যাশ অ্যাকাউন্ট হেড পাওয়া যায়নি।',
            ]);
        }

        return DB::transaction(function () use ($data, $currentFy, $cashHead, $userId) {
            $prefix = 'CV-' . date('Ym', strtotime($data['date'])) . '-';
            $lastCount = Voucher::where('voucher_number', 'like', $prefix . '%')->count() + 1;
            $voucherNumber = $prefix . str_pad((string)$lastCount, 4, '0', STR_PAD_LEFT);

            $status = ($data['submit_action'] ?? '') === 'submit_for_approval' ? 'submitted' : 'draft';
            $amount = (float) $data['amount'];

            $voucher = Voucher::create([
                'voucher_number'   => $voucherNumber,
                'voucher_type'     => 'cash_voucher',
                'cash_type'        => $data['cash_type'],
                'date'             => $data['date'],
                'fiscal_year_id'   => $currentFy->id,
                'cost_center_id'   => $data['cost_center_id'],
                'bank_account_id'  => $data['bank_account_id'] ?? null,
                'cheque_number'    => $data['cheque_number'] ?? null,
                'cheque_date'      => $data['cheque_date'] ?? null,
                'payee_payer_name' => $data['payee_payer_name'] ?? null,
                'total_debit'      => $amount,
                'total_credit'     => $amount,
                'status'           => $status,
                'narration'        => $data['narration'],
                'prepared_by_id'   => $userId,
            ]);

            // Add Entries based on Payment vs Receipt
            if (in_array($data['cash_type'], ['cash_payment', 'bank_payment'], true)) {
                // Debit: Contra Account (Expense / Payable / Asset)
                $voucher->entries()->create([
                    'chart_of_account_id' => $data['contra_account_id'],
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => $amount,
                    'credit_amount'       => 0.00,
                    'line_narration'      => $data['narration'],
                ]);
                // Credit: Cash or Bank
                $voucher->entries()->create([
                    'chart_of_account_id' => $cashHead->id,
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => 0.00,
                    'credit_amount'       => $amount,
                    'line_narration'      => 'ক্যাশ / ব্যাংক প্রদান',
                ]);
            } else {
                // Receipt
                // Debit: Cash or Bank
                $voucher->entries()->create([
                    'chart_of_account_id' => $cashHead->id,
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => $amount,
                    'credit_amount'       => 0.00,
                    'line_narration'      => 'ক্যাশ / ব্যাংক প্রাপ্তি',
                ]);
                // Credit: Contra Account (Revenue / Receivable)
                $voucher->entries()->create([
                    'chart_of_account_id' => $data['contra_account_id'],
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => 0.00,
                    'credit_amount'       => $amount,
                    'line_narration'      => $data['narration'],
                ]);
            }

            AuditLog::log('CREATE_CASH_VOUCHER', "ক্যাশ ভাউচার {$voucherNumber} তৈরি করা হয়েছে। স্ট্যাটাস: {$status}");

            return $voucher;
        });
    }

    /**
     * Create and post or save as draft a Journal Voucher.
     *
     * @param array $data
     * @param int $userId
     * @return Voucher
     * @throws ValidationException
     */
    public function createJournalVoucher(array $data, int $userId): Voucher
    {
        $currentFy = FiscalYear::current();
        if (! $currentFy) {
            throw ValidationException::withMessages(['date' => 'কোনো সক্রিয় অর্থবছর পাওয়া যায়নি।']);
        }

        // Validate debit & credit balance
        $validation = $this->validateBalance($data['entries'], (int) $data['cost_center_id']);
        $totalDebit   = $validation['totalDebit'];
        $totalCredit  = $validation['totalCredit'];
        $cleanEntries = $validation['cleanEntries'];

        return DB::transaction(function () use ($data, $currentFy, $totalDebit, $totalCredit, $cleanEntries, $userId) {
            $prefix = 'JV-' . date('Ym', strtotime($data['date'])) . '-';
            $lastCount = Voucher::where('voucher_number', 'like', $prefix . '%')->count() + 1;
            $voucherNumber = $prefix . str_pad((string)$lastCount, 4, '0', STR_PAD_LEFT);

            $status = ($data['submit_action'] ?? '') === 'submit_for_approval' ? 'submitted' : 'draft';

            $voucher = Voucher::create([
                'voucher_number' => $voucherNumber,
                'voucher_type'   => 'journal_voucher',
                'date'           => $data['date'],
                'fiscal_year_id' => $currentFy->id,
                'cost_center_id' => $data['cost_center_id'],
                'total_debit'    => $totalDebit,
                'total_credit'   => $totalCredit,
                'status'         => $status,
                'narration'      => $data['narration'],
                'prepared_by_id' => $userId,
            ]);

            foreach ($cleanEntries as $e) {
                $voucher->entries()->create($e);
            }

            AuditLog::log('CREATE_JOURNAL_VOUCHER', "জার্নাল ভাউচার {$voucherNumber} তৈরি করা হয়েছে। স্ট্যাটাস: {$status}");

            return $voucher;
        });
    }

    /**
     * Validate that entries balance (Debit == Credit) and contain valid positive rows.
     *
     * @param array $entries
     * @param int $costCenterId
     * @return array{totalDebit: float, totalCredit: float, cleanEntries: array}
     * @throws ValidationException
     */
    public function validateBalance(array $entries, int $costCenterId): array
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $cleanEntries = [];

        foreach ($entries as $entry) {
            $debit = (float) ($entry['debit_amount'] ?? 0);
            $credit = (float) ($entry['credit_amount'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    'entries' => 'একটি সারিতে একই সাথে ডেবিট এবং ক্রেডিট উভয় অ্যামাউন্ট থাকতে পারে না।',
                ]);
            }

            if ($debit == 0 && $credit == 0) {
                continue; // skip empty rows
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $cleanEntries[] = [
                'chart_of_account_id' => $entry['chart_of_account_id'],
                'cost_center_id'      => $costCenterId,
                'debit_amount'        => $debit,
                'credit_amount'       => $credit,
                'line_narration'      => $entry['line_narration'] ?? null,
            ];
        }

        if (count($cleanEntries) < 2) {
            throw ValidationException::withMessages([
                'entries' => 'জার্নাল ভাউচারে কমপক্ষে দুইটি সচল এন্ট্রি (ডেবিট ও ক্রেডিট) থাকতে হবে।',
            ]);
        }

        // Check Debit == Credit (with 0.01 precision)
        if (abs($totalDebit - $totalCredit) > 0.01 || $totalDebit <= 0) {
            throw ValidationException::withMessages([
                'entries' => "ডেবিট (৳ " . number_format($totalDebit, 2) . ") এবং ক্রেডিট (৳ " . number_format($totalCredit, 2) . ") এর যোগফল অবশ্যই সমান হতে হবে।",
            ]);
        }

        return [
            'totalDebit'   => $totalDebit,
            'totalCredit'  => $totalCredit,
            'cleanEntries' => $cleanEntries,
        ];
    }
}
