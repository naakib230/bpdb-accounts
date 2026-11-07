@extends('layouts.app')

@section('title', 'ভাউচার তালিকা | বিউবো অ্যাকাউন্টস পোর্টাল')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-file-invoice-dollar text-success me-2"></i>ভাউচার তালিকা (Vouchers)
            </h4>
            <p class="text-muted small mb-0">ক্যাশ ও জার্নাল ভাউচার পর্যালোচনা, অনুমোদন ও প্রিন্ট কপি</p>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('vouchers.create_cash') }}" class="btn btn-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-money-bill-transfer me-1"></i>ক্যাশ ভাউচার তৈরি
            </a>
            <a href="{{ route('vouchers.create_journal') }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-book-journal-whills me-1"></i>জার্নাল ভাউচার তৈরি
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('vouchers.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">ভাউচার ধরন</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">সকল ধরন</option>
                        <option value="cash_voucher" @selected(request('type') === 'cash_voucher')>ক্যাশ ভাউচার</option>
                        <option value="journal_voucher" @selected(request('type') === 'journal_voucher')>জার্নাল ভাউচার</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">স্ট্যাটাস</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">সকল স্ট্যাটাস</option>
                        <option value="draft" @selected(request('status') === 'draft')>খসড়া (Draft)</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>অনুমোদনের অপেক্ষায়</option>
                        <option value="posted" @selected(request('status') === 'posted')>পোস্টকৃত (Posted)</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>প্রত্যাখ্যাত</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">কস্ট সেন্টার</label>
                    <select name="cost_center_id" class="form-select form-select-sm">
                        <option value="">সকল কস্ট সেন্টার</option>
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}" @selected(request('cost_center_id') == $cc->id)>
                                {{ $cc->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">শুরুর তারিখ</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">শেষ তারিখ</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-success btn-sm w-100" title="ফিল্টার করুন">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary btn-sm" title="রিসেট">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>ভাউচার নং</th>
                        <th>তারিখ</th>
                        <th>ধরন</th>
                        <th>কস্ট সেন্টার</th>
                        <th>প্রাপক / প্রদানকারী</th>
                        <th>বিবরণ (Narration)</th>
                        <th>মোট পরিমাণ</th>
                        <th>স্ট্যাটাস</th>
                        <th class="text-end">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vouchers as $v)
                        <tr>
                            <td class="fw-bold text-dark">
                                <a href="{{ route('vouchers.show', $v) }}" class="text-decoration-none text-success">
                                    {{ $v->voucher_number }}
                                </a>
                            </td>
                            <td>{{ $v->date->format('d/m/Y') }}</td>
                            <td>
                                @if ($v->isCashVoucher())
                                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                        <i class="fa-solid fa-money-bill me-1"></i>ক্যাশ ভাউচার
                                    </span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                        <i class="fa-solid fa-book-journal-whills me-1"></i>জার্নাল ভাউচার
                                    </span>
                                @endif
                            </td>
                            <td class="small">{{ $v->costCenter?->name_bn }}</td>
                            <td class="small text-truncate" style="max-width: 150px;">{{ $v->payee_payer_name ?: '-' }}</td>
                            <td class="small text-truncate" style="max-width: 200px;" title="{{ $v->narration }}">
                                {{ $v->narration }}
                            </td>
                            <td class="fw-bold text-dark">৳ {{ number_format($v->total_debit, 2) }}</td>
                            <td>
                                @if ($v->status === 'posted')
                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>পোস্টকৃত</span>
                                @elseif ($v->status === 'submitted')
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i>অপেক্ষমাণ</span>
                                @elseif ($v->status === 'rejected')
                                    <span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i>প্রত্যাখ্যাত</span>
                                @else
                                    <span class="badge bg-secondary"><i class="fa-solid fa-file-pen me-1"></i>খসড়া</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('vouchers.show', $v) }}" class="btn btn-outline-secondary" title="বিস্তারিত দেখুন">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('vouchers.print', $v) }}" target="_blank" class="btn btn-outline-dark" title="ভাউচার প্রিন্ট">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                    @if ($v->status === 'submitted' && (auth()->user()->isSystemAdmin() || auth()->user()->isDirectorAccounts() || auth()->user()->hasRole('accounts_officer')))
                                        <form method="POST" action="{{ route('vouchers.approve', $v) }}" class="d-inline" onsubmit="return confirm('ভাউচারটি অনুমোদন ও পোস্ট করতে চান?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-success" title="অনুমোদন করুন">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fs-1 text-secondary mb-2 d-block"></i>
                                কোনো ভাউচার তথ্য পাওয়া যায়নি।
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vouchers->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $vouchers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
