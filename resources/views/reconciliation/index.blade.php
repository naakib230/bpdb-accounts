@extends('layouts.app')

@section('title', 'ব্যাংক সমন্বয় বিবরণী | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-handshake-simple text-success me-2"></i>ব্যাংক সমন্বয় বিবরণী (Bank Reconciliation)
            </h4>
            <p class="text-muted small mb-0">ব্যাংক বিবরণী (Bank Statement) এবং ক্যাশ বুকের (Cash Book) ব্যালেন্স মিলকরণ ও সমন্বয়</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('reconciliation.create') }}" class="btn btn-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i>নতুন ব্যাংক সমন্বয়
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 25%;">ব্যাংকের নাম ও হিসাব</th>
                        <th style="width: 15%;">বিবরণীর তারিখ</th>
                        <th style="width: 15%; text-align: right;">ব্যাংক স্টেটমেন্ট ব্যালেন্স (৳)</th>
                        <th style="width: 15%; text-align: right;">ক্যাশ বুক ব্যালেন্স (৳)</th>
                        <th style="width: 10%; text-align: right;">পার্থক্য (৳)</th>
                        <th style="width: 10%;">অবস্থা (Status)</th>
                        <th style="width: 10%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reconciliations as $r)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $r->bankAccount?->bank_name }}</strong>
                                <small class="text-muted d-block">{{ $r->bankAccount?->account_number }} ({{ $r->bankAccount?->account_title }})</small>
                            </td>
                            <td>{{ $r->statement_date->format('d/m/Y') }}</td>
                            <td class="text-end fw-semibold">৳ {{ number_format($r->bank_statement_balance, 2) }}</td>
                            <td class="text-end fw-semibold text-primary">৳ {{ number_format($r->cash_book_balance, 2) }}</td>
                            <td class="text-end fw-bold {{ abs($r->difference) < 0.01 ? 'text-success' : 'text-danger' }}">
                                ৳ {{ number_format($r->difference, 2) }}
                            </td>
                            <td>
                                @if ($r->status === 'reconciled')
                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>সমন্বিত</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>খসড়া / অমিল</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('reconciliation.show', $r) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                    <i class="fa-solid fa-eye me-1"></i>দেখুন
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                কোনো ব্যাংক সমন্বয় বিবরণী পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reconciliations->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $reconciliations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
