@extends('layouts.app')

@section('title', 'Bank Reconciliation Statement | BPDB Accounts')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-handshake-simple text-success me-2"></i>Bank Reconciliation Statement
            </h4>
            <p class="text-muted small mb-0">
                Bangladesh Power Development Board — Cost Center & Bank Account Reconciliation
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($selectedBank)
                <a href="{{ route('reconciliation.export', [
                    'cost_center_id'  => $selectedCostCenter?->id,
                    'bank_account_id' => $selectedBank->id,
                    'from_date'       => $fromDate,
                    'to_date'         => $toDate
                ]) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i>Download CSV
                </a>
                <button type="button" onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-print me-1"></i>Print Statement
                </button>
            @endif
        </div>
    </div>

    <!-- Filter Card: Cost Center, Bank Account, Date Range -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 no-print">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('reconciliation.index') }}" id="recFilterForm">
                <div class="row g-3 align-items-end">
                    <!-- 1. Cost Center -->
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            <i class="fa-solid fa-sitemap text-primary me-1"></i>Cost Center (দপ্তর / প্রকল্প)
                        </label>
                        <select name="cost_center_id" class="form-select form-select-sm" id="costCenterSelect" onchange="this.form.submit()">
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" @selected($selectedCostCenter?->id == $cc->id)>
                                    [{{ $cc->code }}] {{ $cc->name_bn ?: $cc->name }} ({{ $cc->bankAccounts->count() }} Accounts)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Bank Account under selected Cost Center -->
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            <i class="fa-solid fa-building-columns text-info me-1"></i>Bank Account (ব্যাংক হিসাব)
                        </label>
                        <select name="bank_account_id" class="form-select form-select-sm" id="bankAccountSelect" onchange="this.form.submit()">
                            @forelse ($costCenterBankAccounts as $ba)
                                <option value="{{ $ba->id }}" @selected($selectedBank?->id == $ba->id)>
                                    {{ $ba->bank_name }} - {{ $ba->account_type }} ({{ $ba->account_number }})
                                </option>
                            @empty
                                <option value="">No Bank Accounts Found</option>
                            @endforelse
                        </select>
                    </div>

                    <!-- 3. From Date -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-dark mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>

                    <!-- 4. To Date -->
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-dark mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12 text-end mt-2">
                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4">
                            <i class="fa-solid fa-filter me-1"></i>Generate Statement
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedBank)
        <!-- BPDB Official Bank Reconciliation Statement -->
        <form method="POST" action="{{ route('reconciliation.store') }}" id="reconciliationForm">
            @csrf
            <input type="hidden" name="cost_center_id" value="{{ $selectedCostCenter?->id }}">
            <input type="hidden" name="bank_account_id" value="{{ $selectedBank->id }}">
            <input type="hidden" name="from_date" value="{{ $fromDate }}">
            <input type="hidden" name="to_date" value="{{ $toDate }}">
            <input type="hidden" name="cash_book_balance" id="inputCashBookBalance" value="{{ $cashBookBalance }}">

            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden" id="printableStatement">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- Document Header -->
                    <div class="text-center mb-4 pb-3 border-bottom border-2">
                        <h4 class="fw-bold text-dark text-uppercase mb-1 tracking-wide">BANGLADESH POWER DEVELOPMENT BOARD</h4>
                        <h6 class="text-secondary mb-1">
                            Unit Office : <strong>{{ $selectedCostCenter ? ($selectedCostCenter->name_bn ?: $selectedCostCenter->name) : 'BPDB Office' }}</strong>
                        </h6>
                        <h5 class="fw-bold text-success text-uppercase mb-3">Bank Reconciliation Statement</h5>
                        
                        <div class="row g-2 text-start small bg-light p-3 rounded-3 border">
                            <div class="col-md-6">
                                <div>Name of Month:- <strong>{{ \Carbon\Carbon::parse($toDate)->format("F 'y") }}</strong> ({{ \Carbon\Carbon::parse($fromDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d/m/Y') }})</div>
                                <div class="mt-1">Nature of Account :- <strong>{{ $selectedBank->account_title }}</strong></div>
                                <div class="mt-1">Accounts Code :- <strong>{{ $selectedCostCenter?->code ?? ($selectedBank->chartOfAccount?->account_code ?? '135') }}</strong></div>
                            </div>
                            <div class="col-md-6">
                                <div>Bank's Name : <strong>{{ $selectedBank->bank_name }}</strong></div>
                                <div class="mt-1">{{ $selectedBank->branch_name }}</div>
                                <div class="mt-1">Account No.- <strong>{{ $selectedBank->account_type }} - {{ $selectedBank->account_number }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Reconciliation Summary Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle mb-0" style="font-size: 14px;">
                            <tbody>
                                <tr class="bg-light fw-bold">
                                    <td style="width: 70%;" class="ps-3 py-2">Balance as per Cash Book</td>
                                    <td style="width: 30%;" class="text-end pe-3 py-2 fs-6 text-primary">
                                        ৳ <span id="displayCashBookBal">{{ number_format($cashBookBalance, 2) }}</span>
                                    </td>
                                </tr>

                                <!-- Section A: Add -->
                                <tr>
                                    <td class="fw-bold text-success ps-3 pt-3">A) Add :-</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">!) Cheque No. has been issued but not Presented to the Bank :-</td>
                                    <td class="text-end pe-3">৳ <span id="displayTotalA1">{{ number_format($totalA1, 2) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">!!) Deposited / Credited by Bank but not entered into the Cash Book :-</td>
                                    <td class="text-end pe-3">৳ <span id="displayTotalA2">{{ number_format($totalA2, 2) }}</span></td>
                                </tr>
                                <tr class="fw-bold bg-success bg-opacity-10">
                                    <td class="text-end pe-3 text-success">Total-Add</td>
                                    <td class="text-end pe-3 text-success">৳ <span id="displayTotalAdd">{{ number_format($totalAdd, 2) }}</span></td>
                                </tr>
                                <tr class="fw-bold table-light">
                                    <td class="ps-3">Subtotal (Cash Book Balance + Total-Add)</td>
                                    <td class="text-end pe-3">৳ <span id="displaySubtotal">{{ number_format($subtotal, 2) }}</span></td>
                                </tr>

                                <!-- Section B: Less -->
                                <tr>
                                    <td class="fw-bold text-danger ps-3 pt-3">B) Less :-</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">!) Cheque No. /Draft/Pay order deposited but not Credited by Bank :-</td>
                                    <td class="text-end pe-3">৳ <span id="displayTotalB1">{{ number_format($totalB1, 2) }}</span></td>
                                </tr>
                                <tr>
                                    <td class="ps-4">!!) Debited / Paid by the Bank but not entered into the Cash Book :-</td>
                                    <td class="text-end pe-3">৳ <span id="displayTotalB2">{{ number_format($totalB2, 2) }}</span></td>
                                </tr>
                                <tr class="fw-bold bg-danger bg-opacity-10">
                                    <td class="text-end pe-3 text-danger">Total-Less</td>
                                    <td class="text-end pe-3 text-danger">৳ <span id="displayTotalLess">{{ number_format($totalLess, 2) }}</span></td>
                                </tr>

                                <!-- Final Reconciled Balance -->
                                <tr class="table-primary border-2 fw-bold fs-6">
                                    <td class="ps-3 py-3 text-primary">Reconciliation Balance as per Bank Statement</td>
                                    <td class="text-end pe-3 py-3 text-primary">
                                        ৳ <span id="displayReconciledBal">{{ number_format($reconciledBalance, 2) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Statement Input & Match Indicator (Screen Only) -->
                    <div class="row g-3 align-items-center bg-light p-3 rounded-4 border mb-5 no-print">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-dark mb-1">
                                Bank Statement Ending Balance (ব্যাংক বিবরণীর ব্যালেন্স) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white">৳</span>
                                <input type="number" step="0.01" name="bank_statement_balance" id="inputBankStatementBal" 
                                       class="form-control fw-bold fs-6 text-dark" 
                                       value="{{ $bankStatementBalance }}" oninput="recalc()">
                            </div>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold text-muted mb-1">Reconciliation Status / ব্যালেন্স অমিল যাচাই:</label>
                            <div id="reconciliationStatusBadge">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Schedules Section -->
                    <div class="mt-4">
                        <h5 class="fw-bold text-dark mb-3 pb-2 border-bottom">
                            <i class="fa-solid fa-list-check text-success me-2"></i>Detailed Schedules (সমন্বয়ের বিস্তারিত এন্ট্রি)
                        </h5>

                        <!-- Schedule A-1 -->
                        <div class="card border border-success border-opacity-25 rounded-3 mb-4 overflow-hidden">
                            <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
                                <strong class="small text-success">
                                    A--( ! ) Cheque No. has been issued but not Presented to the Bank :-
                                </strong>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-0 no-print" onclick="addRow('A1')">
                                    <i class="fa-solid fa-plus me-1"></i>Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" id="tableA1" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                            <th style="width: 25%;">Date</th>
                                            <th style="width: 30%; text-align: right;">Amount in Taka</th>
                                            <th style="width: 10%; text-align: center;" class="no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyA1">
                                        @forelse ($scheduleItems['A1'] as $idx => $it)
                                            <tr>
                                                <td><input type="text" name="items[{{ $idx }}][reference_no]" class="form-control form-control-sm" value="{{ $it['reference_no'] }}" placeholder="Cheque No."></td>
                                                <td><input type="date" name="items[{{ $idx }}][item_date]" class="form-control form-control-sm" value="{{ $it['item_date'] }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[{{ $idx }}][amount]" class="form-control form-control-sm text-end input-amt-a1" value="{{ $it['amount'] }}" oninput="recalc()">
                                                    <input type="hidden" name="items[{{ $idx }}][schedule]" value="A1">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td><input type="text" name="items[0][reference_no]" class="form-control form-control-sm" placeholder="Cheque No.--"></td>
                                                <td><input type="date" name="items[0][item_date]" class="form-control form-control-sm" value="{{ $toDate }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[0][amount]" class="form-control form-control-sm text-end input-amt-a1" value="0.00" oninput="recalc()">
                                                    <input type="hidden" name="items[0][schedule]" value="A1">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="2" class="text-end">Total--A--(!):</td>
                                            <td class="text-end pe-2">৳ <span id="subtotalA1">0.00</span></td>
                                            <td class="no-print"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Schedule A-2 -->
                        <div class="card border border-success border-opacity-25 rounded-3 mb-4 overflow-hidden">
                            <div class="card-header bg-success bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
                                <strong class="small text-success">
                                    A--(!!) Deposited / Credited by Bank but not entered into the Cash Book :-
                                </strong>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-0 no-print" onclick="addRow('A2')">
                                    <i class="fa-solid fa-plus me-1"></i>Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" id="tableA2" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                            <th style="width: 25%;">Date</th>
                                            <th style="width: 30%; text-align: right;">Amount in Taka</th>
                                            <th style="width: 10%; text-align: center;" class="no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyA2">
                                        @forelse ($scheduleItems['A2'] as $idx => $it)
                                            <tr>
                                                <td><input type="text" name="items[a2_{{ $idx }}][reference_no]" class="form-control form-control-sm" value="{{ $it['reference_no'] }}" placeholder="Credit memo / Interest"></td>
                                                <td><input type="date" name="items[a2_{{ $idx }}][item_date]" class="form-control form-control-sm" value="{{ $it['item_date'] }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[a2_{{ $idx }}][amount]" class="form-control form-control-sm text-end input-amt-a2" value="{{ $it['amount'] }}" oninput="recalc()">
                                                    <input type="hidden" name="items[a2_{{ $idx }}][schedule]" value="A2">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td><input type="text" name="items[a2_0][reference_no]" class="form-control form-control-sm" placeholder="Bank Interest / Credit Memo"></td>
                                                <td><input type="date" name="items[a2_0][item_date]" class="form-control form-control-sm" value="{{ $toDate }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[a2_0][amount]" class="form-control form-control-sm text-end input-amt-a2" value="0.00" oninput="recalc()">
                                                    <input type="hidden" name="items[a2_0][schedule]" value="A2">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="2" class="text-end">A--(!!) Total :-:</td>
                                            <td class="text-end pe-2">৳ <span id="subtotalA2">0.00</span></td>
                                            <td class="no-print"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Schedule B-1 -->
                        <div class="card border border-danger border-opacity-25 rounded-3 mb-4 overflow-hidden">
                            <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
                                <strong class="small text-danger">
                                    B--(!) Cheque No. /Draft/Pay order deposited but not Credited by Bank :-
                                </strong>
                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-0 no-print" onclick="addRow('B1')">
                                    <i class="fa-solid fa-plus me-1"></i>Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" id="tableB1" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                            <th style="width: 25%;">Date</th>
                                            <th style="width: 30%; text-align: right;">Amount in Taka</th>
                                            <th style="width: 10%; text-align: center;" class="no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyB1">
                                        @forelse ($scheduleItems['B1'] as $idx => $it)
                                            <tr>
                                                <td><input type="text" name="items[b1_{{ $idx }}][reference_no]" class="form-control form-control-sm" value="{{ $it['reference_no'] }}" placeholder="Deposit in transit"></td>
                                                <td><input type="date" name="items[b1_{{ $idx }}][item_date]" class="form-control form-control-sm" value="{{ $it['item_date'] }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[b1_{{ $idx }}][amount]" class="form-control form-control-sm text-end input-amt-b1" value="{{ $it['amount'] }}" oninput="recalc()">
                                                    <input type="hidden" name="items[b1_{{ $idx }}][schedule]" value="B1">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td><input type="text" name="items[b1_0][reference_no]" class="form-control form-control-sm" placeholder="Deposit in transit"></td>
                                                <td><input type="date" name="items[b1_0][item_date]" class="form-control form-control-sm" value="{{ $toDate }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[b1_0][amount]" class="form-control form-control-sm text-end input-amt-b1" value="0.00" oninput="recalc()">
                                                    <input type="hidden" name="items[b1_0][schedule]" value="B1">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="2" class="text-end">Total (B--!):</td>
                                            <td class="text-end pe-2">৳ <span id="subtotalB1">0.00</span></td>
                                            <td class="no-print"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Schedule B-2 -->
                        <div class="card border border-danger border-opacity-25 rounded-3 mb-4 overflow-hidden">
                            <div class="card-header bg-danger bg-opacity-10 d-flex justify-content-between align-items-center py-2 px-3">
                                <strong class="small text-danger">
                                    B--(!!) Debited / Paid by the Bank but not entered into the Cash Book :-
                                </strong>
                                <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 py-0 no-print" onclick="addRow('B2')">
                                    <i class="fa-solid fa-plus me-1"></i>Add Row
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0" id="tableB2" style="font-size: 13px;">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 35%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                            <th style="width: 25%;">Date</th>
                                            <th style="width: 30%; text-align: right;">Amount in Taka</th>
                                            <th style="width: 10%; text-align: center;" class="no-print">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyB2">
                                        @forelse ($scheduleItems['B2'] as $idx => $it)
                                            <tr>
                                                <td><input type="text" name="items[b2_{{ $idx }}][reference_no]" class="form-control form-control-sm" value="{{ $it['reference_no'] }}" placeholder="As per bank statement"></td>
                                                <td><input type="date" name="items[b2_{{ $idx }}][item_date]" class="form-control form-control-sm" value="{{ $it['item_date'] }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[b2_{{ $idx }}][amount]" class="form-control form-control-sm text-end input-amt-b2" value="{{ $it['amount'] }}" oninput="recalc()">
                                                    <input type="hidden" name="items[b2_{{ $idx }}][schedule]" value="B2">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td><input type="text" name="items[b2_0][reference_no]" class="form-control form-control-sm" placeholder="As per bank statement / Bank Charges"></td>
                                                <td><input type="date" name="items[b2_0][item_date]" class="form-control form-control-sm" value="{{ $toDate }}"></td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[b2_0][amount]" class="form-control form-control-sm text-end input-amt-b2" value="0.00" oninput="recalc()">
                                                    <input type="hidden" name="items[b2_0][schedule]" value="B2">
                                                </td>
                                                <td class="text-center no-print">
                                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="2" class="text-end">Total (B--!!):</td>
                                            <td class="text-end pe-2">৳ <span id="subtotalB2">0.00</span></td>
                                            <td class="no-print"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Signatures Section -->
                    <div class="row pt-5 mt-5 border-top">
                        <div class="col-6 text-start">
                            <div class="fw-bold mb-4">__________________________</div>
                            <div class="fw-bold text-dark">Prepared by</div>
                            <div class="small text-muted">{{ auth()->user()->designation ?: 'Assistant Accountant / Cashier' }}</div>
                            <div class="small text-muted">{{ $selectedCostCenter ? ($selectedCostCenter->name_bn ?: $selectedCostCenter->name) : 'BPDB Office' }}</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="fw-bold mb-4">__________________________</div>
                            <div class="fw-bold text-dark">Assistant Director (Accounts)</div>
                            <div class="small text-muted">Accounts Officer</div>
                            <div class="small text-muted">{{ $selectedCostCenter ? ($selectedCostCenter->name_bn ?: $selectedCostCenter->name) : 'BPDB Office' }}</div>
                        </div>
                    </div>

                </div>

                <!-- Footer Save Action -->
                <div class="card-footer bg-light p-3 text-end no-print">
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Save Reconciliation Statement
                    </button>
                </div>
            </div>
        </form>
    @else
        <div class="alert alert-warning rounded-4 shadow-sm">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>Please select a Cost Center that has associated Bank Accounts to generate the statement.
        </div>
    @endif

    <!-- Saved Reconciliations History Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4 no-print">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Saved Reconciliation History (সংরক্ষিত ব্যাংক সমন্বয় বিবরণীসমূহ)
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 22%;">Bank & Account</th>
                        <th style="width: 20%;">Cost Center</th>
                        <th style="width: 14%;">Period / Statement Date</th>
                        <th style="width: 14%; text-align: right;">Cash Book (৳)</th>
                        <th style="width: 14%; text-align: right;">Bank Stmt (৳)</th>
                        <th style="width: 8%; text-align: center;">Status</th>
                        <th style="width: 8%; text-align: end;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reconciliations as $r)
                        <tr>
                            <td>
                                <strong class="text-dark d-block">{{ $r->bankAccount?->bank_name }}</strong>
                                <small class="text-muted">{{ $r->bankAccount?->account_type }} - {{ $r->bankAccount?->account_number }}</small>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">{{ $r->costCenter?->name_bn ?: ($r->costCenter?->name ?? 'BPDB') }}</div>
                                <small class="text-muted">Code: {{ $r->costCenter?->code }}</small>
                            </td>
                            <td>
                                <span class="small fw-semibold text-dark">{{ $r->to_date ? $r->to_date->format('d/m/Y') : $r->statement_date->format('d/m/Y') }}</span>
                                @if ($r->from_date)
                                    <small class="text-muted d-block">From: {{ $r->from_date->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-primary">৳ {{ number_format($r->cash_book_balance, 2) }}</td>
                            <td class="text-end fw-semibold">৳ {{ number_format($r->bank_statement_balance, 2) }}</td>
                            <td class="text-center">
                                @if ($r->status === 'reconciled')
                                    <span class="badge bg-success rounded-pill px-2">Matched</span>
                                @else
                                    <span class="badge bg-warning text-dark rounded-pill px-2">Draft</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('reconciliation.index', [
                                    'cost_center_id'  => $r->cost_center_id,
                                    'bank_account_id' => $r->bank_account_id,
                                    'from_date'       => $r->from_date ? $r->from_date->format('Y-m-d') : $r->statement_date->format('Y-m-01'),
                                    'to_date'         => $r->to_date ? $r->to_date->format('Y-m-d') : $r->statement_date->format('Y-m-d'),
                                ]) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Load / Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No saved reconciliation statements found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reconciliations->hasPages())
            <div class="card-footer bg-white border-0 py-2">
                {{ $reconciliations->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<script>
let rowCounter = 100;

function addRow(schedule) {
    rowCounter++;
    const tbody = document.getElementById('tbody' + schedule);
    const tr = document.createElement('tr');
    
    tr.innerHTML = `
        <td><input type="text" name="items[${rowCounter}][reference_no]" class="form-control form-control-sm" placeholder="Ref / Cheque No."></td>
        <td><input type="date" name="items[${rowCounter}][item_date]" class="form-control form-control-sm" value="{{ $toDate }}"></td>
        <td>
            <input type="number" step="0.01" name="items[${rowCounter}][amount]" class="form-control form-control-sm text-end input-amt-${schedule.toLowerCase()}" value="0.00" oninput="recalc()">
            <input type="hidden" name="items[${rowCounter}][schedule]" value="${schedule}">
        </td>
        <td class="text-center no-print">
            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeRow(this)"><i class="fa-solid fa-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);
    recalc();
}

function removeRow(btn) {
    const row = btn.closest('tr');
    if (row) {
        row.remove();
        recalc();
    }
}

function getSum(className) {
    let sum = 0;
    document.querySelectorAll('.' + className).forEach(function(input) {
        const val = parseFloat(input.value) || 0;
        sum += val;
    });
    return sum;
}

function fmt(num) {
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalc() {
    const cashBookBal = parseFloat(document.getElementById('inputCashBookBalance').value) || 0;

    const totA1 = getSum('input-amt-a1');
    const totA2 = getSum('input-amt-a2');
    const totAdd = totA1 + totA2;
    const subtotal = cashBookBal + totAdd;

    const totB1 = getSum('input-amt-b1');
    const totB2 = getSum('input-amt-b2');
    const totLess = totB1 + totB2;

    const reconciledBal = subtotal - totLess;
    const bankStatementBal = parseFloat(document.getElementById('inputBankStatementBal').value) || 0;
    const diff = Math.abs(bankStatementBal - reconciledBal);

    // Update displays
    document.getElementById('subtotalA1').innerText = fmt(totA1);
    document.getElementById('displayTotalA1').innerText = fmt(totA1);

    document.getElementById('subtotalA2').innerText = fmt(totA2);
    document.getElementById('displayTotalA2').innerText = fmt(totA2);

    document.getElementById('displayTotalAdd').innerText = fmt(totAdd);
    document.getElementById('displaySubtotal').innerText = fmt(subtotal);

    document.getElementById('subtotalB1').innerText = fmt(totB1);
    document.getElementById('displayTotalB1').innerText = fmt(totB1);

    document.getElementById('subtotalB2').innerText = fmt(totB2);
    document.getElementById('displayTotalB2').innerText = fmt(totB2);

    document.getElementById('displayTotalLess').innerText = fmt(totLess);
    document.getElementById('displayReconciledBal').innerText = fmt(reconciledBal);

    // Update status badge
    const badgeEl = document.getElementById('reconciliationStatusBadge');
    if (diff < 0.01) {
        badgeEl.innerHTML = `
            <span class="badge bg-success py-2 px-3 fs-6 rounded-pill">
                <i class="fa-solid fa-circle-check me-1"></i>Perfect Match! (Balanced: ৳ ${fmt(reconciledBal)})
            </span>
        `;
    } else {
        badgeEl.innerHTML = `
            <span class="badge bg-danger py-2 px-3 fs-6 rounded-pill">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>Difference: ৳ ${fmt(diff)} (Bank: ৳ ${fmt(bankStatementBal)} vs Reconciled: ৳ ${fmt(reconciledBal)})
            </span>
        `;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    recalc();
});
</script>
@endsection
