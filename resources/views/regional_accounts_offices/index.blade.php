@extends('layouts.app')

@section('title', 'আঞ্চলিক হিসাব দপ্তর (আহিদ) ব্যবস্থাপনা | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-building-columns text-success me-2"></i>আঞ্চলিক হিসাব দপ্তর (আহিদ / RAO) ব্যবস্থাপনা
            </h4>
            <p class="text-muted small mb-0">বিউবোর ২৮টি আঞ্চলিক হিসাব দপ্তর ও কেন্দ্রীয় কোয়াক সেল কনফিগারেশন, কস্ট সেন্টার ও ব্যাংক হিসাব ম্যাপিং</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createRaoModal">
                <i class="fa-solid fa-plus me-1"></i>নতুন আহিদ যোগ করুন
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('raos.index') }}" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="কোড, নাম অথবা ঠিকানা খুঁজুন..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">সকল ধরণের দপ্তর (All Types)</option>
                        <option value="regional" @selected($type === 'regional')>আঞ্চলিক হিসাব দপ্তর (Regional RAO)</option>
                        <option value="generation" @selected($type === 'generation')>বিদ্যুৎ কেন্দ্র ভিত্তিক আহিদ (Generation RAO)</option>
                        <option value="central_overhead" @selected($type === 'central_overhead')>কেন্দ্রীয় সেল (COOAC / Central)</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="fa-solid fa-filter me-1"></i>ফিল্টার
                    </button>
                    @if ($search || $type)
                        <a href="{{ route('raos.index') }}" class="btn btn-outline-secondary btn-sm">রিসেট</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Offices Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 10%;">অফিস কোড</th>
                        <th style="width: 25%;">দপ্তরের নাম (আহিদ / সেল)</th>
                        <th style="width: 25%;">ঠিকানা ও অবস্থান</th>
                        <th style="width: 12%;">ধরন (Type)</th>
                        <th style="width: 13%; text-align: center;">সংযুক্ত কস্ট সেন্টার</th>
                        <th style="width: 15%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($offices as $office)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 fs-6 fw-bold">
                                    {{ $office->code }}
                                </span>
                            </td>
                            <td>
                                <strong class="text-dark d-block">{{ $office->name_bn }}</strong>
                                <small class="text-muted">{{ $office->name }}</small>
                            </td>
                            <td>
                                <small class="text-secondary"><i class="fa-solid fa-location-dot me-1 text-danger"></i>{{ $office->address ?: 'ঠিকানা সংরক্ষিত নেই' }}</small>
                            </td>
                            <td>
                                @if ($office->type === 'central_overhead')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                        <i class="fa-solid fa-star me-1"></i>কোয়াক (COOAC)
                                    </span>
                                @elseif ($office->type === 'generation')
                                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-50">
                                        <i class="fa-solid fa-bolt me-1"></i>উৎপাদন কেন্দ্র আহিদ
                                    </span>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        <i class="fa-solid fa-building me-1"></i>আঞ্চলিক আহিদ
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="javascript:void(0)" 
                                   class="btn btn-sm btn-light border rounded-pill px-3" 
                                   data-bs-toggle="modal" 
                                   data-bs-target="#assignModal{{ $office->id }}"
                                   title="কস্ট সেন্টার ও ব্যাংক হিসাব অ্যাসাইন করুন">
                                    <span class="badge bg-success me-1">{{ $office->cost_centers_count }}</span> CC
                                    <span class="badge bg-info ms-1">{{ $office->bank_accounts_count }}</span> Bank
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm rounded-circle p-1" 
                                            style="width: 30px; height: 30px;"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editRaoModal{{ $office->id }}" 
                                            title="সম্পাদনা">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-success btn-sm rounded-circle p-1" 
                                            style="width: 30px; height: 30px;"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#assignModal{{ $office->id }}" 
                                            title="কস্ট সেন্টার ও ব্যাংক ম্যাপিং">
                                        <i class="fa-solid fa-link"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editRaoModal{{ $office->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <form method="POST" action="{{ route('raos.update', $office->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header border-0 pb-0">
                                            <h5 class="modal-title fw-bold text-dark">
                                                <i class="fa-solid fa-pen-to-square text-primary me-2"></i>আহিদ তথ্য সম্পাদনা
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-semibold">অফিস কোড <span class="text-danger">*</span></label>
                                                    <input type="text" name="code" class="form-control" value="{{ $office->code }}" required>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label small fw-semibold">ধরন (Type) <span class="text-danger">*</span></label>
                                                    <select name="type" class="form-select" required>
                                                        <option value="regional" @selected($office->type === 'regional')>আঞ্চলিক আহিদ (Regional RAO)</option>
                                                        <option value="generation" @selected($office->type === 'generation')>বিদ্যুৎ কেন্দ্র আহিদ (Generation RAO)</option>
                                                        <option value="central_overhead" @selected($office->type === 'central_overhead')>কেন্দ্রীয় সেল (COOAC / Central)</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small fw-semibold">দপ্তরের নাম (বাংলা) <span class="text-danger">*</span></label>
                                                    <input type="text" name="name_bn" class="form-control" value="{{ $office->name_bn }}" required>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small fw-semibold">দপ্তরের নাম (ইংরেজি) <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="{{ $office->name }}" required>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small fw-semibold">ঠিকানা ও অবস্থান</label>
                                                    <textarea name="address" class="form-control" rows="2">{{ $office->address }}</textarea>
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeRao{{ $office->id }}" @checked($office->is_active)>
                                                        <label class="form-check-label small" for="activeRao{{ $office->id }}">দপ্তরটি সক্রিয় (Active)</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">বাতিল</button>
                                            <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">সংরক্ষণ করুন</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Assign CC & Bank Modal -->
                        <div class="modal fade" id="assignModal{{ $office->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content border-0 rounded-4 shadow">
                                    <form method="POST" action="{{ route('raos.assign', $office->id) }}">
                                        @csrf
                                        <div class="modal-header border-0 pb-0">
                                            <div>
                                                <h5 class="modal-title fw-bold text-dark mb-1">
                                                    <i class="fa-solid fa-link text-success me-2"></i>কস্ট সেন্টার ও ব্যাংক হিসাব ম্যাপিং
                                                </h5>
                                                <small class="text-muted">{{ $office->code }} - {{ $office->name_bn }}</small>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <ul class="nav nav-pills mb-3 nav-fill" id="pills-tab{{ $office->id }}" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active rounded-pill fw-semibold" id="tab-cc-{{ $office->id }}" data-bs-toggle="pill" data-bs-target="#pane-cc-{{ $office->id }}" type="button" role="tab">
                                                        <i class="fa-solid fa-sitemap me-1"></i>কস্ট সেন্টারসমূহ (Cost Centers)
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link rounded-pill fw-semibold" id="tab-ba-{{ $office->id }}" data-bs-toggle="pill" data-bs-target="#pane-ba-{{ $office->id }}" type="button" role="tab">
                                                        <i class="fa-solid fa-piggy-bank me-1"></i>ব্যাংক হিসাবসমূহ (Bank Accounts)
                                                    </button>
                                                </li>
                                            </ul>

                                            <div class="tab-content" id="pills-tabContent{{ $office->id }}">
                                                <!-- CC Tab -->
                                                <div class="tab-pane fade show active" id="pane-cc-{{ $office->id }}" role="tabpanel">
                                                    <p class="small text-muted mb-2">এই আহিদ (RAO)-এর অধীনে যেসকল কস্ট সেন্টার পরিচালিত হবে তা নির্বাচন করুন:</p>
                                                    <div class="list-group list-group-flush border rounded-3 p-2" style="max-height: 280px; overflow-y: auto;">
                                                        @foreach ($allCostCenters as $cc)
                                                            <label class="list-group-item d-flex align-items-center gap-2 border-0 py-2">
                                                                <input class="form-check-input flex-shrink-0" type="checkbox" name="cost_center_ids[]" value="{{ $cc->id }}" @checked($cc->regional_accounts_office_id == $office->id)>
                                                                <div class="d-flex flex-column">
                                                                    <span class="small fw-bold text-dark">{{ $cc->code }} - {{ $cc->name_bn }}</span>
                                                                    <span class="small text-muted">
                                                                        ট্রায়াল ব্যালেন্স ধরন: <span class="badge bg-light text-dark border">{{ $cc->trial_balance_type_label }}</span>
                                                                        @if ($cc->regional_accounts_office_id && $cc->regional_accounts_office_id != $office->id)
                                                                            <span class="text-danger ms-1">(অন্য দপ্তরে: {{ $cc->regionalAccountsOffice?->code }})</span>
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <!-- Bank Tab -->
                                                <div class="tab-pane fade" id="pane-ba-{{ $office->id }}" role="tabpanel">
                                                    <p class="small text-muted mb-2">এই আহিদ (RAO)-এর অধীনে পরিচালিত ব্যাংক হিসাবসমূহ নির্বাচন করুন:</p>
                                                    <div class="list-group list-group-flush border rounded-3 p-2" style="max-height: 280px; overflow-y: auto;">
                                                        @foreach ($allBankAccounts as $ba)
                                                            <label class="list-group-item d-flex align-items-center gap-2 border-0 py-2">
                                                                <input class="form-check-input flex-shrink-0" type="checkbox" name="bank_account_ids[]" value="{{ $ba->id }}" @checked($ba->regional_accounts_office_id == $office->id)>
                                                                <div class="d-flex flex-column">
                                                                    <span class="small fw-bold text-dark">{{ $ba->bank_name }} - {{ $ba->account_number }}</span>
                                                                    <span class="small text-muted">{{ $ba->account_title }} (কস্ট সেন্টার: {{ $ba->costCenter?->name_bn ?: 'সকল' }})</span>
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 pt-0">
                                            <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">বাতিল</button>
                                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">ম্যাপিং সংরক্ষণ করুন</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">কোনো আঞ্চলিক হিসাব দপ্তর পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($offices->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $offices->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create RAO Modal -->
<div class="modal fade" id="createRaoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form method="POST" action="{{ route('raos.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-plus-circle text-success me-2"></i>নতুন আঞ্চলিক হিসাব দপ্তর (আহিদ) যোগ করুন
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">অফিস কোড <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="যেমন: 153" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">ধরন (Type) <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="regional">আঞ্চলিক আহিদ (Regional RAO)</option>
                                <option value="generation">বিদ্যুৎ কেন্দ্র আহিদ (Generation RAO)</option>
                                <option value="central_overhead">কেন্দ্রীয় সেল (COOAC / Central)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">দপ্তরের নাম (বাংলা) <span class="text-danger">*</span></label>
                            <input type="text" name="name_bn" class="form-control" placeholder="যেমন: আহিদ, বিউবো, বগুড়া" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">দপ্তরের নাম (ইংরেজি) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="যেমন: RAO, BPDB, Bogura" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">ঠিকানা ও অবস্থান</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="দপ্তরের বিস্তারিত ঠিকানা..."></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeRaoNew" checked>
                                <label class="form-check-label small" for="activeRaoNew">দপ্তরটি সক্রিয় (Active)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">যুক্ত করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
