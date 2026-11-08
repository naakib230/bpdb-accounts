<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\RegionalAccountsOffice;
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

        // Accessible RAOs & Cost Centers
        if ($user->isCentralUser()) {
            $raos        = RegionalAccountsOffice::active()->orderBy('code')->get();
            $costCenters = CostCenter::active()->orderBy('code')->get();
        } else {
            $userRaoId   = $user->regional_accounts_office_id;
            $raos        = RegionalAccountsOffice::where('id', $userRaoId)->get();
            $costCenters = CostCenter::active()
                ->where(function ($q) use ($user, $userRaoId) {
                    if ($userRaoId) {
                        $q->where('regional_accounts_office_id', $userRaoId);
                    }
                    $q->orWhereIn('id', $user->costCenters->pluck('id'));
                })
                ->orderBy('code')
                ->get();
        }

        // Filters
        $scopeLevel   = $request->input('scope_level', 'grand_consolidated'); // grand_consolidated, rao_consolidated, cost_center
        $raoId        = $request->input('rao_id');
        $costCenterId = $request->input('cost_center_id');
        $tbType       = $request->input('tb_type', 'all'); // all, revenue_generation, revenue_distribution, overhead, deposit_works, development
        $viewFormat   = $request->input('view_format', 'matrix'); // matrix (Format A-1), standard (3-stage)
        $asOfDate     = $request->input('as_of_date', date('Y-m-d'));

        // Selected Scope Metadata
        $selectedRao        = $raoId ? RegionalAccountsOffice::find($raoId) : null;
        $selectedCostCenter = $costCenterId ? CostCenter::find($costCenterId) : null;

        // Resolve targeted Cost Center IDs based on Scope
        $targetCcIds = null;
        if ($scopeLevel === 'cost_center' && $costCenterId) {
            $targetCcIds = [$costCenterId];
        } elseif ($scopeLevel === 'rao_consolidated' && $raoId) {
            $targetCcIds = CostCenter::where('regional_accounts_office_id', $raoId)->pluck('id')->toArray();
        } elseif (! $user->isCentralUser()) {
            $targetCcIds = $costCenters->pluck('id')->toArray();
        }

        // Map Cost Center IDs to their trial_balance_type
        $ccTypeMap = CostCenter::pluck('trial_balance_type', 'id')->toArray();

        // Leaf Accounts
        $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();

        // Base Query for posted entries up to as_of_date
        $baseEntriesQuery = VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
            $q->where('status', 'posted')->where('date', '<=', $asOfDate);
        });

        if ($targetCcIds !== null) {
            $baseEntriesQuery->where(function ($q) use ($targetCcIds) {
                $q->whereIn('cost_center_id', $targetCcIds)
                  ->orWhereHas('voucher', fn($v) => $v->whereIn('cost_center_id', $targetCcIds));
            });
        }

        // Aggregate entries grouped by chart_of_account_id and cost_center_id
        $rawEntries = (clone $baseEntriesQuery)
            ->selectRaw('chart_of_account_id, COALESCE(cost_center_id, 0) as cc_id, SUM(debit_amount) as total_dr, SUM(credit_amount) as total_cr')
            ->groupBy('chart_of_account_id', 'cc_id')
            ->get();

        $accountMovements = [];
        foreach ($rawEntries as $entry) {
            $coaId = $entry->chart_of_account_id;
            $ccId  = $entry->cc_id;
            $type  = $ccTypeMap[$ccId] ?? 'overhead';

            if (! isset($accountMovements[$coaId])) {
                $accountMovements[$coaId] = [
                    'all'                  => ['dr' => 0.0, 'cr' => 0.0],
                    'revenue_generation'   => ['dr' => 0.0, 'cr' => 0.0],
                    'revenue_distribution' => ['dr' => 0.0, 'cr' => 0.0],
                    'overhead'             => ['dr' => 0.0, 'cr' => 0.0],
                    'deposit_works'        => ['dr' => 0.0, 'cr' => 0.0],
                    'development'          => ['dr' => 0.0, 'cr' => 0.0],
                ];
            }

            $dr = (float) $entry->total_dr;
            $cr = (float) $entry->total_cr;

            $accountMovements[$coaId]['all']['dr'] += $dr;
            $accountMovements[$coaId]['all']['cr'] += $cr;

            if (isset($accountMovements[$coaId][$type])) {
                $accountMovements[$coaId][$type]['dr'] += $dr;
                $accountMovements[$coaId][$type]['cr'] += $cr;
            }
        }

        $rows = [];
        $totals = [
            'consolidated_dr' => 0.0,
            'consolidated_cr' => 0.0,
            'generation_dr'   => 0.0,
            'generation_cr'   => 0.0,
            'distribution_dr' => 0.0,
            'distribution_cr' => 0.0,
            'overhead_dr'     => 0.0,
            'overhead_cr'     => 0.0,
            'deposit_dr'      => 0.0,
            'deposit_cr'      => 0.0,
            'development_dr'  => 0.0,
            'development_cr'  => 0.0,
            'opening_dr'      => 0.0,
            'opening_cr'      => 0.0,
            'period_dr'       => 0.0,
            'period_cr'       => 0.0,
            'closing_dr'      => 0.0,
            'closing_cr'      => 0.0,
        ];

        foreach ($accounts as $acc) {
            $isDebitNormal = $acc->normal_balance === 'debit';
            $baseOpening   = (float) $acc->opening_balance;
            $mov           = $accountMovements[$acc->id] ?? null;

            $openingDr = $isDebitNormal ? $baseOpening : 0.0;
            $openingCr = ! $isDebitNormal ? $baseOpening : 0.0;

            $totalDr = $mov['all']['dr'] ?? 0.0;
            $totalCr = $mov['all']['cr'] ?? 0.0;

            $netMovement = $totalDr - $totalCr;
            $netTotal = ($isDebitNormal ? $baseOpening : -$baseOpening) + $netMovement;

            $closingDr = $netTotal > 0 ? $netTotal : 0.0;
            $closingCr = $netTotal < 0 ? abs($netTotal) : 0.0;

            // Type movements
            $calcNet = function ($dr, $cr) use ($isDebitNormal) {
                $diff = $dr - $cr;
                return [
                    'dr' => $diff > 0 ? $diff : 0.0,
                    'cr' => $diff < 0 ? abs($diff) : 0.0,
                ];
            };

            $gen   = $calcNet($mov['revenue_generation']['dr'] ?? 0, $mov['revenue_generation']['cr'] ?? 0);
            $dist  = $calcNet($mov['revenue_distribution']['dr'] ?? 0, $mov['revenue_distribution']['cr'] ?? 0);
            $ovh   = $calcNet($mov['overhead']['dr'] ?? 0, $mov['overhead']['cr'] ?? 0);
            $dep   = $calcNet($mov['deposit_works']['dr'] ?? 0, $mov['deposit_works']['cr'] ?? 0);
            $dev   = $calcNet($mov['development']['dr'] ?? 0, $mov['development']['cr'] ?? 0);

            // Filter by selected TB type if not 'all'
            if ($tbType !== 'all') {
                $typeVal = match ($tbType) {
                    'revenue_generation'   => $gen,
                    'revenue_distribution' => $dist,
                    'overhead'             => $ovh,
                    'deposit_works'        => $dep,
                    'development'          => $dev,
                    default                => ['dr' => $closingDr, 'cr' => $closingCr],
                };
                if ($typeVal['dr'] == 0 && $typeVal['cr'] == 0) {
                    continue;
                }
            }

            // Skip zero rows
            if ($closingDr == 0 && $closingCr == 0 && $openingDr == 0 && $openingCr == 0 && $totalDr == 0 && $totalCr == 0) {
                continue;
            }

            $totals['consolidated_dr'] += $closingDr;
            $totals['consolidated_cr'] += $closingCr;
            $totals['generation_dr']   += $gen['dr'];
            $totals['generation_cr']   += $gen['cr'];
            $totals['distribution_dr'] += $dist['dr'];
            $totals['distribution_cr'] += $dist['cr'];
            $totals['overhead_dr']     += $ovh['dr'];
            $totals['overhead_cr']     += $ovh['cr'];
            $totals['deposit_dr']      += $dep['dr'];
            $totals['deposit_cr']      += $dep['cr'];
            $totals['development_dr']  += $dev['dr'];
            $totals['development_cr']  += $dev['cr'];

            $totals['opening_dr'] += $openingDr;
            $totals['opening_cr'] += $openingCr;
            $totals['period_dr']  += $totalDr;
            $totals['period_cr']  += $totalCr;
            $totals['closing_dr'] += $closingDr;
            $totals['closing_cr'] += $closingCr;

            $rows[] = [
                'code'           => $acc->account_code,
                'name_bn'        => $acc->account_name_bn,
                'name'           => $acc->account_name,
                'type'           => $acc->account_type,
                'normal_balance' => $acc->normal_balance,
                'opening_dr'     => $openingDr,
                'opening_cr'     => $openingCr,
                'period_dr'      => $totalDr,
                'period_cr'      => $totalCr,
                'closing_dr'     => $closingDr,
                'closing_cr'     => $closingCr,
                'generation_dr'  => $gen['dr'],
                'generation_cr'  => $gen['cr'],
                'distribution_dr'=> $dist['dr'],
                'distribution_cr'=> $dist['cr'],
                'overhead_dr'    => $ovh['dr'],
                'overhead_cr'    => $ovh['cr'],
                'deposit_dr'     => $dep['dr'],
                'deposit_cr'     => $dep['cr'],
                'development_dr' => $dev['dr'],
                'development_cr' => $dev['cr'],
            ];
        }

        $isBalanced = abs($totals['consolidated_dr'] - $totals['consolidated_cr']) < 0.05;

        return view('trial_balance.index', compact(
            'raos',
            'costCenters',
            'scopeLevel',
            'raoId',
            'costCenterId',
            'selectedRao',
            'selectedCostCenter',
            'tbType',
            'viewFormat',
            'asOfDate',
            'rows',
            'totals',
            'isBalanced'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $scopeLevel   = $request->input('scope_level', 'grand_consolidated');
        $raoId        = $request->input('rao_id');
        $costCenterId = $request->input('cost_center_id');
        $asOfDate     = $request->input('as_of_date', date('Y-m-d'));
        $viewFormat   = $request->input('view_format', 'matrix');

        $fileName = 'BPDB_Trial_Balance_' . strtoupper($scopeLevel) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($scopeLevel, $raoId, $costCenterId, $asOfDate, $viewFormat) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            $title = 'বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড (বিউবো) - রেওয়ামিল (Trial Balance)';
            if ($scopeLevel === 'grand_consolidated') {
                $title .= ' - Grand Consolidated (All RAOs)';
            } elseif ($scopeLevel === 'rao_consolidated' && $raoId) {
                $rao = RegionalAccountsOffice::find($raoId);
                $title .= " - RAO: {$rao?->name_bn} ({$rao?->code})";
            } elseif ($scopeLevel === 'cost_center' && $costCenterId) {
                $cc = CostCenter::find($costCenterId);
                $title .= " - Cost Center: {$cc?->name_bn} ({$cc?->code})";
            }

            fputcsv($handle, [$title]);
            fputcsv($handle, ['তারিখ পর্যন্ত:', $asOfDate, 'ফরম্যাট:', $viewFormat === 'matrix' ? 'Format A-1 Matrix' : 'Standard 3-Stage']);
            fputcsv($handle, []);

            if ($viewFormat === 'matrix') {
                fputcsv($handle, [
                    'হিসাব কোড',
                    'হিসাবের নাম',
                    'Consolidated Dr',
                    'Consolidated Cr',
                    'Generation Dr',
                    'Generation Cr',
                    'Distribution Dr',
                    'Distribution Cr',
                    'Overhead Dr',
                    'Overhead Cr',
                    'Deposit Works Dr',
                    'Deposit Works Cr',
                    'Development Dr',
                    'Development Cr',
                ]);
            } else {
                fputcsv($handle, [
                    'হিসাব কোড',
                    'হিসাবের নাম',
                    'প্রারম্ভিক ডেবিট',
                    'প্রারম্ভিক ক্রেডিট',
                    'চলতি লেনদেন ডেবিট',
                    'চলতি লেনদেন ক্রেডিট',
                    'সমাপনী ডেবিট',
                    'সমাপনী ক্রেডিট',
                ]);
            }

            // Gather and export row data
            $targetCcIds = null;
            if ($scopeLevel === 'cost_center' && $costCenterId) {
                $targetCcIds = [$costCenterId];
            } elseif ($scopeLevel === 'rao_consolidated' && $raoId) {
                $targetCcIds = CostCenter::where('regional_accounts_office_id', $raoId)->pluck('id')->toArray();
            }

            $ccTypeMap = CostCenter::pluck('trial_balance_type', 'id')->toArray();
            $accounts  = ChartOfAccount::leaf()->orderBy('account_code')->get();

            $baseQuery = VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
                $q->where('status', 'posted')->where('date', '<=', $asOfDate);
            });
            if ($targetCcIds !== null) {
                $baseQuery->where(function ($q) use ($targetCcIds) {
                    $q->whereIn('cost_center_id', $targetCcIds)
                      ->orWhereHas('voucher', fn($v) => $v->whereIn('cost_center_id', $targetCcIds));
                });
            }

            $rawEntries = (clone $baseQuery)
                ->selectRaw('chart_of_account_id, COALESCE(cost_center_id, 0) as cc_id, SUM(debit_amount) as total_dr, SUM(credit_amount) as total_cr')
                ->groupBy('chart_of_account_id', 'cc_id')
                ->get();

            $movs = [];
            foreach ($rawEntries as $e) {
                $cId = $e->chart_of_account_id;
                $typ = $ccTypeMap[$e->cc_id] ?? 'overhead';
                $movs[$cId]['all']['dr'] = ($movs[$cId]['all']['dr'] ?? 0) + (float) $e->total_dr;
                $movs[$cId]['all']['cr'] = ($movs[$cId]['all']['cr'] ?? 0) + (float) $e->total_cr;
                $movs[$cId][$typ]['dr'] = ($movs[$cId][$typ]['dr'] ?? 0) + (float) $e->total_dr;
                $movs[$cId][$typ]['cr'] = ($movs[$cId][$typ]['cr'] ?? 0) + (float) $e->total_cr;
            }

            $totDr = 0; $totCr = 0;
            foreach ($accounts as $acc) {
                $isDebit = $acc->normal_balance === 'debit';
                $baseOp  = (float) $acc->opening_balance;
                $opDr    = $isDebit ? $baseOp : 0;
                $opCr    = ! $isDebit ? $baseOp : 0;
                $tDr     = $movs[$acc->id]['all']['dr'] ?? 0;
                $tCr     = $movs[$acc->id]['all']['cr'] ?? 0;
                $net     = ($isDebit ? $baseOp : -$baseOp) + ($tDr - $tCr);
                $clDr    = $net > 0 ? $net : 0;
                $clCr    = $net < 0 ? abs($net) : 0;

                if ($clDr == 0 && $clCr == 0 && $opDr == 0 && $opCr == 0 && $tDr == 0 && $tCr == 0) {
                    continue;
                }

                $totDr += $clDr;
                $totCr += $clCr;

                if ($viewFormat === 'matrix') {
                    $calcDiff = function ($dr, $cr) {
                        $d = $dr - $cr;
                        return ['dr' => $d > 0 ? $d : 0, 'cr' => $d < 0 ? abs($d) : 0];
                    };
                    $gen  = $calcDiff($movs[$acc->id]['revenue_generation']['dr'] ?? 0, $movs[$acc->id]['revenue_generation']['cr'] ?? 0);
                    $dist = $calcDiff($movs[$acc->id]['revenue_distribution']['dr'] ?? 0, $movs[$acc->id]['revenue_distribution']['cr'] ?? 0);
                    $ovh  = $calcDiff($movs[$acc->id]['overhead']['dr'] ?? 0, $movs[$acc->id]['overhead']['cr'] ?? 0);
                    $dep  = $calcDiff($movs[$acc->id]['deposit_works']['dr'] ?? 0, $movs[$acc->id]['deposit_works']['cr'] ?? 0);
                    $dev  = $calcDiff($movs[$acc->id]['development']['dr'] ?? 0, $movs[$acc->id]['development']['cr'] ?? 0);

                    fputcsv($handle, [
                        $acc->account_code,
                        $acc->account_name_bn ?: $acc->account_name,
                        $clDr > 0 ? number_format($clDr, 2) : '',
                        $clCr > 0 ? number_format($clCr, 2) : '',
                        $gen['dr'] > 0 ? number_format($gen['dr'], 2) : '',
                        $gen['cr'] > 0 ? number_format($gen['cr'], 2) : '',
                        $dist['dr'] > 0 ? number_format($dist['dr'], 2) : '',
                        $dist['cr'] > 0 ? number_format($dist['cr'], 2) : '',
                        $ovh['dr'] > 0 ? number_format($ovh['dr'], 2) : '',
                        $ovh['cr'] > 0 ? number_format($ovh['cr'], 2) : '',
                        $dep['dr'] > 0 ? number_format($dep['dr'], 2) : '',
                        $dep['cr'] > 0 ? number_format($dep['cr'], 2) : '',
                        $dev['dr'] > 0 ? number_format($dev['dr'], 2) : '',
                        $dev['cr'] > 0 ? number_format($dev['cr'], 2) : '',
                    ]);
                } else {
                    fputcsv($handle, [
                        $acc->account_code,
                        $acc->account_name_bn ?: $acc->account_name,
                        $opDr > 0 ? number_format($opDr, 2) : '',
                        $opCr > 0 ? number_format($opCr, 2) : '',
                        $tDr > 0 ? number_format($tDr, 2) : '',
                        $tCr > 0 ? number_format($tCr, 2) : '',
                        $clDr > 0 ? number_format($clDr, 2) : '',
                        $clCr > 0 ? number_format($clCr, 2) : '',
                    ]);
                }
            }

            fputcsv($handle, []);
            fputcsv($handle, ['সর্বমোট যোগফল (Grand Total)', '', number_format($totDr, 2), number_format($totCr, 2)]);

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
