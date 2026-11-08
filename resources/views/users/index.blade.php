@extends('layouts.app')

@section('title', 'ব্যবহারকারী ব্যবস্থাপনা (Users) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-users-gear text-success me-2"></i>ব্যবহারকারী ব্যবস্থাপনা (Individual Users)
            </h4>
            <p class="text-muted small mb-0">বিউবো কেন্দ্রীয় হিসাব দপ্তর (বিকেসি) ও আঞ্চলিক হিসাব দপ্তর (আহিদ / RAO) ভিত্তিক কর্মকর্তা ইউজার ও রোল ব্যবস্থাপনা</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fa-solid fa-user-plus me-1"></i>নতুন ব্যবহারকারী তৈরি
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control form-control-sm border-0 bg-light" placeholder="নাম, ইউজারনেম, ইমেইল বা পদবি..." value="{{ $search }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="rao_id" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
                        <option value="">-- সকল আহিদ / RAO (All RAOs) --</option>
                        @foreach ($raos as $r)
                            <option value="{{ $r->id }}" @selected($raoFilter == $r->id)>
                                [{{ $r->code }}] {{ $r->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select form-select-sm border-0 bg-light" onchange="this.form.submit()">
                        <option value="">-- সকল রোল (All Roles) --</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->name }}" @selected($roleFilter == $r->name)>
                                {{ $r->display_name_bn ?: $r->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">খুঁজুন</button>
                    @if ($search || $raoFilter || $roleFilter)
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill" title="ফিল্টার রিসেট"><i class="fa-solid fa-xmark"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 22%;">কর্মকর্তা ও ইউজারনেম</th>
                        <th style="width: 18%;">পদবি ও দপ্তর / RAO</th>
                        <th style="width: 18%;">নির্ধারিত ভূমিকা (Role)</th>
                        <th style="width: 16%;">অ্যাসাইনকৃত কস্ট সেন্টার</th>
                        <th style="width: 13%;">অ্যাসাইনকৃত ব্যাংক হিসাব</th>
                        <th style="width: 5%; text-align: center;">স্ট্যাটাস</th>
                        <th style="width: 8%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($u->photo_url)
                                        <img src="{{ $u->photo_url }}" alt="" class="rounded-circle object-fit-cover border shadow-sm flex-shrink-0" style="width: 38px; height: 38px;">
                                    @else
                                        <div class="rounded-circle bg-light border text-secondary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; font-size: 14px;">
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
                                @if ($u->regionalAccountsOffice)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary mt-1">
                                        <i class="fa-solid fa-building-shield me-1"></i>[{{ $u->regionalAccountsOffice->code }}] {{ $u->regionalAccountsOffice->name_bn }}
                                    </span>
                                @elseif ($u->isCentralUser())
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border mt-1">
                                        <i class="fa-solid fa-building me-1"></i>কেন্দ্রীয় দপ্তর (বিকেসি)
                                    </span>
                                @else
                                    <small class="text-muted d-block">{{ $u->office_name ?: 'বিউবো' }}</small>
                                @endif
                            </td>
                            <td>
                                @foreach ($u->roles as $r)
                                    @if (str_starts_with($r->name, 'rao_'))
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success me-1 mb-1">
                                            <i class="fa-solid fa-location-dot me-1"></i>{{ $r->display_name_bn ?: $r->display_name }}
                                        </span>
                                    @elseif (str_starts_with($r->name, 'bkc_') || $r->name === 'dir_accounts' || $r->name === 'director_accounts' || $r->name === 'system_admin')
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary me-1 mb-1">
                                            <i class="fa-solid fa-shield-halved me-1"></i>{{ $r->display_name_bn ?: $r->display_name }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border me-1 mb-1">
                                            {{ $r->display_name_bn ?: $r->display_name }}
                                        </span>
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                @if ($u->isSystemAdmin() || $u->isDirectorAccounts())
                                    <span class="badge bg-light text-dark border">সার্বিক বিউবো (All Offices)</span>
                                @else
                                    @forelse ($u->costCenters as $cc)
                                        <span class="badge bg-light text-dark border me-1 mb-1" title="{{ $cc->name_bn }}">{{ $cc->code }}</span>
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

<!-- Edit User Modals -->
@foreach ($users as $u)
    <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ route('users.update', $u) }}" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-user-pen me-2 text-primary"></i>ব্যবহারকারী তথ্য ও ভূমিকা সম্পাদনা</h5>
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
                            <label class="form-label small fw-semibold text-dark">আঞ্চলিক হিসাব দপ্তর (আহিদ / RAO)</label>
                            <select name="regional_accounts_office_id" class="form-select">
                                <option value="">-- কেন্দ্রীয় দপ্তর / কোনো RAO নির্ধারিত নেই --</option>
                                @foreach ($raos as $r)
                                    <option value="{{ $r->id }}" @selected($u->regional_accounts_office_id == $r->id)>
                                        [{{ $r->code }}] {{ $r->name_bn }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark">দপ্তরের নাম (Office Name)</label>
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
                        <div class="col-md-6 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_switch_{{ $u->id }}" @checked($u->is_active)>
                                <label class="form-check-label fw-semibold text-dark" for="active_switch_{{ $u->id }}">সক্রিয় অ্যাকাউন্ট (Active)</label>
                            </div>
                        </div>

                        <!-- Roles Section -->
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-dark mb-1">
                                নির্ধারিত ভূমিকা ও এক্সেস লেভেল (Role Assignment) <span class="text-danger">*</span>
                            </label>
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="row g-3">
                                    <!-- RAO Roles -->
                                    <div class="col-md-6">
                                        <div class="fw-bold text-success small mb-2 border-bottom pb-1">
                                            <i class="fa-solid fa-location-dot me-1"></i>আঞ্চলিক হিসাব দপ্তর (RAO) ভূমিকা
                                        </div>
                                        @foreach ($roles->filter(fn($r) => str_starts_with($r->name, 'rao_')) as $r)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $r->id }}" id="role_{{ $u->id }}_{{ $r->id }}" @checked($u->roles->contains('id', $r->id))>
                                                <label class="form-check-label small fw-semibold text-dark" for="role_{{ $u->id }}_{{ $r->id }}">
                                                    {{ $r->display_name_bn ?: $r->display_name }}
                                                </label>
                                                <div class="text-muted" style="font-size: 11px;">{{ $r->description }}</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Central Roles -->
                                    <div class="col-md-6">
                                        <div class="fw-bold text-primary small mb-2 border-bottom pb-1">
                                            <i class="fa-solid fa-shield-halved me-1"></i>কেন্দ্রীয় ও বিকেসি ভূমিকা (Central Roles)
                                        </div>
                                        <div style="max-height: 220px; overflow-y: auto;">
                                            @foreach ($roles->reject(fn($r) => str_starts_with($r->name, 'rao_')) as $r)
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $r->id }}" id="role_{{ $u->id }}_{{ $r->id }}" @checked($u->roles->contains('id', $r->id))>
                                                    <label class="form-check-label small fw-semibold text-dark" for="role_{{ $u->id }}_{{ $r->id }}">
                                                        {{ $r->display_name_bn ?: $r->display_name }}
                                                    </label>
                                                    <div class="text-muted" style="font-size: 11px;">{{ $r->description }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                        <input type="text" name="username" class="form-control" placeholder="যেমন: 140012345" required>
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
                        <input type="text" name="designation" class="form-control" placeholder="যেমন: সহকারী পরিচালক (হিসাব)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">আঞ্চলিক হিসাব দপ্তর (আহিদ / RAO)</label>
                        <select name="regional_accounts_office_id" class="form-select">
                            <option value="">-- কেন্দ্রীয় দপ্তর / কোনো RAO নির্ধারিত নেই --</option>
                            @foreach ($raos as $r)
                                <option value="{{ $r->id }}">
                                    [{{ $r->code }}] {{ $r->name_bn }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">দপ্তরের নাম (Office Name)</label>
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

                    <!-- Roles Assignment -->
                    <div class="col-12">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            নির্ধারিত ভূমিকা (Role Assignment) <span class="text-danger">*</span>
                        </label>
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="row g-3">
                                <!-- RAO Roles -->
                                <div class="col-md-6">
                                    <div class="fw-bold text-success small mb-2 border-bottom pb-1">
                                        <i class="fa-solid fa-location-dot me-1"></i>আঞ্চলিক হিসাব দপ্তর (RAO) ভূমিকা
                                    </div>
                                    @foreach ($roles->filter(fn($r) => str_starts_with($r->name, 'rao_')) as $r)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $r->id }}" id="new_role_{{ $r->id }}">
                                            <label class="form-check-label small fw-semibold text-dark" for="new_role_{{ $r->id }}">
                                                {{ $r->display_name_bn ?: $r->display_name }}
                                            </label>
                                            <div class="text-muted" style="font-size: 11px;">{{ $r->description }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Central Roles -->
                                <div class="col-md-6">
                                    <div class="fw-bold text-primary small mb-2 border-bottom pb-1">
                                        <i class="fa-solid fa-shield-halved me-1"></i>কেন্দ্রীয় ও বিকেসি ভূমিকা (Central Roles)
                                    </div>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        @foreach ($roles->reject(fn($r) => str_starts_with($r->name, 'rao_')) as $r)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="role_ids[]" value="{{ $r->id }}" id="new_role_{{ $r->id }}">
                                                <label class="form-check-label small fw-semibold text-dark" for="new_role_{{ $r->id }}">
                                                    {{ $r->display_name_bn ?: $r->display_name }}
                                                </label>
                                                <div class="text-muted" style="font-size: 11px;">{{ $r->description }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
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

                <div class="mb-3">
                    <label class="form-label small fw-semibold">অ্যাসাইনকৃত ব্যাংক হিসাবসমূহ:</label>
                    <div class="p-2 border rounded-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                        <div class="row g-2">
                            @foreach ($bankAccounts as $ba)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="bank_account_ids[]" value="{{ $ba->id }}" id="nba_{{ $ba->id }}" class="form-check-input">
                                        <label class="form-check-label small" for="nba_{{ $ba->id }}">
                                            {{ $ba->bank_name }} ({{ $ba->account_number }})
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