<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\CostCenter;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankReconciliationController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Accessible Cost Centers
        if ($user->isCentralUser()) {
            $costCenters = CostCenter::active()->with('bankAccounts')->orderBy('code')->get();
            $allBankAccounts = BankAccount::active()->with(['chartOfAccount', 'costCenter'])->get();
        } else {
            $userRaoId = $user->regional_accounts_office_id;
            $costCenters = CostCenter::active()
                ->where(function ($q) use ($user, $userRaoId) {
                    if ($userRaoId) {
                        $q->where('regional_accounts_office_id', $userRaoId);
                    }
                    $q->orWhereIn('id', $user->costCenters->pluck('id'));
                })
                ->with('bankAccounts')
                ->orderBy('code')
                ->get();

            $allBankAccounts = BankAccount::active()
                ->where(function ($q) use ($user, $userRaoId) {
                    if ($userRaoId) {
                        $q->where('regional_accounts_office_id', $userRaoId);
                    }
                    $q->orWhereIn('id', $user->bankAccounts->pluck('id'));
                })
                ->with(['chartOfAccount', 'costCenter'])
                ->get();
        }

        // Selected Cost Center
        $selectedCcId = $request->input('cost_center_id');
        if (! $selectedCcId && $costCenters->isNotEmpty()) {
            // Prefer a cost center that already has bank accounts
            $firstWithBank = $costCenters->first(fn($cc) => $cc->bankAccounts->isNotEmpty());
            $selectedCcId = $firstWithBank ? $firstWithBank->id : $costCenters->first()->id;
        }
        $selectedCostCenter = $costCenters->firstWhere('id', $selectedCcId);

        // Bank Accounts under selected Cost Center (A cost center can have multiple bank accounts)
        $costCenterBankAccounts = $allBankAccounts->where('cost_center_id', $selectedCcId);
        if ($costCenterBankAccounts->isEmpty() && $allBankAccounts->isNotEmpty()) {
            // Fallback so user can still reconcile any available account
            $costCenterBankAccounts = $allBankAccounts;
        }

        // Selected Bank Account
        $selectedBankId = $request->input('bank_account_id');
        if (! $selectedBankId || ! $costCenterBankAccounts->contains('id', $selectedBankId)) {
            $selectedBankId = $costCenterBankAccounts->first()?->id;
        }
        $selectedBank = $allBankAccounts->firstWhere('id', $selectedBankId);

        // Date Range
        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate   = $request->input('to_date', date('Y-m-t'));

        // Calculate Balance as per Cash Book up to $toDate
        $cashBookBalance = 0.0;
        if ($selectedBank) {
            $coaId       = $selectedBank->chart_of_account_id;
            $baseOpening = (float) $selectedBank->opening_balance;

            $transQuery = VoucherEntry::whereHas('voucher', function ($q) use ($toDate) {
                $q->where('status', 'posted')->where('date', '<=', $toDate);
            });

            if ($coaId) {
                $transQuery->where('chart_of_account_id', $coaId);
            }
            if ($selectedCostCenter && $selectedBank->cost_center_id == $selectedCostCenter->id) {
                $transQuery->where(function ($q) use ($selectedCostCenter) {
                    $q->where('cost_center_id', $selectedCostCenter->id)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $selectedCostCenter->id));
                });
            }

            $dr = (float) (clone $transQuery)->sum('debit_amount');
            $cr = (float) (clone $transQuery)->sum('credit_amount');

            $cashBookBalance = $baseOpening + $dr - $cr;
        }

        // Check if an existing reconciliation was saved for this bank account and date
        $existing = null;
        if ($selectedBank) {
            $existing = BankReconciliation::with('items')
                ->where('bank_account_id', $selectedBank->id)
                ->where(function ($q) use ($toDate, $fromDate) {
                    $q->where('to_date', $toDate)
                      ->orWhere('statement_date', $toDate);
                })
                ->latest()
                ->first();
        }

        // Pre-group existing items by schedule or provide empty structure
        $scheduleItems = [
            'A1' => [],
            'A2' => [],
            'B1' => [],
            'B2' => [],
        ];

        if ($existing) {
            foreach ($existing->items as $item) {
                $sch = $item->schedule ?: 'A1';
                $scheduleItems[$sch][] = [
                    'reference_no' => $item->reference_no,
                    'item_date'    => $item->item_date?->format('Y-m-d'),
                    'description'  => $item->description,
                    'amount'       => (float) $item->amount,
                ];
            }
        }

        // Calculate schedule totals
        $totalA1 = array_sum(array_column($scheduleItems['A1'], 'amount'));
        $totalA2 = array_sum(array_column($scheduleItems['A2'], 'amount'));
        $totalAdd = $totalA1 + $totalA2;
        $subtotal = $cashBookBalance + $totalAdd;

        $totalB1 = array_sum(array_column($scheduleItems['B1'], 'amount'));
        $totalB2 = array_sum(array_column($scheduleItems['B2'], 'amount'));
        $totalLess = $totalB1 + $totalB2;

        $reconciledBalance = $subtotal - $totalLess;
        $bankStatementBalance = $existing ? (float) $existing->bank_statement_balance : $reconciledBalance;

        // History of saved reconciliations
        $reconciliations = BankReconciliation::with(['bankAccount', 'costCenter', 'preparedBy'])
            ->latest('statement_date')
            ->paginate(10);

        return view('reconciliation.index', compact(
            'costCenters',
            'selectedCostCenter',
            'costCenterBankAccounts',
            'selectedBank',
            'fromDate',
            'toDate',
            'cashBookBalance',
            'scheduleItems',
            'totalA1',
            'totalA2',
            'totalAdd',
            'subtotal',
            'totalB1',
            'totalB2',
            'totalLess',
            'reconciledBalance',
            'bankStatementBalance',
            'existing',
            'reconciliations'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cost_center_id'         => ['nullable', 'exists:cost_centers,id'],
            'bank_account_id'        => ['required', 'exists:bank_accounts,id'],
            'from_date'              => ['required', 'date'],
            'to_date'                => ['required', 'date', 'after_or_equal:from_date'],
            'cash_book_balance'      => ['required', 'numeric'],
            'bank_statement_balance' => ['required', 'numeric'],
            'notes'                  => ['nullable', 'string'],
            'items'                  => ['nullable', 'array'],
            'items.*.schedule'       => ['required', 'in:A1,A2,B1,B2'],
            'items.*.reference_no'   => ['nullable', 'string', 'max:100'],
            'items.*.item_date'      => ['nullable', 'date'],
            'items.*.description'    => ['nullable', 'string', 'max:255'],
            'items.*.amount'         => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data) {
            $itemsData = $data['items'] ?? [];

            $totalA1 = 0; $totalA2 = 0; $totalB1 = 0; $totalB2 = 0;
            foreach ($itemsData as $item) {
                $amt = (float) $item['amount'];
                if ($amt <= 0) continue;

                if ($item['schedule'] === 'A1') $totalA1 += $amt;
                elseif ($item['schedule'] === 'A2') $totalA2 += $amt;
                elseif ($item['schedule'] === 'B1') $totalB1 += $amt;
                elseif ($item['schedule'] === 'B2') $totalB2 += $amt;
            }

            $totalAdd  = $totalA1 + $totalA2;
            $totalLess = $totalB1 + $totalB2;
            $reconciledBalance = (float) $data['cash_book_balance'] + $totalAdd - $totalLess;
            $diff = round((float) $data['bank_statement_balance'] - $reconciledBalance, 2);
            $status = abs($diff) < 0.01 ? 'reconciled' : 'draft';

            // Find existing or create
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
                    'cash_book_balance'      => $data['cash_book_balance'],
                    'reconciled_balance'     => $reconciledBalance,
                    'difference'             => $diff,
                    'status'                 => $status,
                    'notes'                  => $data['notes'] ?? null,
                    'prepared_by_id'         => Auth::id(),
                    'reconciled_at'          => $status === 'reconciled' ? now() : null,
                ]
            );

            // Re-sync items
            $rec->items()->delete();
            foreach ($itemsData as $item) {
                $amt = (float) $item['amount'];
                if ($amt <= 0) continue;

                $type = match ($item['schedule']) {
                    'A1' => 'outstanding_cheque',
                    'A2' => 'interest_credited',
                    'B1' => 'deposit_in_transit',
                    'B2' => 'bank_charge',
                    default => 'other_adjustment',
                };
                $effect = in_array($item['schedule'], ['A1', 'A2']) ? 'add' : 'less';

                $rec->items()->create([
                    'schedule'     => $item['schedule'],
                    'type'         => $type,
                    'item_date'    => $item['item_date'] ?: $data['to_date'],
                    'reference_no' => $item['reference_no'] ?? null,
                    'description'  => $item['description'] ?? ($type),
                    'amount'       => $amt,
                    'effect'       => $effect,
                ]);
            }

            AuditLog::log('SAVE_BANK_RECONCILIATION', "Bank Reconciliation Statement saved. Account ID: {$data['bank_account_id']}, Status: {$status}");
        });

        return redirect()->route('reconciliation.index', [
            'cost_center_id'  => $data['cost_center_id'] ?? null,
            'bank_account_id' => $data['bank_account_id'],
            'from_date'       => $data['from_date'],
            'to_date'         => $data['to_date'],
        ])->with('success', 'Bank Reconciliation Statement has been successfully saved!');
    }

    public function show(BankReconciliation $reconciliation): View
    {
        $reconciliation->load(['bankAccount.costCenter', 'bankAccount.chartOfAccount', 'costCenter', 'items', 'preparedBy']);
        return view('reconciliation.show', compact('reconciliation'));
    }

    public function create(Request $request): View
    {
        return $this->index($request);
    }

    public function export(Request $request): StreamedResponse
    {
        $costCenterId  = $request->input('cost_center_id');
        $bankAccountId = $request->input('bank_account_id');
        $fromDate      = $request->input('from_date', date('Y-m-01'));
        $toDate        = $request->input('to_date', date('Y-m-t'));

        $bankAccount = BankAccount::with(['chartOfAccount', 'costCenter'])->find($bankAccountId)
            ?? BankAccount::first();

        $costCenter = $costCenterId ? CostCenter::find($costCenterId) : $bankAccount?->costCenter;

        // Cash book balance
        $cashBookBalance = 0.0;
        if ($bankAccount) {
            $coaId = $bankAccount->chart_of_account_id;
            $baseOpening = (float) $bankAccount->opening_balance;

            $transQuery = VoucherEntry::whereHas('voucher', function ($q) use ($toDate) {
                $q->where('status', 'posted')->where('date', '<=', $toDate);
            });
            if ($coaId) {
                $transQuery->where('chart_of_account_id', $coaId);
            }
            if ($costCenter) {
                $transQuery->where(function ($q) use ($costCenter) {
                    $q->where('cost_center_id', $costCenter->id)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenter->id));
                });
            }

            $dr = (float) (clone $transQuery)->sum('debit_amount');
            $cr = (float) (clone $transQuery)->sum('credit_amount');
            $cashBookBalance = $baseOpening + $dr - $cr;
        }

        // Existing reconciliation items
        $rec = BankReconciliation::with('items')
            ->where('bank_account_id', $bankAccount?->id)
            ->where(function ($q) use ($toDate) {
                $q->where('to_date', $toDate)->orWhere('statement_date', $toDate);
            })
            ->latest()
            ->first();

        $items = $rec ? $rec->items : collect();
        $itemsA1 = $items->where('schedule', 'A1');
        $itemsA2 = $items->where('schedule', 'A2');
        $itemsB1 = $items->where('schedule', 'B1');
        $itemsB2 = $items->where('schedule', 'B2');

        $totalA1 = (float) $itemsA1->sum('amount');
        $totalA2 = (float) $itemsA2->sum('amount');
        $totalAdd = $totalA1 + $totalA2;
        $subtotal = $cashBookBalance + $totalAdd;

        $totalB1 = (float) $itemsB1->sum('amount');
        $totalB2 = (float) $itemsB2->sum('amount');
        $totalLess = $totalB1 + $totalB2;

        $reconciledBalance = $subtotal - $totalLess;

        $monthName = Carbon::parse($toDate)->format("F 'y");
        $unitOfficeName = $costCenter ? ($costCenter->name_bn ?: $costCenter->name) : 'BPDB Office';
        $bankName = $bankAccount ? $bankAccount->bank_name : 'Bank';
        $branchName = $bankAccount ? $bankAccount->branch_name : 'Branch';
        $natureOfAccount = $bankAccount ? $bankAccount->account_title : 'Operational Account';
        $accountsCode = $costCenter ? $costCenter->code : ($bankAccount?->chartOfAccount?->account_code ?? '135');
        $accountNo = $bankAccount ? "{$bankAccount->account_type} - {$bankAccount->account_number}" : 'STD - 0100043163986';

        $fileName = 'Bank_Reconciliation_' . ($costCenter?->code ?? 'BPDB') . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use (
            $unitOfficeName,
            $monthName,
            $bankName,
            $branchName,
            $natureOfAccount,
            $accountsCode,
            $accountNo,
            $cashBookBalance,
            $totalA1,
            $totalA2,
            $totalAdd,
            $subtotal,
            $totalB1,
            $totalB2,
            $totalLess,
            $reconciledBalance,
            $itemsA1,
            $itemsA2,
            $itemsB1,
            $itemsB2
        ) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['BANGLADESH POWER DEVELOPMENT BOARD', '', '']);
            fputcsv($handle, ["Unit Office :  {$unitOfficeName}", '', '']);
            fputcsv($handle, ['Bank Reconciliation Statement', '', '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ["Name of Month:-{$monthName}", "Bank's Name : {$bankName}", '']);
            fputcsv($handle, ["Nature of Account :- {$natureOfAccount}", $branchName, '']);
            fputcsv($handle, ["Accounts Code :-{$accountsCode}", "Account No.-{$accountNo}", '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Balance as per Cash Book', '', number_format($cashBookBalance, 2, '.', '')]);
            fputcsv($handle, ['A)  Add :-', '', '']);
            fputcsv($handle, ['!) Cheque No. has been issued but not Presented to the Bank :-', number_format($totalA1, 2, '.', ''), '']);
            fputcsv($handle, ['!!) Deposited / Credited by Bank but not entered into the Cash Book :-', number_format($totalA2, 2, '.', ''), '']);
            fputcsv($handle, ['                              Total-Add', '', number_format($totalAdd, 2, '.', '')]);
            fputcsv($handle, ['', '', number_format($subtotal, 2, '.', '')]);
            fputcsv($handle, ['B) Less :-', '', '']);
            fputcsv($handle, ['!) Cheque No. /Draft/Pay order deposited but not Credited by Bank :-', number_format($totalB1, 2, '.', ''), '']);
            fputcsv($handle, ['!!) Debited / Paid by the Bank but not entered into the Cash Book :-', number_format($totalB2, 2, '.', ''), '']);
            fputcsv($handle, ['Total-Less', '', number_format($totalLess, 2, '.', '')]);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Reconciliation Balance as per Bank Statement', '', number_format($reconciledBalance, 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            // Schedule A-1
            fputcsv($handle, ['A--( ! ) Cheque No. has been issued but not Presented to the Bank :-', '', '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Cheque No./ Memo/Draft/Pay order No.', 'Date', 'Amount in Taka']);
            if ($itemsA1->isEmpty()) {
                fputcsv($handle, ['Cheque No.--', '', '0.00']);
            } else {
                foreach ($itemsA1 as $it) {
                    fputcsv($handle, [$it->reference_no ?: $it->description, $it->item_date?->format('d/m/Y'), number_format($it->amount, 2, '.', '')]);
                }
            }
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Total--A--(!)', '', number_format($totalA1, 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            // Schedule A-2
            fputcsv($handle, ['A--(!!) Deposited / Credited by Bank but not entered into the Cash Book :-', '', '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Cheque No./ Memo/Draft/Pay order No.', 'Date', 'Amount in Taka']);
            if ($itemsA2->isEmpty()) {
                fputcsv($handle, ['', '', '0.00']);
            } else {
                foreach ($itemsA2 as $it) {
                    fputcsv($handle, [$it->reference_no ?: $it->description, $it->item_date?->format('d/m/Y'), number_format($it->amount, 2, '.', '')]);
                }
            }
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['A--(!!) Total :-', '', number_format($totalA2, 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            // Schedule B-1
            fputcsv($handle, ['B--(!) Cheque No. /Draft/Pay order deposited but not Credited by Bank :-', '', '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Cheque No./ Memo/Draft/Pay order No.', 'Date', 'Amount in Taka']);
            if ($itemsB1->isEmpty()) {
                fputcsv($handle, ['', '', '0.00']);
            } else {
                foreach ($itemsB1 as $it) {
                    fputcsv($handle, [$it->reference_no ?: $it->description, $it->item_date?->format('d/m/Y'), number_format($it->amount, 2, '.', '')]);
                }
            }
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Total  (B--!)', '', number_format($totalB1, 2, '.', '')]);
            fputcsv($handle, ['', '', '']);

            // Schedule B-2
            fputcsv($handle, ['B--(!!) Debited / Paid by the Bank but not entered into the Cash Book :-', '', '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Cheque No./ Memo/Draft/Pay order No.', 'Date', 'Amount in Taka']);
            if ($itemsB2->isEmpty()) {
                fputcsv($handle, ['As per bank statement', '', '0.00']);
            } else {
                foreach ($itemsB2 as $it) {
                    fputcsv($handle, [$it->reference_no ?: $it->description, $it->item_date?->format('d/m/Y'), number_format($it->amount, 2, '.', '')]);
                }
            }
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Total  (B--!!)', '', number_format($totalB2, 2, '.', '')]);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['', '', '']);
            fputcsv($handle, ['Prepared by', 'Assistant Director (Accounts)', '']);
            fputcsv($handle, [$unitOfficeName, $unitOfficeName, '']);

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
