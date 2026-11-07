<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\VoucherEntry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();

        $costCenters = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? CostCenter::active()->get()
            : $user->costCenters;

        $selectedAccountId = $request->input('chart_of_account_id');
        $selectedAccount = $selectedAccountId ? ChartOfAccount::find($selectedAccountId) : $accounts->first();

        $fromDate = $request->input('from_date', date('Y-07-01'));
        $toDate   = $request->input('to_date', date('Y-m-d'));
        $costCenterId = $request->input('cost_center_id');

        $ledgerData = [];
        $openingBalance = 0.0;
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $closingBalance = 0.0;

        if ($selectedAccount) {
            $isDebitNormal = $selectedAccount->normal_balance === 'debit';
            $baseOpening = (float) $selectedAccount->opening_balance;

            // Compute prior transactions before from_date
            $priorQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate) {
                $q->where('status', 'posted')->where('date', '<', $fromDate);
            })->where('chart_of_account_id', $selectedAccount->id);

            if ($costCenterId) {
                $priorQuery->where(function ($q) use ($costCenterId) {
                    $q->where('cost_center_id', $costCenterId)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenterId));
                });
            }

            $priorDebit  = (float) $priorQuery->sum('debit_amount');
            $priorCredit = (float) $priorQuery->sum('credit_amount');

            $openingBalance = $isDebitNormal
                ? ($baseOpening + $priorDebit - $priorCredit)
                : ($baseOpening + $priorCredit - $priorDebit);

            // Fetch transactions within date range
            $transQuery = VoucherEntry::with(['voucher.costCenter', 'costCenter'])
                ->whereHas('voucher', function ($q) use ($fromDate, $toDate) {
                    $q->where('status', 'posted')
                      ->whereBetween('date', [$fromDate, $toDate]);
                })
                ->where('chart_of_account_id', $selectedAccount->id);

            if ($costCenterId) {
                $transQuery->where(function ($q) use ($costCenterId) {
                    $q->where('cost_center_id', $costCenterId)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenterId));
                });
            }

            $entries = $transQuery->join('vouchers', 'voucher_entries.voucher_id', '=', 'vouchers.id')
                ->orderBy('vouchers.date')
                ->orderBy('vouchers.id')
                ->select('voucher_entries.*')
                ->get();

            $runningBalance = $openingBalance;

            foreach ($entries as $entry) {
                $dr = (float) $entry->debit_amount;
                $cr = (float) $entry->credit_amount;
                $totalDebit += $dr;
                $totalCredit += $cr;

                if ($isDebitNormal) {
                    $runningBalance += ($dr - $cr);
                } else {
                    $runningBalance += ($cr - $dr);
                }

                $ledgerData[] = [
                    'date'            => $entry->voucher->date->format('d/m/Y'),
                    'voucher_number'  => $entry->voucher->voucher_number,
                    'voucher_type'    => $entry->voucher->bangla_voucher_type,
                    'cost_center'     => $entry->costCenter?->name_bn ?? $entry->voucher->costCenter?->name_bn,
                    'narration'       => $entry->line_narration ?: $entry->voucher->narration,
                    'debit'           => $dr,
                    'credit'          => $cr,
                    'running_balance' => $runningBalance,
                ];
            }

            $closingBalance = $runningBalance;
        }

        return view('ledger.index', compact(
            'accounts',
            'costCenters',
            'selectedAccount',
            'fromDate',
            'toDate',
            'costCenterId',
            'openingBalance',
            'ledgerData',
            'totalDebit',
            'totalCredit',
            'closingBalance'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $selectedAccount = ChartOfAccount::findOrFail($request->input('chart_of_account_id'));
        $fromDate = $request->input('from_date', date('Y-07-01'));
        $toDate   = $request->input('to_date', date('Y-m-d'));
        $costCenterId = $request->input('cost_center_id');

        $fileName = 'General_Ledger_' . $selectedAccount->account_code . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($selectedAccount, $fromDate, $toDate, $costCenterId) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            fputcsv($handle, ['বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড (বিউবো) - সাধারণ খতিয়ান (General Ledger)']);
            fputcsv($handle, ['হিসাব কোড ও নাম:', $selectedAccount->account_code . ' - ' . $selectedAccount->account_name_bn]);
            fputcsv($handle, ['সময়সীমা:', $fromDate . ' হতে ' . $toDate]);
            fputcsv($handle, []);
            fputcsv($handle, ['তারিখ', 'ভাউচার নম্বর', 'ধরন', 'কস্ট সেন্টার', 'বিবরণ', 'ডেবিট (টাকা)', 'ক্রেডিট (টাকা)', 'ব্যালেন্স (টাকা)']);

            $isDebitNormal = $selectedAccount->normal_balance === 'debit';
            $baseOpening = (float) $selectedAccount->opening_balance;

            $priorQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate) {
                $q->where('status', 'posted')->where('date', '<', $fromDate);
            })->where('chart_of_account_id', $selectedAccount->id);

            if ($costCenterId) {
                $priorQuery->where(function ($q) use ($costCenterId) {
                    $q->where('cost_center_id', $costCenterId)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenterId));
                });
            }

            $priorDebit  = (float) $priorQuery->sum('debit_amount');
            $priorCredit = (float) $priorQuery->sum('credit_amount');

            $runningBalance = $isDebitNormal
                ? ($baseOpening + $priorDebit - $priorCredit)
                : ($baseOpening + $priorCredit - $priorDebit);

            fputcsv($handle, [$fromDate, '-', 'প্রারম্ভিক জের', '-', 'প্রারম্ভিক ব্যালেন্স', '', '', number_format($runningBalance, 2)]);

            $transQuery = VoucherEntry::with(['voucher.costCenter', 'costCenter'])
                ->whereHas('voucher', function ($q) use ($fromDate, $toDate) {
                    $q->where('status', 'posted')
                      ->whereBetween('date', [$fromDate, $toDate]);
                })
                ->where('chart_of_account_id', $selectedAccount->id);

            if ($costCenterId) {
                $transQuery->where(function ($q) use ($costCenterId) {
                    $q->where('cost_center_id', $costCenterId)
                      ->orWhereHas('voucher', fn($v) => $v->where('cost_center_id', $costCenterId));
                });
            }

            $entries = $transQuery->join('vouchers', 'voucher_entries.voucher_id', '=', 'vouchers.id')
                ->orderBy('vouchers.date')
                ->orderBy('vouchers.id')
                ->select('voucher_entries.*')
                ->get();

            foreach ($entries as $entry) {
                $dr = (float) $entry->debit_amount;
                $cr = (float) $entry->credit_amount;
                if ($isDebitNormal) {
                    $runningBalance += ($dr - $cr);
                } else {
                    $runningBalance += ($cr - $dr);
                }

                fputcsv($handle, [
                    $entry->voucher->date->format('d/m/Y'),
                    $entry->voucher->voucher_number,
                    $entry->voucher->bangla_voucher_type,
                    $entry->costCenter?->name_bn ?? $entry->voucher->costCenter?->name_bn,
                    $entry->line_narration ?: $entry->voucher->narration,
                    $dr > 0 ? number_format($dr, 2) : '',
                    $cr > 0 ? number_format($cr, 2) : '',
                    number_format($runningBalance, 2),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
