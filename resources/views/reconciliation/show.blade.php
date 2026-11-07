@extends('layouts.app')

@section('title', 'ব্যাংক সমন্বয় বিবরণী | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <a href="{{ route('reconciliation.index') }}" class="text-decoration-none text-secondary small">
                <i class="fa-solid fa-arrow-left me-1"></i>তালিকায় ফিরে যান
            </a>
            <h4 class="fw-bold text-dark mt-2 mb-1">
                <i class="fa-solid fa-handshake-simple text-success me-2"></i>ব্যাংক সমন্বয় বিবরণী
                <span class="badge {{ $reconciliation->status === 'reconciled' ? 'bg-success' : 'bg-warning text-dark' }} fs-6 ms-2">
                    {{ $reconciliation->status === 'reconciled' ? 'সমন্বিত' : 'খসড়া / অমিল' }}
                </span>
            </h4>
            <p class="text-muted small mb-0">{{ $reconciliation->bankAccount?->bank_name }} • {{ $reconciliation->statement_date->format('d/m/Y') }} তারিখ পর্যন্ত</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm no-print">
                <i class="fa-solid fa-print me-1"></i>প্রিন্ট
            </button>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">ব্যাংকের নাম:</small>
                <strong>{{ $reconciliation->bankAccount?->bank_name }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">হিসাব নম্বর:</small>
                <strong>{{ $reconciliation->bankAccount?->account_number }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">বিবরণীর তারিখ:</small>
                <strong>{{ $reconciliation->statement_date->format('d/m/Y') }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">প্রস্তুতকারী:</small>
                <strong>{{ $reconciliation->preparedBy?->name_bn ?: $reconciliation->preparedBy?->name }}</strong>
            </div>

            <div class="col-6 col-md-4 mt-3 pt-3 border-top">
                <small class="text-muted d-block">ক্যাশ বুক ব্যালেন্স:</small>
                <h5 class="fw-bold text-primary mb-0">৳ {{ number_format($reconciliation->cash_book_balance, 2) }}</h5>
            </div>
            <div class="col-6 col-md-4 mt-3 pt-3 border-top">
                <small class="text-muted d-block">ব্যাংক স্টেটমেন্ট ব্যালেন্স:</small>
                <h5 class="fw-bold text-dark mb-0">৳ {{ number_format($reconciliation->bank_statement_balance, 2) }}</h5>
            </div>
            <div class="col-6 col-md-4 mt-3 pt-3 border-top">
                <small class="text-muted d-block">সমন্বিত ব্যালেন্স (Reconciled):</small>
                <h5 class="fw-bold text-success mb-0">৳ {{ number_format($reconciliation->reconciled_balance, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- Reconciling Items Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white p-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check me-2 text-primary"></i>সমন্বয়কারী আইটেমসমূহ
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 15%;">তারিখ</th>
                        <th style="width: 15%;">রেফারেন্স নং</th>
                        <th style="width: 35%;">বিবরণ</th>
                        <th style="width: 15%;">প্রভাব</th>
                        <th style="width: 15%; text-align: right;">টাকার পরিমাণ (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reconciliation->items as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td>{{ $item->item_date->format('d/m/Y') }}</td>
                            <td>{{ $item->reference_no ?: '-' }}</td>
                            <td>{{ $item->description }}</td>
                            <td>
                                <span class="badge {{ $item->effect === 'add' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $item->effect === 'add' ? '(+) যোগ' : '(-) বিয়োগ' }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">৳ {{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">কোনো সমন্বয়কারী আইটেম নেই।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
