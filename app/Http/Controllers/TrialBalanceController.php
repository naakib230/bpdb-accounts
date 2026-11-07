<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $costCenters = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? CostCenter::active()->get()
            : $user->costCenters;

        $asOfDate     = $request->input('as_of_date', date('Y-m-d'));
        $costCenterId = $request->input('cost_center_id');

        $rows = [];
        $grandTotalOpeningDr = 0.0;
        $grandTotalOpeningCr = 0.0;
        $grandTotalPeriodDr  = 0.0;
        $grandTotalPeriodCr  = 0.0;
        $grandTotalClosingDr = 0.0;
        $grandTotalClosingCr = 0.0;

        $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();

        foreach ($accounts as $acc) {
            $isDebitNormal = $acc->normal_balance === 'debit';
            $baseOpening = (float) $acc->opening_balance;

            $transQuery = VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
                $q->where('status', 'posted')->where('date', '<=', $asOfDate);
            })->where('chart_of_account_id', $acc->id);

            if ($costCenterId) {
                $transQuery->where(function ($q) use ($costCenterId) {
                    $q->where('cost_center_id', $costCenterId)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenterId));
                });
            }

            $periodDr = (float) $transQuery->sum('debit_amount');
            $periodCr = (float) $transQuery->sum('credit_amount');

            // Opening Balance
            $openingDr = $isDebitNormal ? $baseOpening : 0.0;
            $openingCr = ! $isDebitNormal ? $baseOpening : 0.0;

            // Closing Net Balance
            $netMovement = $periodDr - $periodCr;
            $netTotal = ($isDebitNormal ? $baseOpening : -$baseOpening) + $netMovement;

            $closingDr = $netTotal > 0 ? $netTotal : 0.0;
            $closingCr = $netTotal < 0 ? abs($netTotal) : 0.0;

            // Don't show rows if entirely zero across the board
            if ($openingDr == 0 && $openingCr == 0 && $periodDr == 0 && $periodCr == 0 && $closingDr == 0 && $closingCr == 0) {
                continue;
            }

            $grandTotalOpeningDr += $openingDr;
            $grandTotalOpeningCr += $openingCr;
            $grandTotalPeriodDr  += $periodDr;
            $grandTotalPeriodCr  += $periodCr;
            $grandTotalClosingDr += $closingDr;
            $grandTotalClosingCr += $closingCr;

            $rows[] = [
                'code'            => $acc->account_code,
                'name_bn'         => $acc->account_name_bn,
                'name'            => $acc->account_name,
                'type'            => $acc->account_type,
                'normal_balance'  => $acc->normal_balance,
                'opening_dr'      => $openingDr,
                'opening_cr'      => $openingCr,
                'period_dr'       => $periodDr,
                'period_cr'       => $periodCr,
                'closing_dr'      => $closingDr,
                'closing_cr'      => $closingCr,
            ];
        }

        $isBalanced = abs($grandTotalClosingDr - $grandTotalClosingCr) < 0.01;

        return view('trial_balance.index', compact(
            'costCenters',
            'asOfDate',
            'costCenterId',
            'rows',
            'grandTotalOpeningDr',
            'grandTotalOpeningCr',
            'grandTotalPeriodDr',
            'grandTotalPeriodCr',
            'grandTotalClosingDr',
            'grandTotalClosingCr',
            'isBalanced'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $asOfDate     = $request->input('as_of_date', date('Y-m-d'));
        $costCenterId = $request->input('cost_center_id');
        $fileName     = 'Trial_Balance_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($asOfDate, $costCenterId) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড (বিউবো) - রেওয়ামিল (Trial Balance)']);
            fputcsv($handle, ['তারিখ পর্যন্ত:', $asOfDate]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'হিসাব কোড',
                'হিসাবের নাম (বাংলা)',
                'হিসাবের ধরন',
                'প্রারম্ভিক ডেবিট',
                'প্রারম্ভিক ক্রেডিট',
                'চলতি লেনদেন ডেবিট',
                'চলতি লেনদেন ক্রেডিট',
                'সমাপনী ডেবিট',
                'সমাপনী ক্রেডিট'
            ]);

            $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();
            $totDr = 0; $totCr = 0;

            foreach ($accounts as $acc) {
                $isDebitNormal = $acc->normal_balance === 'debit';
                $baseOpening = (float) $acc->opening_balance;

                $transQuery = VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
                    $q->where('status', 'posted')->where('date', '<=', $asOfDate);
                })->where('chart_of_account_id', $acc->id);

                if ($costCenterId) {
                    $transQuery->where(function ($q) use ($costCenterId) {
                        $q->where('cost_center_id', $costCenterId)
                          ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenterId));
                    });
                }

                $periodDr = (float) $transQuery->sum('debit_amount');
                $periodCr = (float) $transQuery->sum('credit_amount');

                $openingDr = $isDebitNormal ? $baseOpening : 0.0;
                $openingCr = ! $isDebitNormal ? $baseOpening : 0.0;

                $netMovement = $periodDr - $periodCr;
                $netTotal = ($isDebitNormal ? $baseOpening : -$baseOpening) + $netMovement;

                $closingDr = $netTotal > 0 ? $netTotal : 0.0;
                $closingCr = $netTotal < 0 ? abs($netTotal) : 0.0;

                if ($openingDr == 0 && $openingCr == 0 && $periodDr == 0 && $periodCr == 0 && $closingDr == 0 && $closingCr == 0) {
                    continue;
                }

                $totDr += $closingDr;
                $totCr += $closingCr;

                fputcsv($handle, [
                    $acc->account_code,
                    $acc->account_name_bn,
                    strtoupper($acc->account_type),
                    $openingDr > 0 ? number_format($openingDr, 2) : '',
                    $openingCr > 0 ? number_format($openingCr, 2) : '',
                    $periodDr > 0 ? number_format($periodDr, 2) : '',
                    $periodCr > 0 ? number_format($periodCr, 2) : '',
                    $closingDr > 0 ? number_format($closingDr, 2) : '',
                    $closingCr > 0 ? number_format($closingCr, 2) : '',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['সর্বমোট যোগফল', '', '', '', '', '', '', number_format($totDr, 2), number_format($totCr, 2)]);

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
