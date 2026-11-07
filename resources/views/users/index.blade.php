@extends('layouts.app')

@section('title', 'ব্যবহারকারী ব্যবস্থাপনা (Users) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-users-gear text-success me-2"></i>ব্যবহারকারী ব্যবস্থাপনা (Individual Users)
            </h4>
            <p class="text-muted small mb-0">অ্যাকাউন্টস কর্মকর্তাদের ইউজার অ্যাকাউন্ট তৈরি, ভূমিকা ও দপ্তর অ্যাসাইন</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fa-solid fa-user-plus me-1"></i>নতুন ব্যবহারকারী তৈরি
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 20%;">কর্মকর্তা ও ইউজারনেম</th>
                        <th style="width: 15%;">পদবি ও দপ্তর</th>
                        <th style="width: 15%;">নির্ধারিত ভূমিকা (Role)</th>
                        <th style="width: 20%;">অ্যাসাইনকৃত কস্ট সেন্টার</th>
                        <th style="width: 15%;">অ্যাসাইনকৃত ব্যাংক হিসাব</th>
                        <th style="width: 5%; text-align: center;">স্ট্যাটাস</th>
                        <th style="width: 10%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($u->photo_url)
                                        <img src="{{ $u->photo_url }}" alt="" class="rounded-circle object-fit-cover border shadow-sm flex-shrink-0" style="width: 36px; height: 36px;">
                                    @else
                                        <div class="rounded-circle bg-light border text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; font-size: 13px;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <strong class="text-dark d-block">{{ $u->name_bn ?: $u->name }}</strong>
                                        <small class="text-muted"><i class="fa-solid fa-id-badge me-1"></i>{{ $u->erp_no ?: $u->username }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold">{{ $u->designation ?: 'হিসাব কর্মকর্তা' }}</div>
                                <small class="text-muted">{{ $u->office_name ?: 'বিউবো' }}</small>
                            </td>
                            <td>
                                @foreach ($u->roles as $r)
                                    <span class="badge bg-success bg-opacity-10 text-success me-1">
                                        {{ $r->display_name_bn ?: $r->display_name }}
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                @if ($u->isSystemAdmin() || $u->isDirectorAccounts())
                                    <span class="badge bg-light text-dark border">সার্বিক বিউবো (All Offices)</span>
                                @else
                                    @forelse ($u->costCenters as $cc)
                                        <span class="badge bg-light text-dark border me-1 mb-1">{{ $cc->code }}</span>
                                    @empty
                                        <span class="text-muted small">কোনোটি নয়</span>
                                    @endforelse
                                @endif
                            </td>
                            <td>
                                @if ($u->isSystemAdmin() || $u->isDirectorAccounts())
                                    <span class="badge bg-light text-dark border">সকল ব্যাংক হিসাব</span>
                                @else
                                    @forelse ($u->bankAccounts as $ba)
                                        <span class="badge bg-light text-dark border me-1 mb-1" title="{{ $ba->bank_name }}">
                                            {{ $ba->account_number }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">কোনোটি নয়</span>
                                    @endforelse
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($u->is_active)
                                    <span class="badge bg-success">সক্রিয়</span>
                                @else
                                    <span class="badge bg-danger">নিষ্ক্রিয়</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="সম্পাদনা">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggle', $u) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-{{ $u->is_active ? 'warning' : 'success' }}" title="{{ $u->is_active ? 'নিষ্ক্রিয় করুন' : 'সক্রিয় করুন' }}">
                                                <i class="fa-solid fa-power-off"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">কোনো ব্যবহারকারী পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- Edit User Modals (Outside table and card) -->
@foreach ($users as $u)
    <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('users.update', $u) }}" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-user-pen me-2 text-primary"></i>ব্যবহারকারী তথ্য সম্পাদনা</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">ইউজারনেম / ERP <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" value="{{ $u->username }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">পাসওয়ার্ড (পরিবর্তন করতে চাইলে লিখুন)</label>
                            <input type="password" name="password" class="form-control" placeholder="নতুন পাসওয়ার্ড">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">পূর্ণ নাম (বাংলা)</label>
                            <input type="text" name="name_bn" class="form-control" value="{{ $u->name_bn }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">পূর্ণ নাম (ইংরেজি) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">পদবি</label>
                            <input type="text" name="designation" class="form-control" value="{{ $u->designation }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">দপ্তরের নাম</label>
                            <input type="text" name="office_name" class="form-control" value="{{ $u->office_name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">ইমেইল</label>
                            <input type="email" name="email" class="form-control" value="{{ $u->email }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">মোবাইল নম্বর</label>
                            <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark">নির্ধারিত ভূমিকা (Role) <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-select" required>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}" @selected($u->roles->contains('id', $r->id))>
                                        {{ $r->display_name_bn ?: $r->display_name }} ({{ $r->name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Assigned Cost Centers -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">অ্যাসাইনকৃত কস্ট সেন্টারসমূহ (Cost Centers):</label>
                        <div class="p-2 border rounded-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                            <div class="row g-2">
                                @foreach ($costCenters as $cc)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="cost_center_ids[]" value="{{ $cc->id }}" id="ecc_{{ $u->id }}_{{ $cc->id }}" class="form-check-input" @checked($u->costCenters->contains('id', $cc->id))>
                                            <label class="form-check-label small" for="ecc_{{ $u->id }}_{{ $cc->id }}">
                                                {{ $cc->code }} - {{ $cc->name_bn }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Bank Accounts -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">অ্যাসাইনকৃত ব্যাংক হিসাবসমূহ (Bank Accounts):</label>
                        <div class="p-2 border rounded-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                            <div class="row g-2">
                                @foreach ($bankAccounts as $ba)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" name="bank_account_ids[]" value="{{ $ba->id }}" id="eba_{{ $u->id }}_{{ $ba->id }}" class="form-check-input" @checked($u->bankAccounts->contains('id', $ba->id))>
                                            <label class="form-check-label small" for="eba_{{ $u->id }}_{{ $ba->id }}">
                                                {{ $ba->bank_name }} ({{ $ba->account_number }})
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">আপডেট করুন</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('users.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-success"></i>নতুন ব্যবহারকারী তৈরি</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">ইউজারনেম / ERP নম্বর <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="যেমন: 100234" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">পাসওয়ার্ড <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="পাসওয়ার্ড লিখুন (কমপক্ষে ৬ অক্ষর)" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">পূর্ণ নাম (বাংলা)</label>
                        <input type="text" name="name_bn" class="form-control" placeholder="যেমন: মো. কামরুল হাসান">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">পূর্ণ নাম (ইংরেজি) <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="যেমন: Md. Kamrul Hasan" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">পদবি</label>
                        <input type="text" name="designation" class="form-control" placeholder="যেমন: সহকারী হিসাব কর্মকর্তা">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">দপ্তরের নাম</label>
                        <input type="text" name="office_name" class="form-control" placeholder="যেমন: হিসাব পরিদপ্তর, ওয়াপদা ভবন, ঢাকা">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">ইমেইল</label>
                        <input type="email" name="email" class="form-control" placeholder="user@bpdb.gov.bd">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">মোবাইল নম্বর</label>
                        <input type="text" name="phone" class="form-control" placeholder="017xxxxxxxx">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">নির্ধারিত ভূমিকা (Role) <span class="text-danger">*</span></label>
                        <select name="role_id" class="form-select" required>
                            @foreach ($roles as $r)
                                <option value="{{ $r->id }}">
                                    {{ $r->display_name_bn ?: $r->display_name }} ({{ $r->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">অ্যাসাইনকৃত কস্ট সেন্টারসমূহ:</label>
                    <div class="p-2 border rounded-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                        <div class="row g-2">
                            @foreach ($costCenters as $cc)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="cost_center_ids[]" value="{{ $cc->id }}" id="ncc_{{ $cc->id }}" class="form-check-input">
                                        <label class="form-check-label small" for="ncc_{{ $cc->id }}">
                                            {{ $cc->code }} - {{ $cc->name_bn }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                <button type="submit" class="btn btn-success rounded-pill">সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>
@endsection
