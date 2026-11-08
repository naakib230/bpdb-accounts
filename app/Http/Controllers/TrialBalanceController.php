<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
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

        // Date Range Filters
        $currentFy       = FiscalYear::current();
        $defaultFromDate = $currentFy ? $currentFy->start_date->format('Y-m-d') : date('Y-07-01');
        $defaultToDate   = date('Y-m-d');

        $fromDate = $request->input('from_date', $request->input('start_date', $defaultFromDate));
        $toDate   = $request->input('to_date', $request->input('end_date', $request->input('as_of_date', $defaultToDate)));

        // Scope & View Filters
        $scopeLevel   = $request->input('scope_level', 'grand_consolidated'); // grand_consolidated, rao_consolidated, cost_center
        $raoId        = $request->input('rao_id');
        $costCenterId = $request->input('cost_center_id');
        $tbType       = $request->input('tb_type', 'all'); // all, revenue_generation, revenue_distribution, overhead, deposit_works, development
        $viewFormat   = $request->input('view_format', 'matrix'); // matrix (Format A-1), standard (3-stage)

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

        // 1. Prior posted entries (before $fromDate)
        $priorQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate) {
            $q->where('status', 'posted')->where('date', '<', $fromDate);
        });
        if ($targetCcIds !== null) {
            $priorQuery->where(function ($q) use ($targetCcIds) {
                $q->whereIn('cost_center_id', $targetCcIds)
                  ->orWhereHas('voucher', fn($v) => $v->whereIn('cost_center_id', $targetCcIds));
            });
        }
        $priorMovements = $priorQuery
            ->selectRaw('chart_of_account_id, SUM(debit_amount) as prior_dr, SUM(credit_amount) as prior_cr')
            ->groupBy('chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        // 2. Period posted entries (between $fromDate and $toDate)
        $periodQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate, $toDate) {
            $q->where('status', 'posted')
              ->where('date', '>=', $fromDate)
              ->where('date', '<=', $toDate);
        });
        if ($targetCcIds !== null) {
            $periodQuery->where(function ($q) use ($targetCcIds) {
                $q->whereIn('cost_center_id', $targetCcIds)
                  ->orWhereHas('voucher', fn($v) => $v->whereIn('cost_center_id', $targetCcIds));
            });
        }
        $rawPeriodEntries = (clone $periodQuery)
            ->selectRaw('chart_of_account_id, COALESCE(cost_center_id, 0) as cc_id, SUM(debit_amount) as total_dr, SUM(credit_amount) as total_cr')
            ->groupBy('chart_of_account_id', 'cc_id')
            ->get();

        $accountMovements = [];
        foreach ($rawPeriodEntries as $entry) {
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

            // Opening Balance as of $fromDate
            $pDr = (float) ($priorMovements[$acc->id]->prior_dr ?? 0.0);
            $pCr = (float) ($priorMovements[$acc->id]->prior_cr ?? 0.0);
            $netPrior = ($isDebitNormal ? $baseOpening : -$baseOpening) + ($pDr - $pCr);
            $openingDr = $netPrior > 0 ? $netPrior : 0.0;
            $openingCr = $netPrior < 0 ? abs($netPrior) : 0.0;

            // Period Movement between $fromDate and $toDate
            $totalDr = $mov['all']['dr'] ?? 0.0;
            $totalCr = $mov['all']['cr'] ?? 0.0;

            // Closing Balance as of $toDate
            $netTotal = ($isDebitNormal ? $baseOpening : -$baseOpening) + ($pDr - $pCr) + ($totalDr - $totalCr);
            $closingDr = $netTotal > 0 ? $netTotal : 0.0;
            $closingCr = $netTotal < 0 ? abs($netTotal) : 0.0;

            // Type movements in period
            $calcNet = function ($dr, $cr) {
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
                'name'           => $acc->account_name ?: $acc->account_name_bn,
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

        // Hide columns that have no figures (Part 2 of user request)
        $hasGeneration   = ($totals['generation_dr'] > 0 || $totals['generation_cr'] > 0);
        $hasDistribution = ($totals['distribution_dr'] > 0 || $totals['distribution_cr'] > 0);
        $hasOverhead     = ($totals['overhead_dr'] > 0 || $totals['overhead_cr'] > 0);
        $hasDeposit      = ($totals['deposit_dr'] > 0 || $totals['deposit_cr'] > 0);
        $hasDevelopment  = ($totals['development_dr'] > 0 || $totals['development_cr'] > 0);

        if ($tbType !== 'all') {
            $hasGeneration   = ($tbType === 'revenue_generation' && $hasGeneration);
            $hasDistribution = ($tbType === 'revenue_distribution' && $hasDistribution);
            $hasOverhead     = ($tbType === 'overhead' && $hasOverhead);
            $hasDeposit      = ($tbType === 'deposit_works' && $hasDeposit);
            $hasDevelopment  = ($tbType === 'development' && $hasDevelopment);
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
            'fromDate',
            'toDate',
            'rows',
            'totals',
            'hasGeneration',
            'hasDistribution',
            'hasOverhead',
            'hasDeposit',
            'hasDevelopment',
            'isBalanced'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $currentFy       = FiscalYear::current();
        $defaultFromDate = $currentFy ? $currentFy->start_date->format('Y-m-d') : date('Y-07-01');
        $defaultToDate   = date('Y-m-d');

        $fromDate     = $request->input('from_date', $request->input('start_date', $defaultFromDate));
        $toDate       = $request->input('to_date', $request->input('end_date', $request->input('as_of_date', $defaultToDate)));
        $scopeLevel   = $request->input('scope_level', 'grand_consolidated');
        $raoId        = $request->input('rao_id');
        $costCenterId = $request->input('cost_center_id');
        $tbType       = $request->input('tb_type', 'all');
        $viewFormat   = $request->input('view_format', 'matrix');

        $fileName = 'BPDB_Trial_Balance_' . strtoupper($scopeLevel) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($scopeLevel, $raoId, $costCenterId, $tbType, $fromDate, $toDate, $viewFormat) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            $title = 'Bangladesh Power Development Board (BPDB) - Trial Balance';
            if ($scopeLevel === 'grand_consolidated') {
                $title .= ' - Grand Consolidated (All RAOs)';
            } elseif ($scopeLevel === 'rao_consolidated' && $raoId) {
                $rao = RegionalAccountsOffice::find($raoId);
                $title .= " - RAO: {$rao?->name} ({$rao?->code})";
            } elseif ($scopeLevel === 'cost_center' && $costCenterId) {
                $cc = CostCenter::find($costCenterId);
                $title .= " - Cost Center: {$cc?->name} ({$cc?->code})";
            }

            fputcsv($handle, [$title]);
            fputcsv($handle, ['From Date:', $fromDate, 'To Date:', $toDate, 'Format:', $viewFormat === 'matrix' ? 'Format A-1 (Matrix)' : 'Standard (3-Stage)']);
            fputcsv($handle, []);

            // Target CC IDs
            $targetCcIds = null;
            if ($scopeLevel === 'cost_center' && $costCenterId) {
                $targetCcIds = [$costCenterId];
            } elseif ($scopeLevel === 'rao_consolidated' && $raoId) {
                $targetCcIds = CostCenter::where('regional_accounts_office_id', $raoId)->pluck('id')->toArray();
            }

            $ccTypeMap = CostCenter::pluck('trial_balance_type', 'id')->toArray();
            $accounts  = ChartOfAccount::leaf()->orderBy('account_code')->get();

            // Prior movements
            $priorQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate) {
                $q->where('status', 'posted')->where('date', '<', $fromDate);
            });
            if ($targetCcIds !== null) {
                $priorQuery->where(function ($q) use ($targetCcIds) {
                    $q->whereIn('cost_center_id', $targetCcIds)
                      ->orWhereHas('voucher', fn($v) => $v->whereIn('cost_center_id', $targetCcIds));
                });
            }
            $priorMovements = $priorQuery
                ->selectRaw('chart_of_account_id, SUM(debit_amount) as prior_dr, SUM(credit_amount) as prior_cr')
                ->groupBy('chart_of_account_id')
                ->get()
                ->keyBy('chart_of_account_id');

            // Period movements
            $periodQuery = VoucherEntry::whereHas('voucher', function ($q) use ($fromDate, $toDate) {
                $q->where('status', 'posted')->where('date', '>=', $fromDate)->where('date', '<=', $toDate);
            });
            if ($targetCcIds !== null) {
                $periodQuery->where(function ($q) use ($targetCcIds) {
                    $q->whereIn('cost_center_id', $targetCcIds)
                      ->orWhereHas('voucher', fn($v) => $v->whereIn('cost_center_id', $targetCcIds));
                });
            }
            $rawPeriodEntries = (clone $periodQuery)
                ->selectRaw('chart_of_account_id, COALESCE(cost_center_id, 0) as cc_id, SUM(debit_amount) as total_dr, SUM(credit_amount) as total_cr')
                ->groupBy('chart_of_account_id', 'cc_id')
                ->get();

            $movs = [];
            foreach ($rawPeriodEntries as $e) {
                $cId = $e->chart_of_account_id;
                $typ = $ccTypeMap[$e->cc_id] ?? 'overhead';
                $movs[$cId]['all']['dr'] = ($movs[$cId]['all']['dr'] ?? 0) + (float) $e->total_dr;
                $movs[$cId]['all']['cr'] = ($movs[$cId]['all']['cr'] ?? 0) + (float) $e->total_cr;
                $movs[$cId][$typ]['dr'] = ($movs[$cId][$typ]['dr'] ?? 0) + (float) $e->total_dr;
                $movs[$cId][$typ]['cr'] = ($movs[$cId][$typ]['cr'] ?? 0) + (float) $e->total_cr;
            }

            // Pre-calculate column totals to know which columns have figures
            $catTotals = ['gen_dr' => 0, 'gen_cr' => 0, 'dist_dr' => 0, 'dist_cr' => 0, 'ovh_dr' => 0, 'ovh_cr' => 0, 'dep_dr' => 0, 'dep_cr' => 0, 'dev_dr' => 0, 'dev_cr' => 0];
            foreach ($accounts as $acc) {
                $m = $movs[$acc->id] ?? [];
                $calcD = function ($dr, $cr) {
                    $d = $dr - $cr;
                    return ['dr' => $d > 0 ? $d : 0, 'cr' => $d < 0 ? abs($d) : 0];
                };
                $g = $calcD($m['revenue_generation']['dr'] ?? 0, $m['revenue_generation']['cr'] ?? 0);
                $di = $calcD($m['revenue_distribution']['dr'] ?? 0, $m['revenue_distribution']['cr'] ?? 0);
                $ov = $calcD($m['overhead']['dr'] ?? 0, $m['overhead']['cr'] ?? 0);
                $dp = $calcD($m['deposit_works']['dr'] ?? 0, $m['deposit_works']['cr'] ?? 0);
                $dv = $calcD($m['development']['dr'] ?? 0, $m['development']['cr'] ?? 0);

                $catTotals['gen_dr'] += $g['dr']; $catTotals['gen_cr'] += $g['cr'];
                $catTotals['dist_dr'] += $di['dr']; $catTotals['dist_cr'] += $di['cr'];
                $catTotals['ovh_dr'] += $ov['dr']; $catTotals['ovh_cr'] += $ov['cr'];
                $catTotals['dep_dr'] += $dp['dr']; $catTotals['dep_cr'] += $dp['cr'];
                $catTotals['dev_dr'] += $dv['dr']; $catTotals['dev_cr'] += $dv['cr'];
            }

            $hasGen  = ($catTotals['gen_dr'] > 0 || $catTotals['gen_cr'] > 0);
            $hasDist = ($catTotals['dist_dr'] > 0 || $catTotals['dist_cr'] > 0);
            $hasOvh  = ($catTotals['ovh_dr'] > 0 || $catTotals['ovh_cr'] > 0);
            $hasDep  = ($catTotals['dep_dr'] > 0 || $catTotals['dep_cr'] > 0);
            $hasDev  = ($catTotals['dev_dr'] > 0 || $catTotals['dev_cr'] > 0);

            if ($tbType !== 'all') {
                $hasGen  = ($tbType === 'revenue_generation' && $hasGen);
                $hasDist = ($tbType === 'revenue_distribution' && $hasDist);
                $hasOvh  = ($tbType === 'overhead' && $hasOvh);
                $hasDep  = ($tbType === 'deposit_works' && $hasDep);
                $hasDev  = ($tbType === 'development' && $hasDev);
            }

            // CSV Headers
            if ($viewFormat === 'matrix') {
                $headers = ['Code', 'Head of Accounts', 'Consolidated Dr', 'Consolidated Cr'];
                if ($hasGen)  { $headers[] = 'Revenue Generation Dr'; $headers[] = 'Revenue Generation Cr'; }
                if ($hasDist) { $headers[] = 'Revenue Distribution Dr'; $headers[] = 'Revenue Distribution Cr'; }
                if ($hasOvh)  { $headers[] = 'Overhead Dr'; $headers[] = 'Overhead Cr'; }
                if ($hasDep)  { $headers[] = 'Deposit Works Dr'; $headers[] = 'Deposit Works Cr'; }
                if ($hasDev)  { $headers[] = 'Development Dr'; $headers[] = 'Development Cr'; }
                fputcsv($handle, $headers);
            } else {
                fputcsv($handle, [
                    'Code',
                    'Head of Accounts',
                    'Opening Dr',
                    'Opening Cr',
                    'Period Dr',
                    'Period Cr',
                    'Closing Dr',
                    'Closing Cr',
                ]);
            }

            $totDr = 0; $totCr = 0;
            foreach ($accounts as $acc) {
                $isDebit = $acc->normal_balance === 'debit';
                $baseOp  = (float) $acc->opening_balance;
                $pDr     = (float) ($priorMovements[$acc->id]->prior_dr ?? 0);
                $pCr     = (float) ($priorMovements[$acc->id]->prior_cr ?? 0);
                $netP    = ($isDebit ? $baseOp : -$baseOp) + ($pDr - $pCr);
                $opDr    = $netP > 0 ? $netP : 0;
                $opCr    = $netP < 0 ? abs($netP) : 0;

                $tDr     = $movs[$acc->id]['all']['dr'] ?? 0;
                $tCr     = $movs[$acc->id]['all']['cr'] ?? 0;
                $net     = ($isDebit ? $baseOp : -$baseOp) + ($pDr - $pCr) + ($tDr - $tCr);
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

                    $row = [
                        $acc->account_code,
                        $acc->account_name ?: $acc->account_name_bn,
                        $clDr > 0 ? number_format($clDr, 2) : '',
                        $clCr > 0 ? number_format($clCr, 2) : '',
                    ];
                    if ($hasGen)  { $row[] = $gen['dr'] > 0 ? number_format($gen['dr'], 2) : ''; $row[] = $gen['cr'] > 0 ? number_format($gen['cr'], 2) : ''; }
                    if ($hasDist) { $row[] = $dist['dr'] > 0 ? number_format($dist['dr'], 2) : ''; $row[] = $dist['cr'] > 0 ? number_format($dist['cr'], 2) : ''; }
                    if ($hasOvh)  { $row[] = $ovh['dr'] > 0 ? number_format($ovh['dr'], 2) : ''; $row[] = $ovh['cr'] > 0 ? number_format($ovh['cr'], 2) : ''; }
                    if ($hasDep)  { $row[] = $dep['dr'] > 0 ? number_format($dep['dr'], 2) : ''; $row[] = $dep['cr'] > 0 ? number_format($dep['cr'], 2) : ''; }
                    if ($hasDev)  { $row[] = $dev['dr'] > 0 ? number_format($dev['dr'], 2) : ''; $row[] = $dev['cr'] > 0 ? number_format($dev['cr'], 2) : ''; }

                    fputcsv($handle, $row);
                } else {
                    fputcsv($handle, [
                        $acc->account_code,
                        $acc->account_name ?: $acc->account_name_bn,
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
            fputcsv($handle, ['Total:', '', number_format($totDr, 2), number_format($totCr, 2)]);

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
