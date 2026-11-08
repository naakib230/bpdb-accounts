@extends('layouts.app')

@section('title', 'Bank Reconciliation Statement | BPDB Accounts')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 no-print">
        <div>
            <a href="{{ route('reconciliation.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mb-2">
                <i class="fa-solid fa-arrow-left me-1"></i>Back to Reconciliation Form
            </a>
            <h4 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-handshake-simple text-success me-2"></i>Bank Reconciliation Statement
            </h4>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('reconciliation.export', [
                'cost_center_id'  => $reconciliation->cost_center_id,
                'bank_account_id' => $reconciliation->bank_account_id,
                'from_date'       => $reconciliation->from_date ? $reconciliation->from_date->format('Y-m-d') : $reconciliation->statement_date->format('Y-m-01'),
                'to_date'         => $reconciliation->to_date ? $reconciliation->to_date->format('Y-m-d') : $reconciliation->statement_date->format('Y-m-d'),
            ]) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i>Download CSV
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i>Print Statement
            </button>
        </div>
    </div>

    @php
        $bank = $reconciliation->bankAccount;
        $cc   = $reconciliation->costCenter ?: $bank?->costCenter;
        $fromDate = $reconciliation->from_date ? $reconciliation->from_date->format('d/m/Y') : $reconciliation->statement_date->format('01/m/Y');
        $toDate   = $reconciliation->to_date ? $reconciliation->to_date->format('d/m/Y') : $reconciliation->statement_date->format('d/m/Y');
        $monthName = $reconciliation->to_date ? $reconciliation->to_date->format("F 'y") : $reconciliation->statement_date->format("F 'y");

        $itemsA1 = $reconciliation->items->where('schedule', 'A1');
        $itemsA2 = $reconciliation->items->where('schedule', 'A2');
        $itemsB1 = $reconciliation->items->where('schedule', 'B1');
        $itemsB2 = $reconciliation->items->where('schedule', 'B2');

        $totalA1 = (float) $itemsA1->sum('amount');
        $totalA2 = (float) $itemsA2->sum('amount');
        $totalAdd = $totalA1 + $totalA2;
        $subtotal = (float)$reconciliation->cash_book_balance + $totalAdd;

        $totalB1 = (float) $itemsB1->sum('amount');
        $totalB2 = (float) $itemsB2->sum('amount');
        $totalLess = $totalB1 + $totalB2;
        $reconciledBal = $subtotal - $totalLess;
    @endphp

    <!-- Official BPDB Statement Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden" id="printableStatement">
        <div class="card-body p-4 p-md-5">
            <!-- Header -->
            <div class="text-center mb-4 pb-3 border-bottom border-2">
                <h4 class="fw-bold text-dark text-uppercase mb-1 tracking-wide">BANGLADESH POWER DEVELOPMENT BOARD</h4>
                <h6 class="text-secondary mb-1">
                    Unit Office : <strong>{{ $cc ? ($cc->name_bn ?: $cc->name) : 'BPDB Office' }}</strong>
                </h6>
                <h5 class="fw-bold text-success text-uppercase mb-3">Bank Reconciliation Statement</h5>
                
                <div class="row g-2 text-start small bg-light p-3 rounded-3 border">
                    <div class="col-md-6">
                        <div>Name of Month:- <strong>{{ $monthName }}</strong> ({{ $fromDate }} to {{ $toDate }})</div>
                        <div class="mt-1">Nature of Account :- <strong>{{ $bank?->account_title }}</strong></div>
                        <div class="mt-1">Accounts Code :- <strong>{{ $cc?->code ?? ($bank?->chartOfAccount?->account_code ?? '135') }}</strong></div>
                    </div>
                    <div class="col-md-6">
                        <div>Bank's Name : <strong>{{ $bank?->bank_name }}</strong></div>
                        <div class="mt-1">{{ $bank?->branch_name }}</div>
                        <div class="mt-1">Account No.- <strong>{{ $bank?->account_type }} - {{ $bank?->account_number }}</strong></div>
                    </div>
                </div>
            </div>

            <!-- Summary Table -->
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle mb-0" style="font-size: 14px;">
                    <tbody>
                        <tr class="bg-light fw-bold">
                            <td style="width: 70%;" class="ps-3 py-2">Balance as per Cash Book</td>
                            <td style="width: 30%;" class="text-end pe-3 py-2 fs-6 text-primary">
                                ৳ {{ number_format($reconciliation->cash_book_balance, 2) }}
                            </td>
                        </tr>

                        <!-- Section A -->
                        <tr>
                            <td class="fw-bold text-success ps-3 pt-3">A) Add :-</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="ps-4">!) Cheque No. has been issued but not Presented to the Bank :-</td>
                            <td class="text-end pe-3">৳ {{ number_format($totalA1, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">!!) Deposited / Credited by Bank but not entered into the Cash Book :-</td>
                            <td class="text-end pe-3">৳ {{ number_format($totalA2, 2) }}</td>
                        </tr>
                        <tr class="fw-bold bg-success bg-opacity-10">
                            <td class="text-end pe-3 text-success">Total-Add</td>
                            <td class="text-end pe-3 text-success">৳ {{ number_format($totalAdd, 2) }}</td>
                        </tr>
                        <tr class="fw-bold table-light">
                            <td class="ps-3">Subtotal</td>
                            <td class="text-end pe-3">৳ {{ number_format($subtotal, 2) }}</td>
                        </tr>

                        <!-- Section B -->
                        <tr>
                            <td class="fw-bold text-danger ps-3 pt-3">B) Less :-</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td class="ps-4">!) Cheque No. /Draft/Pay order deposited but not Credited by Bank :-</td>
                            <td class="text-end pe-3">৳ {{ number_format($totalB1, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="ps-4">!!) Debited / Paid by the Bank but not entered into the Cash Book :-</td>
                            <td class="text-end pe-3">৳ {{ number_format($totalB2, 2) }}</td>
                        </tr>
                        <tr class="fw-bold bg-danger bg-opacity-10">
                            <td class="text-end pe-3 text-danger">Total-Less</td>
                            <td class="text-end pe-3 text-danger">৳ {{ number_format($totalLess, 2) }}</td>
                        </tr>

                        <!-- Reconciled -->
                        <tr class="table-primary border-2 fw-bold fs-6">
                            <td class="ps-3 py-3 text-primary">Reconciliation Balance as per Bank Statement</td>
                            <td class="text-end pe-3 py-3 text-primary">
                                ৳ {{ number_format($reconciledBal, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Schedules Details -->
            <div class="mt-4">
                <!-- A1 -->
                <div class="card border border-success border-opacity-25 rounded-3 mb-4 overflow-hidden">
                    <div class="card-header bg-success bg-opacity-10 py-2 px-3 fw-bold small text-success">
                        A--( ! ) Cheque No. has been issued but not Presented to the Bank :-
                    </div>
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                <th style="width: 30%;">Date</th>
                                <th style="width: 30%; text-align: right;">Amount in Taka</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itemsA1 as $it)
                                <tr>
                                    <td>{{ $it->reference_no ?: $it->description }}</td>
                                    <td>{{ $it->item_date?->format('d/m/Y') }}</td>
                                    <td class="text-end">৳ {{ number_format($it->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>Cheque No.--</td>
                                    <td>-</td>
                                    <td class="text-end">0.00</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">Total--A--(!):</td>
                                <td class="text-end">৳ {{ number_format($totalA1, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- A2 -->
                <div class="card border border-success border-opacity-25 rounded-3 mb-4 overflow-hidden">
                    <div class="card-header bg-success bg-opacity-10 py-2 px-3 fw-bold small text-success">
                        A--(!!) Deposited / Credited by Bank but not entered into the Cash Book :-
                    </div>
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                <th style="width: 30%;">Date</th>
                                <th style="width: 30%; text-align: right;">Amount in Taka</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itemsA2 as $it)
                                <tr>
                                    <td>{{ $it->reference_no ?: $it->description }}</td>
                                    <td>{{ $it->item_date?->format('d/m/Y') }}</td>
                                    <td class="text-end">৳ {{ number_format($it->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>-</td>
                                    <td>-</td>
                                    <td class="text-end">0.00</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">A--(!!) Total :-:</td>
                                <td class="text-end">৳ {{ number_format($totalA2, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- B1 -->
                <div class="card border border-danger border-opacity-25 rounded-3 mb-4 overflow-hidden">
                    <div class="card-header bg-danger bg-opacity-10 py-2 px-3 fw-bold small text-danger">
                        B--(!) Cheque No. /Draft/Pay order deposited but not Credited by Bank :-
                    </div>
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                <th style="width: 30%;">Date</th>
                                <th style="width: 30%; text-align: right;">Amount in Taka</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itemsB1 as $it)
                                <tr>
                                    <td>{{ $it->reference_no ?: $it->description }}</td>
                                    <td>{{ $it->item_date?->format('d/m/Y') }}</td>
                                    <td class="text-end">৳ {{ number_format($it->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>-</td>
                                    <td>-</td>
                                    <td class="text-end">0.00</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">Total (B--!):</td>
                                <td class="text-end">৳ {{ number_format($totalB1, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- B2 -->
                <div class="card border border-danger border-opacity-25 rounded-3 mb-4 overflow-hidden">
                    <div class="card-header bg-danger bg-opacity-10 py-2 px-3 fw-bold small text-danger">
                        B--(!!) Debited / Paid by the Bank but not entered into the Cash Book :-
                    </div>
                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 13px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Cheque No./ Memo/Draft/Pay order No.</th>
                                <th style="width: 30%;">Date</th>
                                <th style="width: 30%; text-align: right;">Amount in Taka</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itemsB2 as $it)
                                <tr>
                                    <td>{{ $it->reference_no ?: $it->description }}</td>
                                    <td>{{ $it->item_date?->format('d/m/Y') }}</td>
                                    <td class="text-end">৳ {{ number_format($it->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>As per bank statement</td>
                                    <td>-</td>
                                    <td class="text-end">0.00</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="2" class="text-end">Total (B--!!):</td>
                                <td class="text-end">৳ {{ number_format($totalB2, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Signatures -->
            <div class="row pt-5 mt-5 border-top">
                <div class="col-6 text-start">
                    <div class="fw-bold mb-4">__________________________</div>
                    <div class="fw-bold text-dark">Prepared by</div>
                    <div class="small text-muted">{{ $reconciliation->preparedBy?->designation ?: 'Assistant Accountant' }}</div>
                    <div class="small text-muted">{{ $cc ? ($cc->name_bn ?: $cc->name) : 'BPDB Office' }}</div>
                </div>
                <div class="col-6 text-end">
                    <div class="fw-bold mb-4">__________________________</div>
                    <div class="fw-bold text-dark">Assistant Director (Accounts)</div>
                    <div class="small text-muted">Accounts Officer</div>
                    <div class="small text-muted">{{ $cc ? ($cc->name_bn ?: $cc->name) : 'BPDB Office' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
