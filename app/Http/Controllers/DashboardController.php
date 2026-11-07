<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $currentFy = FiscalYear::current();
        $user = Auth::user();

        // Accessible Cost Centers
        $costCenterQuery = CostCenter::active();
        if (! $user->isSystemAdmin() && ! $user->isDirectorAccounts()) {
            $costCenterQuery->whereIn('id', $user->costCenters->pluck('id'));
        }
        $accessibleCostCenters = $costCenterQuery->get();

        // Bank Accounts total balance
        $bankAccountQuery = BankAccount::active();
        if (! $user->isSystemAdmin() && ! $user->isDirectorAccounts()) {
            $bankAccountQuery->whereIn('id', $user->bankAccounts->pluck('id'));
        }
        $bankAccounts = $bankAccountQuery->get();
        $totalBankBalance = $bankAccounts->sum('opening_balance');

        // Cash in Hand balance
        $cashInHandCoa = ChartOfAccount::where('account_code', '101010')->first();
        $cashInHandBalance = $cashInHandCoa ? (float) $cashInHandCoa->opening_balance : 0.0;

        // Current Fiscal Year Revenue & Expenses from posted vouchers
        $revenue = 0.0;
        $expenditure = 0.0;

        if ($currentFy) {
            $revenue = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy) {
                $q->where('fiscal_year_id', $currentFy->id)->where('status', 'posted');
            })
            ->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'revenue'))
            ->sum('credit_amount');

            $expenditure = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy) {
                $q->where('fiscal_year_id', $currentFy->id)->where('status', 'posted');
            })
            ->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'expense'))
            ->sum('debit_amount');
        }

        $netSurplus = $revenue - $expenditure;

        // Pending vouchers count
        $pendingVouchersCount = Voucher::where('status', 'submitted')->count();
        $totalVouchersCount   = Voucher::count();

        // Recent vouchers
        $recentVouchers = Voucher::with(['costCenter', 'preparedBy'])
            ->latest('date')
            ->latest('id')
            ->take(8)
            ->get();

        // Recent audit logs
        $recentLogs = AuditLog::with('user')
            ->latest('created_at')
            ->take(6)
            ->get();

        // Top Cost Center Budget vs Actual Spending
        $costCenterBudgets = [];
        if ($currentFy) {
            foreach ($accessibleCostCenters->take(6) as $cc) {
                $allocated = (float) Budget::where('fiscal_year_id', $currentFy->id)
                    ->where('cost_center_id', $cc->id)
                    ->sum('revised_budget');

                if ($allocated == 0) {
                    $allocated = (float) Budget::where('fiscal_year_id', $currentFy->id)
                        ->where('cost_center_id', $cc->id)
                        ->sum('original_budget');
                }

                $spent = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy, $cc) {
                    $q->where('fiscal_year_id', $currentFy->id)
                      ->where('status', 'posted')
                      ->where('cost_center_id', $cc->id);
                })
                ->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'expense'))
                ->sum('debit_amount');

                $costCenterBudgets[] = [
                    'code'      => $cc->code,
                    'name_bn'   => $cc->name_bn,
                    'allocated' => $allocated,
                    'spent'     => $spent,
                    'percent'   => $allocated > 0 ? min(100, round(($spent / $allocated) * 100, 1)) : 0,
                ];
            }
        }

        return view('dashboard.index', compact(
            'currentFy',
            'accessibleCostCenters',
            'bankAccounts',
            'totalBankBalance',
            'cashInHandBalance',
            'revenue',
            'expenditure',
            'netSurplus',
            'pendingVouchersCount',
            'totalVouchersCount',
            'recentVouchers',
            'recentLogs',
            'costCenterBudgets'
        ));
    }
}
