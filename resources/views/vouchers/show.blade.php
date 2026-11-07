@extends('layouts.app')

@section('title', 'ভাউচার বিবরণ - ' . $voucher->voucher_number . ' | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <a href="{{ route('vouchers.index') }}" class="text-decoration-none text-secondary small">
                <i class="fa-solid fa-arrow-left me-1"></i>ভাউচার তালিকায় ফিরে যান
            </a>
            <h4 class="fw-bold text-dark mt-2 mb-1">
                {{ $voucher->voucher_number }}
                <span class="badge {{ $voucher->status === 'posted' ? 'bg-success' : ($voucher->status === 'submitted' ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6 ms-2">
                    {{ $voucher->bangla_status }}
                </span>
            </h4>
            <p class="text-muted small mb-0">{{ $voucher->bangla_voucher_type }} • কস্ট সেন্টার: {{ $voucher->costCenter?->name_bn }}</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('vouchers.print', $voucher) }}" target="_blank" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-print me-1"></i>প্রিন্ট ভাউচার
            </a>

            @if ($voucher->status === 'submitted' && (auth()->user()->isSystemAdmin() || auth()->user()->isDirectorAccounts() || auth()->user()->hasRole('accounts_officer')))
                <form method="POST" action="{{ route('vouchers.approve', $voucher) }}" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত যে ভাউচারটি অনুমোদন ও পোস্ট করবেন?')">
                    @csrf
                    <button type="submit" class="btn btn-success rounded-pill px-3 shadow-sm">
                        <i class="fa-solid fa-check me-1"></i>অনুমোদন ও পোস্টিং
                    </button>
                </form>

                <button type="button" class="btn btn-danger rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    <i class="fa-solid fa-xmark me-1"></i>প্রত্যাখ্যান
                </button>
            @endif

            @if ($voucher->status === 'draft')
                <form method="POST" action="{{ route('vouchers.destroy', $voucher) }}" class="d-inline" onsubmit="return confirm('খসড়া ভাউচারটি মুছে ফেলতে চান?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-3">
                        <i class="fa-solid fa-trash-can me-1"></i>মুছে ফেলুন
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Voucher Details Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 p-4">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">ভাউচারের তারিখ:</small>
                <strong class="text-dark">{{ $voucher->date->format('d/m/Y') }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">অর্থবছর:</small>
                <strong class="text-dark">{{ $voucher->fiscalYear?->name }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">প্রস্তুতকারী কর্মকর্তা:</small>
                <strong class="text-dark">{{ $voucher->preparedBy?->name_bn ?: $voucher->preparedBy?->name }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <small class="text-muted d-block">অনুমোদনকারী:</small>
                <strong class="text-dark">{{ $voucher->approvedBy?->name_bn ?: ($voucher->approvedBy?->name ?: 'অপেক্ষমাণ') }}</strong>
            </div>

            @if ($voucher->bankAccount)
                <div class="col-6 col-md-4">
                    <small class="text-muted d-block">ব্যাংক হিসাব:</small>
                    <strong class="text-dark">{{ $voucher->bankAccount->bank_name }} ({{ $voucher->bankAccount->account_number }})</strong>
                </div>
                <div class="col-6 col-md-4">
                    <small class="text-muted d-block">চেক নম্বর ও তারিখ:</small>
                    <strong class="text-dark">{{ $voucher->cheque_number ?: '-' }} ({{ $voucher->cheque_date ? $voucher->cheque_date->format('d/m/Y') : '-' }})</strong>
                </div>
            @endif

            @if ($voucher->payee_payer_name)
                <div class="col-6 col-md-4">
                    <small class="text-muted d-block">প্রাপক / প্রদানকারী:</small>
                    <strong class="text-dark">{{ $voucher->payee_payer_name }}</strong>
                </div>
            @endif

            <div class="col-12 mt-3 pt-3 border-top">
                <small class="text-muted d-block">সার্বিক বিবরণ (Narration):</small>
                <p class="text-dark mb-0 fw-medium">{{ $voucher->narration }}</p>
            </div>

            @if ($voucher->rejection_reason)
                <div class="col-12 mt-2">
                    <div class="alert alert-danger mb-0 py-2">
                        <strong>প্রত্যাখ্যানের কারণ:</strong> {{ $voucher->rejection_reason }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Entries Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-ol me-2 text-primary"></i>ভাউচার এন্ট্রি তালিকা (Double Entry Items)
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 15%;">হিসাব কোড</th>
                        <th style="width: 35%;">হিসাব খাত (Account Head)</th>
                        <th style="width: 25%;">লাইন বিবরণ</th>
                        <th style="width: 10%; text-align: right;">ডেবিট (টাকা)</th>
                        <th style="width: 10%; text-align: right;">ক্রেডিট (টাকা)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($voucher->entries as $idx => $entry)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold">{{ $entry->chartOfAccount?->account_code }}</td>
                            <td>
                                <div>{{ $entry->chartOfAccount?->account_name_bn }}</div>
                                <small class="text-muted">{{ $entry->chartOfAccount?->account_name }}</small>
                            </td>
                            <td class="small">{{ $entry->line_narration ?: '-' }}</td>
                            <td class="text-end fw-semibold">
                                {{ $entry->debit_amount > 0 ? '৳ ' . number_format($entry->debit_amount, 2) : '-' }}
                            </td>
                            <td class="text-end fw-semibold">
                                {{ $entry->credit_amount > 0 ? '৳ ' . number_format($entry->credit_amount, 2) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light border-top border-2">
                    <tr>
                        <th colspan="4" class="text-end fw-bold">সর্বমোট:</th>
                        <th class="text-end fw-bold text-success fs-6">৳ {{ number_format($voucher->total_debit, 2) }}</th>
                        <th class="text-end fw-bold text-primary fs-6">৳ {{ number_format($voucher->total_credit, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('vouchers.reject', $voucher) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>ভাউচার প্রত্যাখ্যান</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">প্রত্যাখ্যানের সুনির্দিষ্ট কারণ উল্লেখ করুন:</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="ভাউচারে ভুল তথ্য বা বিল ত্রুটি থাকলে লিখুন..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                <button type="submit" class="btn btn-danger rounded-pill">প্রত্যাখ্যান নিশ্চিত করুন</button>
            </div>
        </form>
    </div>
</div>
@endsection
