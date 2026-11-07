<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function balanceSheet(Request $request): View
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        // Helper to compute account closing balance as of date
        $computeBalance = function ($account) use ($asOfDate) {
            $isDebitNormal = $account->normal_balance === 'debit';
            $baseOpening   = (float) $account->opening_balance;

            $trans = VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
                $q->where('status', 'posted')->where('date', '<=', $asOfDate);
            })->where('chart_of_account_id', $account->id);

            $dr = (float) $trans->sum('debit_amount');
            $cr = (float) $trans->sum('credit_amount');

            return $isDebitNormal ? ($baseOpening + $dr - $cr) : ($baseOpening + $cr - $dr);
        };

        // 1. Assets
        $currentAssets = ChartOfAccount::leaf()->where('account_type', 'asset')
            ->where('account_code', 'like', '101%')->get()
            ->map(fn($a) => ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $computeBalance($a)])
            ->filter(fn($a) => $a['amount'] != 0);

        $fixedAssets = ChartOfAccount::leaf()->where('account_type', 'asset')
            ->where('account_code', 'like', '102%')->get()
            ->map(fn($a) => ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $computeBalance($a)])
            ->filter(fn($a) => $a['amount'] != 0);

        $totalCurrentAssets = $currentAssets->sum('amount');
        $totalFixedAssets   = $fixedAssets->sum('amount');
        $totalAssets        = $totalCurrentAssets + $totalFixedAssets;

        // 2. Liabilities
        $currentLiabilities = ChartOfAccount::leaf()->where('account_type', 'liability')
            ->where('account_code', 'like', '201%')->get()
            ->map(fn($a) => ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $computeBalance($a)])
            ->filter(fn($a) => $a['amount'] != 0);

        $longTermLiabilities = ChartOfAccount::leaf()->where('account_type', 'liability')
            ->where('account_code', 'like', '202%')->get()
            ->map(fn($a) => ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $computeBalance($a)])
            ->filter(fn($a) => $a['amount'] != 0);

        $totalCurrentLiabilities  = $currentLiabilities->sum('amount');
        $totalLongTermLiabilities = $longTermLiabilities->sum('amount');
        $totalLiabilities         = $totalCurrentLiabilities + $totalLongTermLiabilities;

        // 3. Equity & Net Surplus
        $equityAccounts = ChartOfAccount::leaf()->where('account_type', 'equity')->get()
            ->map(fn($a) => ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $computeBalance($a)])
            ->filter(fn($a) => $a['amount'] != 0);

        $baseEquity = $equityAccounts->sum('amount');

        // Current Year Surplus/Deficit (Revenue - Expenses)
        $revenueTotal = (float) VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
            $q->where('status', 'posted')->where('date', '<=', $asOfDate);
        })->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'revenue'))->sum('credit_amount');

        $expenseTotal = (float) VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
            $q->where('status', 'posted')->where('date', '<=', $asOfDate);
        })->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'expense'))->sum('debit_amount');

        $currentYearSurplus = $revenueTotal - $expenseTotal;
        $totalEquity = $baseEquity + $currentYearSurplus;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return view('reports.balance_sheet', compact(
            'asOfDate',
            'currentAssets',
            'fixedAssets',
            'totalCurrentAssets',
            'totalFixedAssets',
            'totalAssets',
            'currentLiabilities',
            'longTermLiabilities',
            'totalCurrentLiabilities',
            'totalLongTermLiabilities',
            'totalLiabilities',
            'equityAccounts',
            'baseEquity',
            'currentYearSurplus',
            'totalEquity',
            'totalLiabilitiesAndEquity',
            'isBalanced'
        ));
    }

    public function incomeStatement(Request $request): View
    {
        $fromDate = $request->input('from_date', date('Y-07-01'));
        $toDate   = $request->input('to_date', date('Y-m-d'));

        // Revenues
        $revenues = ChartOfAccount::leaf()->where('account_type', 'revenue')->get()->map(function ($a) use ($fromDate, $toDate) {
            $amt = (float) VoucherEntry::whereHas('voucher', function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'posted')->whereBetween('date', [$fromDate, $toDate]);
            })->where('chart_of_account_id', $a->id)->sum('credit_amount');

            return ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $amt];
        })->filter(fn($a) => $a['amount'] > 0);

        $totalRevenue = $revenues->sum('amount');

        // Expenses
        $expenses = ChartOfAccount::leaf()->where('account_type', 'expense')->get()->map(function ($a) use ($fromDate, $toDate) {
            $amt = (float) VoucherEntry::whereHas('voucher', function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'posted')->whereBetween('date', [$fromDate, $toDate]);
            })->where('chart_of_account_id', $a->id)->sum('debit_amount');

            return ['code' => $a->account_code, 'name_bn' => $a->account_name_bn, 'amount' => $amt];
        })->filter(fn($a) => $a['amount'] > 0);

        $totalExpense = $expenses->sum('amount');
        $netOperatingIncome = $totalRevenue - $totalExpense;

        return view('reports.income_statement', compact(
            'fromDate',
            'toDate',
            'revenues',
            'totalRevenue',
            'expenses',
            'totalExpense',
            'netOperatingIncome'
        ));
    }

    public function bankBook(Request $request): View
    {
        $user = Auth::user();
        $bankAccounts = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? BankAccount::active()->with('chartOfAccount')->get()
            : $user->bankAccounts()->with('chartOfAccount')->get();

        $selectedBankId = $request->input('bank_account_id', $bankAccounts->first()?->id);
        $selectedBank = $bankAccounts->firstWhere('id', $selectedBankId);

        $fromDate = $request->input('from_date', date('Y-07-01'));
        $toDate   = $request->input('to_date', date('Y-m-d'));

        $transactions = [];
        $openingBalance = 0.0;
        $totalDr = 0.0; $totalCr = 0.0;
        $closingBalance = 0.0;

        if ($selectedBank && $selectedBank->chartOfAccount) {
            $coaId = $selectedBank->chart_of_account_id;
            $baseOpening = (float) $selectedBank->opening_balance;

            $priorQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate) {
                $q->where('status', 'posted')->where('date', '<', $fromDate);
            })->where('chart_of_account_id', $coaId);

            $priorDr = (float) $priorQuery->sum('debit_amount');
            $priorCr = (float) $priorQuery->sum('credit_amount');

            $openingBalance = $baseOpening + $priorDr - $priorCr;
            $running = $openingBalance;

            $entries = VoucherEntry::with(['voucher.costCenter', 'costCenter'])
                ->whereHas('voucher', function ($q) use ($fromDate, $toDate) {
                    $q->where('status', 'posted')->whereBetween('date', [$fromDate, $toDate]);
                })
                ->where('chart_of_account_id', $coaId)
                ->join('vouchers', 'voucher_entries.voucher_id', '=', 'vouchers.id')
                ->orderBy('vouchers.date')
                ->orderBy('vouchers.id')
                ->select('voucher_entries.*')
                ->get();

            foreach ($entries as $e) {
                $dr = (float) $e->debit_amount;
                $cr = (float) $e->credit_amount;
                $totalDr += $dr;
                $totalCr += $cr;
                $running += ($dr - $cr);

                $transactions[] = [
                    'date'           => $e->voucher->date->format('d/m/Y'),
                    'voucher_number' => $e->voucher->voucher_number,
                    'cheque_number'  => $e->voucher->cheque_number,
                    'payee'          => $e->voucher->payee_payer_name,
                    'narration'      => $e->line_narration ?: $e->voucher->narration,
                    'debit'          => $dr,
                    'credit'         => $cr,
                    'balance'        => $running,
                ];
            }

            $closingBalance = $running;
        }

        return view('reports.bank_book', compact(
            'bankAccounts',
            'selectedBank',
            'fromDate',
            'toDate',
            'openingBalance',
            'transactions',
            'totalDr',
            'totalCr',
            'closingBalance'
        ));
    }

    public function costCenterExpenditure(Request $request): View
    {
        $currentFy = FiscalYear::current();
        $costCenters = CostCenter::active()->get();

        $data = [];
        $totalExpAll = 0.0;

        foreach ($costCenters as $cc) {
            $spent = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy, $cc) {
                $q->where('status', 'posted');
                if ($currentFy) $q->where('fiscal_year_id', $currentFy->id);
                $q->where('cost_center_id', $cc->id);
            })
            ->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'expense'))
            ->sum('debit_amount');

            $allocated = 0.0;
            if ($currentFy) {
                $allocated = (float) Budget::where('fiscal_year_id', $currentFy->id)
                    ->where('cost_center_id', $cc->id)
                    ->sum('revised_budget');
                if ($allocated == 0) {
                    $allocated = (float) Budget::where('fiscal_year_id', $currentFy->id)
                        ->where('cost_center_id', $cc->id)
                        ->sum('original_budget');
                }
            }

            $totalExpAll += $spent;

            $data[] = [
                'code'      => $cc->code,
                'name_bn'   => $cc->name_bn,
                'location'  => $cc->location,
                'allocated' => $allocated,
                'spent'     => $spent,
                'remaining' => $allocated - $spent,
                'percent'   => $allocated > 0 ? min(100, round(($spent / $allocated) * 100, 1)) : 0,
            ];
        }

        return view('reports.cost_center_expenditure', compact('currentFy', 'data', 'totalExpAll'));
    }

    public function budgetVariance(Request $request): View
    {
        $currentFy = FiscalYear::current();
        $costCenters = CostCenter::active()->get();
        $selectedCcId = $request->input('cost_center_id', $costCenters->first()?->id);
        $selectedCc = CostCenter::find($selectedCcId);

        $budgets = [];
        $totalBudget = 0.0;
        $totalActual = 0.0;

        if ($currentFy && $selectedCc) {
            $budgetRecords = Budget::with('chartOfAccount')
                ->where('fiscal_year_id', $currentFy->id)
                ->where('cost_center_id', $selectedCc->id)
                ->get();

            foreach ($budgetRecords as $b) {
                $budgetAmt = $b->active_budget_amount;
                $actual = $b->utilized_amount;
                $variance = $budgetAmt - $actual;
                $pct = $budgetAmt > 0 ? round(($actual / $budgetAmt) * 100, 2) : 0;

                $totalBudget += $budgetAmt;
                $totalActual += $actual;

                $budgets[] = [
                    'account_code' => $b->chartOfAccount->account_code,
                    'account_name' => $b->chartOfAccount->account_name_bn,
                    'budget'       => $budgetAmt,
                    'actual'       => $actual,
                    'variance'     => $variance,
                    'percentage'   => $pct,
                ];
            }
        }

        return view('reports.budget_variance', compact(
            'currentFy',
            'costCenters',
            'selectedCc',
            'budgets',
            'totalBudget',
            'totalActual'
        ));
    }
}
