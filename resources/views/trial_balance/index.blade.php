@extends('layouts.app')

@section('title', 'Trial Balance | BPDB Accounts')

@section('content')
<div class="container-fluid px-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-scale-balanced text-success me-2"></i>Trial Balance
                </h4>
                @if ($scopeLevel === 'grand_consolidated')
                    <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-layer-group me-1"></i>Grand Consolidated (All RAOs)
                    </span>
                @elseif ($scopeLevel === 'rao_consolidated' && $selectedRao)
                    <span class="badge bg-info text-dark px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-building-shield me-1"></i>RAO: [{{ $selectedRao->code }}] {{ $selectedRao->name }}
                    </span>
                @elseif ($scopeLevel === 'cost_center' && $selectedCostCenter)
                    <span class="badge bg-success px-3 py-2 rounded-pill shadow-sm">
                        <i class="fa-solid fa-sitemap me-1"></i>Cost Center: [{{ $selectedCostCenter->code }}] {{ $selectedCostCenter->name }}
                    </span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Bangladesh Power Development Board (BPDB) — 
                <span>From: <strong>{{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }}</strong></span>
                <span>To: <strong>{{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</strong></span>
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0 no-print">
            <a href="{{ route('trial_balance.export', request()->all()) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i>Download Excel / CSV
            </a>
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i>Print
            </button>
        </div>
    </div>

    <!-- Filter & Scope Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white no-print">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('trial_balance.index') }}" id="tbFilterForm">
                <!-- Scope Pills -->
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pb-3 border-bottom">
                    <span class="small fw-bold text-muted text-uppercase me-2">Scope:</span>
                    
                    <input type="radio" class="btn-check" name="scope_level" id="scope_grand" value="grand_consolidated" autocomplete="off" @checked($scopeLevel === 'grand_consolidated') onchange="updateScopeFields()">
                    <label class="btn btn-sm btn-outline-primary rounded-pill px-3" for="scope_grand">
                        <i class="fa-solid fa-globe me-1"></i>Grand Consolidated (All RAOs)
                    </label>

                    <input type="radio" class="btn-check" name="scope_level" id="scope_rao" value="rao_consolidated" autocomplete="off" @checked($scopeLevel === 'rao_consolidated') onchange="updateScopeFields()">
                    <label class="btn btn-sm btn-outline-info rounded-pill px-3" for="scope_rao">
                        <i class="fa-solid fa-building-shield me-1"></i>RAO Consolidated
                    </label>

                    <input type="radio" class="btn-check" name="scope_level" id="scope_cc" value="cost_center" autocomplete="off" @checked($scopeLevel === 'cost_center') onchange="updateScopeFields()">
                    <label class="btn btn-sm btn-outline-success rounded-pill px-3" for="scope_cc">
                        <i class="fa-solid fa-sitemap me-1"></i>Single Cost Center
                    </label>
                </div>

                <div class="row g-3 align-items-end">
                    <!-- RAO Select -->
                    <div class="col-12 col-md-3" id="raoFieldCol" style="{{ $scopeLevel === 'rao_consolidated' ? '' : 'display:none;' }}">
                        <label class="form-label small fw-semibold text-dark mb-1">Select RAO</label>
                        <select name="rao_id" class="form-select form-select-sm" id="raoSelect">
                            <option value="">-- Select RAO --</option>
                            @foreach ($raos as $r)
                                <option value="{{ $r->id }}" @selected($raoId == $r->id)>
                                    [{{ $r->code }}] {{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cost Center Select -->
                    <div class="col-12 col-md-3" id="ccFieldCol" style="{{ $scopeLevel === 'cost_center' ? '' : 'display:none;' }}">
                        <label class="form-label small fw-semibold text-dark mb-1">Select Cost Center</label>
                        <select name="cost_center_id" class="form-select form-select-sm" id="ccSelect">
                            <option value="">-- Select Cost Center --</option>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" @selected($costCenterId == $cc->id)>
                                    [{{ $cc->code }}] {{ $cc->name }} ({{ \App\Models\CostCenter::TB_TYPES[$cc->trial_balance_type]['name_en'] ?? $cc->trial_balance_type }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Trial Balance Type -->
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Trial Balance Type</label>
                        <select name="tb_type" class="form-select form-select-sm">
                            <option value="all" @selected($tbType === 'all')>-- All Categories Consolidated --</option>
                            <option value="revenue_generation" @selected($tbType === 'revenue_generation')>Revenue Generation</option>
                            <option value="revenue_distribution" @selected($tbType === 'revenue_distribution')>Revenue Distribution</option>
                            <option value="overhead" @selected($tbType === 'overhead')>Overhead</option>
                            <option value="deposit_works" @selected($tbType === 'deposit_works')>Deposit Works</option>
                            <option value="development" @selected($tbType === 'development')>Development</option>
                        </select>
                    </div>

                    <!-- View Format Toggle -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-dark mb-1">View Format</label>
                        <select name="view_format" class="form-select form-select-sm">
                            <option value="matrix" @selected($viewFormat === 'matrix')>Format A-1 (Matrix)</option>
                            <option value="standard" @selected($viewFormat === 'standard')>Standard (3-Stage)</option>
                        </select>
                    </div>

                    <!-- Date Range: From Date -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-dark mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>

                    <!-- Date Range: To Date -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-dark mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>

                    <!-- Submit & Reset -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-sm flex-fill rounded-pill">
                            <i class="fa-solid fa-filter me-1"></i>Filter
                        </button>
                        <a href="{{ route('trial_balance.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill" title="Reset Filter">
                            <i class="fa-solid fa-rotate-right"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Status Alert -->
    @if ($isBalanced)
        <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0 p-3">
            <i class="fa-solid fa-circle-check fs-3 me-3 text-success"></i>
            <div class="flex-grow-1">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <strong class="d-block text-success fs-6">Trial Balance is Balanced! (Total Debit = Total Credit)</strong>
                        <small class="text-muted">
                            Total Debit: ৳ {{ number_format($totals['consolidated_dr'], 2) }} | 
                            Total Credit: ৳ {{ number_format($totals['consolidated_cr'], 2) }}
                        </small>
                    </div>
                    <span class="badge bg-success rounded-pill px-3 py-2 mt-2 mt-md-0">
                        <i class="fa-solid fa-check-double me-1"></i>Balanced
                    </span>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0 p-3">
            <i class="fa-solid fa-triangle-exclamation fs-3 me-3 text-danger"></i>
            <div class="flex-grow-1">
                <strong class="d-block text-danger fs-6">Trial Balance Mismatch! (Debit != Credit)</strong>
                <small>
                    Difference: ৳ {{ number_format(abs($totals['consolidated_dr'] - $totals['consolidated_cr']), 2) }} 
                    (Total Debit: ৳ {{ number_format($totals['consolidated_dr'], 2) }} vs Total Credit: ৳ {{ number_format($totals['consolidated_cr'], 2) }})
                </small>
            </div>
        </div>
    @endif

    <!-- Report Printing Title (Print Only) -->
    <div class="d-none d-print-block text-center mb-4">
        <h4 class="fw-bold mb-1">Bangladesh Power Development Board (BPDB)</h4>
        <h5 class="fw-semibold mb-1">
            @if ($scopeLevel === 'grand_consolidated')
                Grand Consolidated Trial Balance (Format A-1)
            @elseif ($scopeLevel === 'rao_consolidated' && $selectedRao)
                RAO: [{{ $selectedRao->code }}] {{ $selectedRao->name }} — Consolidated Trial Balance
            @elseif ($scopeLevel === 'cost_center' && $selectedCostCenter)
                Cost Center: [{{ $selectedCostCenter->code }}] {{ $selectedCostCenter->name }} — Trial Balance
            @endif
        </h5>
        <p class="small text-muted mb-0">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d-M-Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d-M-Y') }}</p>
    </div>

    <!-- Trial Balance Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            @if ($viewFormat === 'matrix')
                <!-- Format A-1 Matrix View (Only active categories with non-zero figures are shown) -->
                <table class="table table-hover table-bordered align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light align-middle text-center border-bottom-2">
                        <tr>
                            <th rowspan="2" style="width: 8%;" class="bg-light">Code</th>
                            <th rowspan="2" style="width: 24%;" class="text-start bg-light">Head of Accounts</th>
                            
                            <!-- Consolidated (Always Shown) -->
                            <th colspan="2" class="bg-primary bg-opacity-10 text-primary border-start border-end fw-bold">
                                Consolidated
                            </th>

                            <!-- Revenue Generation (Shown only if figures exist) -->
                            @if ($hasGeneration)
                                <th colspan="2" class="bg-success bg-opacity-10 text-success border-end fw-bold">
                                    Revenue Generation
                                </th>
                            @endif

                            <!-- Revenue Distribution (Shown only if figures exist) -->
                            @if ($hasDistribution)
                                <th colspan="2" class="bg-warning bg-opacity-10 text-dark border-end fw-bold">
                                    Revenue Distribution
                                </th>
                            @endif

                            <!-- Overhead (Shown only if figures exist) -->
                            @if ($hasOverhead)
                                <th colspan="2" class="bg-secondary bg-opacity-10 text-secondary border-end fw-bold">
                                    Overhead
                                </th>
                            @endif

                            <!-- Deposit Works (Shown only if figures exist) -->
                            @if ($hasDeposit)
                                <th colspan="2" class="bg-info bg-opacity-10 text-info border-end fw-bold">
                                    Deposit Works
                                </th>
                            @endif

                            <!-- Development (Shown only if figures exist) -->
                            @if ($hasDevelopment)
                                <th colspan="2" class="bg-danger bg-opacity-10 text-danger fw-bold">
                                    Development
                                </th>
                            @endif
                        </tr>
                        <tr>
                            <!-- Consolidated -->
                            <th class="text-end bg-primary bg-opacity-10 text-primary border-start" style="width: 8%;">Debit</th>
                            <th class="text-end bg-primary bg-opacity-10 text-primary border-end" style="width: 8%;">Credit</th>

                            <!-- Revenue Generation -->
                            @if ($hasGeneration)
                                <th class="text-end bg-success bg-opacity-10 text-success" style="width: 8%;">Debit</th>
                                <th class="text-end bg-success bg-opacity-10 text-success border-end" style="width: 8%;">Credit</th>
                            @endif

                            <!-- Revenue Distribution -->
                            @if ($hasDistribution)
                                <th class="text-end bg-warning bg-opacity-10 text-dark" style="width: 8%;">Debit</th>
                                <th class="text-end bg-warning bg-opacity-10 text-dark border-end" style="width: 8%;">Credit</th>
                            @endif

                            <!-- Overhead -->
                            @if ($hasOverhead)
                                <th class="text-end bg-secondary bg-opacity-10 text-secondary" style="width: 8%;">Debit</th>
                                <th class="text-end bg-secondary bg-opacity-10 text-secondary border-end" style="width: 8%;">Credit</th>
                            @endif

                            <!-- Deposit Works -->
                            @if ($hasDeposit)
                                <th class="text-end bg-info bg-opacity-10 text-info" style="width: 8%;">Debit</th>
                                <th class="text-end bg-info bg-opacity-10 text-info border-end" style="width: 8%;">Credit</th>
                            @endif

                            <!-- Development -->
                            @if ($hasDevelopment)
                                <th class="text-end bg-danger bg-opacity-10 text-danger" style="width: 8%;">Debit</th>
                                <th class="text-end bg-danger bg-opacity-10 text-danger" style="width: 8%;">Credit</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td class="fw-bold text-center">{{ $r['code'] }}</td>
                                <td>
                                    <a href="{{ route('ledger.index', ['chart_of_account_id' => \App\Models\ChartOfAccount::where('account_code', $r['code'])->value('id'), 'cost_center_id' => $costCenterId]) }}" class="text-decoration-none text-dark fw-semibold">
                                        {{ $r['name'] }}
                                    </a>
                                </td>
                                
                                <!-- Consolidated -->
                                <td class="text-end fw-semibold text-primary border-start">{{ $r['closing_dr'] > 0 ? number_format($r['closing_dr'], 2) : '-' }}</td>
                                <td class="text-end fw-semibold text-primary border-end">{{ $r['closing_cr'] > 0 ? number_format($r['closing_cr'], 2) : '-' }}</td>

                                <!-- Revenue Generation -->
                                @if ($hasGeneration)
                                    <td class="text-end">{{ $r['generation_dr'] > 0 ? number_format($r['generation_dr'], 2) : '-' }}</td>
                                    <td class="text-end border-end">{{ $r['generation_cr'] > 0 ? number_format($r['generation_cr'], 2) : '-' }}</td>
                                @endif

                                <!-- Revenue Distribution -->
                                @if ($hasDistribution)
                                    <td class="text-end">{{ $r['distribution_dr'] > 0 ? number_format($r['distribution_dr'], 2) : '-' }}</td>
                                    <td class="text-end border-end">{{ $r['distribution_cr'] > 0 ? number_format($r['distribution_cr'], 2) : '-' }}</td>
                                @endif

                                <!-- Overhead -->
                                @if ($hasOverhead)
                                    <td class="text-end">{{ $r['overhead_dr'] > 0 ? number_format($r['overhead_dr'], 2) : '-' }}</td>
                                    <td class="text-end border-end">{{ $r['overhead_cr'] > 0 ? number_format($r['overhead_cr'], 2) : '-' }}</td>
                                @endif

                                <!-- Deposit Works -->
                                @if ($hasDeposit)
                                    <td class="text-end">{{ $r['deposit_dr'] > 0 ? number_format($r['deposit_dr'], 2) : '-' }}</td>
                                    <td class="text-end border-end">{{ $r['deposit_cr'] > 0 ? number_format($r['deposit_cr'], 2) : '-' }}</td>
                                @endif

                                <!-- Development -->
                                @if ($hasDevelopment)
                                    <td class="text-end">{{ $r['development_dr'] > 0 ? number_format($r['development_dr'], 2) : '-' }}</td>
                                    <td class="text-end">{{ $r['development_cr'] > 0 ? number_format($r['development_cr'], 2) : '-' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fs-2 text-secondary mb-2 d-block"></i>
                                    No trial balance records found for the selected scope and date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light border-top-2 fw-bold text-end">
                        <tr class="fs-6">
                            <td colspan="2" class="text-center text-dark">Total:</td>
                            <td class="text-primary border-start">৳ {{ number_format($totals['consolidated_dr'], 2) }}</td>
                            <td class="text-primary border-end">৳ {{ number_format($totals['consolidated_cr'], 2) }}</td>
                            
                            @if ($hasGeneration)
                                <td>৳ {{ number_format($totals['generation_dr'], 2) }}</td>
                                <td class="border-end">৳ {{ number_format($totals['generation_cr'], 2) }}</td>
                            @endif

                            @if ($hasDistribution)
                                <td>৳ {{ number_format($totals['distribution_dr'], 2) }}</td>
                                <td class="border-end">৳ {{ number_format($totals['distribution_cr'], 2) }}</td>
                            @endif

                            @if ($hasOverhead)
                                <td>৳ {{ number_format($totals['overhead_dr'], 2) }}</td>
                                <td class="border-end">৳ {{ number_format($totals['overhead_cr'], 2) }}</td>
                            @endif

                            @if ($hasDeposit)
                                <td>৳ {{ number_format($totals['deposit_dr'], 2) }}</td>
                                <td class="border-end">৳ {{ number_format($totals['deposit_cr'], 2) }}</td>
                            @endif

                            @if ($hasDevelopment)
                                <td>৳ {{ number_format($totals['development_dr'], 2) }}</td>
                                <td>৳ {{ number_format($totals['development_cr'], 2) }}</td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            @else
                <!-- Standard 3-stage View -->
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead class="table-light align-middle">
                        <tr>
                            <th rowspan="2" style="width: 10%;">Code</th>
                            <th rowspan="2" style="width: 30%;">Head of Accounts</th>
                            <th rowspan="2" style="width: 10%;">Type</th>
                            <th colspan="2" class="text-center bg-light border-bottom">Opening Balance</th>
                            <th colspan="2" class="text-center bg-light border-bottom">Period Transactions</th>
                            <th colspan="2" class="text-center bg-light border-bottom">Closing Balance</th>
                        </tr>
                        <tr>
                            <th class="text-end" style="width: 8%;">Debit</th>
                            <th class="text-end" style="width: 8%;">Credit</th>
                            <th class="text-end" style="width: 8%;">Debit</th>
                            <th class="text-end" style="width: 8%;">Credit</th>
                            <th class="text-end" style="width: 9%;">Debit</th>
                            <th class="text-end" style="width: 9%;">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $r)
                            <tr>
                                <td class="fw-bold">{{ $r['code'] }}</td>
                                <td>
                                    <a href="{{ route('ledger.index', ['chart_of_account_id' => \App\Models\ChartOfAccount::where('account_code', $r['code'])->value('id'), 'cost_center_id' => $costCenterId]) }}" class="text-decoration-none text-dark fw-medium">
                                        {{ $r['name'] }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ strtoupper($r['type']) }}</span>
                                </td>
                                <td class="text-end">{{ $r['opening_dr'] > 0 ? number_format($r['opening_dr'], 2) : '-' }}</td>
                                <td class="text-end">{{ $r['opening_cr'] > 0 ? number_format($r['opening_cr'], 2) : '-' }}</td>
                                <td class="text-end text-success">{{ $r['period_dr'] > 0 ? number_format($r['period_dr'], 2) : '-' }}</td>
                                <td class="text-end text-primary">{{ $r['period_cr'] > 0 ? number_format($r['period_cr'], 2) : '-' }}</td>
                                <td class="text-end fw-bold text-success">{{ $r['closing_dr'] > 0 ? number_format($r['closing_dr'], 2) : '-' }}</td>
                                <td class="text-end fw-bold text-primary">{{ $r['closing_cr'] > 0 ? number_format($r['closing_cr'], 2) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    No records found for the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light border-top border-2">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total:</td>
                            <td class="text-end">৳ {{ number_format($totals['opening_dr'], 2) }}</td>
                            <td class="text-end">৳ {{ number_format($totals['opening_cr'], 2) }}</td>
                            <td class="text-end text-success">৳ {{ number_format($totals['period_dr'], 2) }}</td>
                            <td class="text-end text-primary">৳ {{ number_format($totals['period_cr'], 2) }}</td>
                            <td class="text-end text-success fs-6">৳ {{ number_format($totals['closing_dr'], 2) }}</td>
                            <td class="text-end text-primary fs-6">৳ {{ number_format($totals['closing_cr'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
</div>

<script>
function updateScopeFields() {
    const isGrand = document.getElementById('scope_grand').checked;
    const isRao   = document.getElementById('scope_rao').checked;
    const isCc    = document.getElementById('scope_cc').checked;

    const raoCol = document.getElementById('raoFieldCol');
    const ccCol  = document.getElementById('ccFieldCol');

    if (raoCol) raoCol.style.display = isRao ? 'block' : 'none';
    if (ccCol) ccCol.style.display   = isCc ? 'block' : 'none';
}
</script>
@endsection
