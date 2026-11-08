<?php

use App\Http\Controllers\AccountingPeriodController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\CostCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\RegionalAccountsOfficeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrialBalanceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\YearClosingController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/first-time', [AuthController::class, 'showFirstTime'])->name('first_time');
    Route::post('/first-time/verify', [AuthController::class, 'verifyFirstTime'])->name('first_time.verify');
    Route::post('/first-time/activate', [AuthController::class, 'activateAccount'])->name('first_time.activate');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/switch-role', [AuthController::class, 'switchRole'])->name('switch_role');
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Vouchers (Cash Voucher & Journal Voucher)
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::get('/vouchers/create/cash', [VoucherController::class, 'createCash'])->name('vouchers.create_cash');
    Route::post('/vouchers/cash', [VoucherController::class, 'storeCash'])->name('vouchers.store_cash');
    Route::get('/vouchers/create/journal', [VoucherController::class, 'createJournal'])->name('vouchers.create_journal');
    Route::post('/vouchers/journal', [VoucherController::class, 'storeJournal'])->name('vouchers.store_journal');
    Route::get('/vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
    Route::get('/vouchers/{voucher}/print', [VoucherController::class, 'print'])->name('vouchers.print');
    Route::post('/vouchers/{voucher}/approve', [VoucherController::class, 'approve'])->name('vouchers.approve');
    Route::post('/vouchers/{voucher}/reject', [VoucherController::class, 'reject'])->name('vouchers.reject');
    Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');

    // General Ledger
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');

    // Trial Balance
    Route::get('/trial-balance', [TrialBalanceController::class, 'index'])->name('trial_balance.index');
    Route::get('/trial-balance/export', [TrialBalanceController::class, 'export'])->name('trial_balance.export');

    // Financial Statements & Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance_sheet');
    Route::get('/reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income_statement');
    Route::get('/reports/bank-book', [ReportController::class, 'bankBook'])->name('reports.bank_book');
    Route::get('/reports/cost-center-expenditure', [ReportController::class, 'costCenterExpenditure'])->name('reports.cost_center_expenditure');
    Route::get('/reports/budget-variance', [ReportController::class, 'budgetVariance'])->name('reports.budget_variance');

    // Regional Accounts Offices (আহিদ - RAO)
    Route::get('/raos', [RegionalAccountsOfficeController::class, 'index'])->name('raos.index');
    Route::post('/raos', [RegionalAccountsOfficeController::class, 'store'])->name('raos.store');
    Route::put('/raos/{rao}', [RegionalAccountsOfficeController::class, 'update'])->name('raos.update');
    Route::delete('/raos/{rao}', [RegionalAccountsOfficeController::class, 'destroy'])->name('raos.destroy');
    Route::post('/raos/{rao}/assign', [RegionalAccountsOfficeController::class, 'assign'])->name('raos.assign');

    // Cost Centers (with Excel upload/download & user assignment)
    Route::get('/cost-centers', [CostCenterController::class, 'index'])->name('cost_centers.index');
    Route::post('/cost-centers', [CostCenterController::class, 'store'])->name('cost_centers.store');
    Route::put('/cost-centers/{costCenter}', [CostCenterController::class, 'update'])->name('cost_centers.update');
    Route::delete('/cost-centers/{costCenter}', [CostCenterController::class, 'destroy'])->name('cost_centers.destroy');
    Route::post('/cost-centers/{costCenter}/assign-users', [CostCenterController::class, 'assignUsers'])->name('cost_centers.assign_users');
    Route::get('/cost-centers-export', [CostCenterController::class, 'export'])->name('cost_centers.export');
    Route::get('/cost-centers-template', [CostCenterController::class, 'downloadTemplate'])->name('cost_centers.template');
    Route::post('/cost-centers-import', [CostCenterController::class, 'import'])->name('cost_centers.import');

    // Bank Accounts (with Excel upload/download & user assignment)
    Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank_accounts.index');
    Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank_accounts.store');
    Route::put('/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])->name('bank_accounts.update');
    Route::delete('/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank_accounts.destroy');
    Route::post('/bank-accounts/{bankAccount}/assign-users', [BankAccountController::class, 'assignUsers'])->name('bank_accounts.assign_users');
    Route::get('/bank-accounts-export', [BankAccountController::class, 'export'])->name('bank_accounts.export');
    Route::get('/bank-accounts-template', [BankAccountController::class, 'downloadTemplate'])->name('bank_accounts.template');
    Route::post('/bank-accounts-import', [BankAccountController::class, 'import'])->name('bank_accounts.import');

    // Chart of Accounts (with Excel upload/download & user assignment)
    Route::get('/chart-of-accounts', [ChartOfAccountController::class, 'index'])->name('chart_of_accounts.index');
    Route::post('/chart-of-accounts', [ChartOfAccountController::class, 'store'])->name('chart_of_accounts.store');
    Route::put('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'update'])->name('chart_of_accounts.update');
    Route::delete('/chart-of-accounts/{chartOfAccount}', [ChartOfAccountController::class, 'destroy'])->name('chart_of_accounts.destroy');
    Route::post('/chart-of-accounts/{chartOfAccount}/assign-users', [ChartOfAccountController::class, 'assignUsers'])->name('chart_of_accounts.assign_users');
    Route::get('/chart-of-accounts-export', [ChartOfAccountController::class, 'export'])->name('chart_of_accounts.export');
    Route::get('/chart-of-accounts-template', [ChartOfAccountController::class, 'downloadTemplate'])->name('chart_of_accounts.template');
    Route::post('/chart-of-accounts-import', [ChartOfAccountController::class, 'import'])->name('chart_of_accounts.import');

    // Accounting Period Setup
    Route::get('/accounting-periods', [AccountingPeriodController::class, 'index'])->name('accounting_periods.index');
    Route::post('/accounting-periods/fiscal-year', [AccountingPeriodController::class, 'storeYear'])->name('accounting_periods.store_year');
    Route::post('/accounting-periods/fiscal-year/{fiscalYear}/set-current', [AccountingPeriodController::class, 'setCurrent'])->name('accounting_periods.set_current');
    Route::post('/accounting-periods/{period}/status', [AccountingPeriodController::class, 'updatePeriodStatus'])->name('accounting_periods.update_status');

    // Budget
    Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('/budget', [BudgetController::class, 'store'])->name('budget.store');

    // Bank Reconciliation
    Route::get('/reconciliation', [BankReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('/reconciliation/create', [BankReconciliationController::class, 'create'])->name('reconciliation.create');
    Route::post('/reconciliation', [BankReconciliationController::class, 'store'])->name('reconciliation.store');
    Route::get('/reconciliation/{reconciliation}', [BankReconciliationController::class, 'show'])->name('reconciliation.show');

    // Year Closing
    Route::get('/year-closing', [YearClosingController::class, 'index'])->name('year_closing.index');
    Route::post('/year-closing/process', [YearClosingController::class, 'processClose'])->name('year_closing.process');

    // Individual Users Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
});
