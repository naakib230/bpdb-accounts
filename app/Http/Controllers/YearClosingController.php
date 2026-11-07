<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class YearClosingController extends Controller
{
    public function index(): View
    {
        $currentFy = FiscalYear::current();
        $unpostedCount = 0;
        $revenueTotal = 0.0;
        $expenseTotal = 0.0;
        $netSurplus = 0.0;

        if ($currentFy) {
            $unpostedCount = Voucher::where('fiscal_year_id', $currentFy->id)
                ->whereIn('status', ['draft', 'submitted'])
                ->count();

            $revenueTotal = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy) {
                $q->where('fiscal_year_id', $currentFy->id)->where('status', 'posted');
            })->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'revenue'))->sum('credit_amount');

            $expenseTotal = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy) {
                $q->where('fiscal_year_id', $currentFy->id)->where('status', 'posted');
            })->whereHas('chartOfAccount', fn($q) => $q->where('account_type', 'expense'))->sum('debit_amount');

            $netSurplus = $revenueTotal - $expenseTotal;
        }

        $allFiscalYears = FiscalYear::orderByDesc('start_date')->get();

        return view('year_closing.index', compact(
            'currentFy',
            'unpostedCount',
            'revenueTotal',
            'expenseTotal',
            'netSurplus',
            'allFiscalYears'
        ));
    }

    public function processClose(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSystemAdmin() && ! $user->isDirectorAccounts()) {
            abort(403, 'অর্থবছর সমাপনী করার ক্ষমতা কেবলমাত্র হিসাব পরিচালক এবং অ্যাডমিনের রয়েছে।');
        }

        $currentFy = FiscalYear::current();
        if (! $currentFy || $currentFy->is_closed) {
            return back()->with('error', 'কোনো উন্মুক্ত সক্রিয় অর্থবছর পাওয়া যায়নি।');
        }

        // 1. Check for unposted vouchers
        $unpostedCount = Voucher::where('fiscal_year_id', $currentFy->id)
            ->whereIn('status', ['draft', 'submitted'])
            ->count();

        if ($unpostedCount > 0) {
            return back()->with('error', "এই অর্থবছরে এখনও {$unpostedCount} টি খসড়া/অপেক্ষমাণ ভাউচার রয়েছে। বছর সমাপ্তির আগে সব ভাউচার পোস্ট বা মুছে ফেলতে হবে।");
        }

        // Next Fiscal Year
        $nextFy = FiscalYear::where('start_date', '>', $currentFy->start_date)->orderBy('start_date')->first();
        if (! $nextFy) {
            return back()->with('error', 'পরবর্তী অর্থবছর সেটআপ করা নেই। অনুগ্রহ করে Accounting Period Setup হতে পরবর্তী অর্থবছর তৈরি করুন।');
        }

        DB::transaction(function () use ($currentFy, $nextFy, $user) {
            $headCostCenter = CostCenter::where('code', 'CC-101')->first() ?? CostCenter::first();
            $retainedEarningsCoa = ChartOfAccount::where('account_code', '301020')->first();

            // 1. Calculate revenues and expenses balances to close
            $revenueHeads = ChartOfAccount::leaf()->where('account_type', 'revenue')->get();
            $expenseHeads = ChartOfAccount::leaf()->where('account_type', 'expense')->get();

            $closingEntries = [];
            $totalRevenueToClose = 0.0;
            $totalExpenseToClose = 0.0;

            foreach ($revenueHeads as $rev) {
                $cr = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy) {
                    $q->where('fiscal_year_id', $currentFy->id)->where('status', 'posted');
                })->where('chart_of_account_id', $rev->id)->sum('credit_amount');

                if ($cr > 0) {
                    $totalRevenueToClose += $cr;
                    // To close credit balance of revenue, DEBIT revenue
                    $closingEntries[] = [
                        'chart_of_account_id' => $rev->id,
                        'cost_center_id'      => $headCostCenter->id,
                        'debit_amount'        => $cr,
                        'credit_amount'       => 0.00,
                        'line_narration'      => 'রাজস্ব খাতের বছর সমাপনী উদ্বৃত্ত স্থানান্তর',
                    ];
                }
            }

            foreach ($expenseHeads as $exp) {
                $dr = (float) VoucherEntry::whereHas('voucher', function ($q) use ($currentFy) {
                    $q->where('fiscal_year_id', $currentFy->id)->where('status', 'posted');
                })->where('chart_of_account_id', $exp->id)->sum('debit_amount');

                if ($dr > 0) {
                    $totalExpenseToClose += $dr;
                    // To close debit balance of expense, CREDIT expense
                    $closingEntries[] = [
                        'chart_of_account_id' => $exp->id,
                        'cost_center_id'      => $headCostCenter->id,
                        'debit_amount'        => 0.00,
                        'credit_amount'       => $dr,
                        'line_narration'      => 'ব্যয় খাতের বছর সমাপনী উদ্বৃত্ত স্থানান্তর',
                    ];
                }
            }

            $netDifference = $totalRevenueToClose - $totalExpenseToClose;

            if ($netDifference > 0) {
                // Surplus: Credit Retained Earnings
                $closingEntries[] = [
                    'chart_of_account_id' => $retainedEarningsCoa->id,
                    'cost_center_id'      => $headCostCenter->id,
                    'debit_amount'        => 0.00,
                    'credit_amount'       => $netDifference,
                    'line_narration'      => 'চলতি বছরের নিট উদ্বৃত্ত পুঞ্জীভূত সঞ্চিতিতে স্থানান্তর',
                ];
            } elseif ($netDifference < 0) {
                // Deficit: Debit Retained Earnings
                $closingEntries[] = [
                    'chart_of_account_id' => $retainedEarningsCoa->id,
                    'cost_center_id'      => $headCostCenter->id,
                    'debit_amount'        => abs($netDifference),
                    'credit_amount'       => 0.00,
                    'line_narration'      => 'চলতি বছরের নিট ঘাটতি পুঞ্জীভূত সঞ্চিতিতে স্থানান্তর',
                ];
            }

            // Create closing voucher if there were any revenues or expenses
            if (! empty($closingEntries)) {
                $voucherNumber = 'YEC-' . str_replace('-', '', $currentFy->name) . '-0001';
                $totDebit = max($totalRevenueToClose, $totalExpenseToClose);

                $closingVoucher = Voucher::create([
                    'voucher_number' => $voucherNumber,
                    'voucher_type'   => 'journal_voucher',
                    'date'           => $currentFy->end_date,
                    'fiscal_year_id' => $currentFy->id,
                    'cost_center_id' => $headCostCenter->id,
                    'total_debit'    => $totDebit,
                    'total_credit'   => $totDebit,
                    'status'         => 'posted',
                    'narration'      => "অর্থবছর {$currentFy->name} সমাপনী জার্নাল ভাউচার (আয় ও ব্যয় সমন্বয়)।",
                    'prepared_by_id' => $user->id,
                    'approved_by_id' => $user->id,
                    'approved_at'    => now(),
                ]);

                foreach ($closingEntries as $e) {
                    $closingVoucher->entries()->create($e);
                }
            }

            // Close current FY
            $currentFy->update([
                'is_current' => false,
                'is_closed'  => true,
                'closed_at'  => now(),
                'closed_by'  => $user->id,
            ]);

            // Set next FY as active
            $nextFy->update(['is_current' => true]);

            AuditLog::log('YEAR_CLOSING', "অর্থবছর {$currentFy->name} সমাপনী সম্পন্ন হয়েছে এবং পরবর্তী অর্থবছর {$nextFy->name} সক্রিয় করা হয়েছে।");
        });

        return redirect()->route('year_closing.index')
            ->with('success', "অর্থবছর {$currentFy->name} সফলভাবে সমাপ্ত হয়েছে এবং পরবর্তী অর্থবছর {$nextFy->name} সক্রিয় করা হয়েছে।");
    }
}
