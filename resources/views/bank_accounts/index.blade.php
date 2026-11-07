@extends('layouts.app')

@section('title', 'ব্যাংক হিসাব ব্যবস্থাপনা | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-money-check-dollar text-success me-2"></i>ব্যাংক হিসাব ব্যবস্থাপনা (Bank Accounts)
            </h4>
            <p class="text-muted small mb-0">বিউবোর ব্যাংক হিসাবসমূহ পরিচালনা, ইউজার অ্যাসাইন এবং এক্সেল আপলোড-ডাউনলোড</p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <button class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fa-solid fa-plus me-1"></i>নতুন ব্যাংক হিসাব
            </button>
            <a href="{{ route('bank_accounts.export') }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i>এক্সেল ডাউনলোড
            </a>
            <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa-solid fa-file-arrow-up me-1"></i>এক্সেল আপলোড
            </button>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 20%;">ব্যাংকের নাম ও শাখা</th>
                        <th style="width: 15%;">হিসাব নম্বর</th>
                        <th style="width: 15%;">শিরোনাম ও ধরন</th>
                        <th style="width: 12%;">জিএল কোড</th>
                        <th style="width: 13%; text-align: right;">বর্তমান স্থিতি (৳)</th>
                        <th style="width: 15%;">দায়িত্বপ্রাপ্ত ইউজার</th>
                        <th style="width: 10%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bankAccounts as $acc)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $acc->bank_name }}</strong>
                                <small class="text-muted d-block">{{ $acc->branch_name }}</small>
                            </td>
                            <td class="fw-bold text-success">{{ $acc->account_number }}</td>
                            <td>
                                <div class="small fw-semibold">{{ $acc->account_title }}</div>
                                <span class="badge bg-light text-dark border">{{ $acc->account_type }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                    {{ $acc->chartOfAccount?->account_code }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-success">৳ {{ number_format($acc->opening_balance, 2) }}</td>
                            <td>
                                @forelse ($acc->users as $u)
                                    <span class="badge bg-success bg-opacity-10 text-success me-1 mb-1">
                                        <i class="fa-solid fa-user me-1"></i>{{ $u->name_bn ?: $u->name }}
                                    </span>
                                @empty
                                    <span class="text-muted small">কোনো ইউজার অ্যাসাইন নেই</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal{{ $acc->id }}" title="ইউজার দায়িত্ব বণ্টন">
                                        <i class="fa-solid fa-user-plus"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $acc->id }}" title="সম্পাদনা">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">কোনো ব্যাংক হিসাব পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($bankAccounts->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $bankAccounts->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- Edit & Assign Modals (Outside table and card) -->
@foreach ($bankAccounts as $acc)
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal{{ $acc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('bank_accounts.update', $acc) }}" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>ব্যাংক হিসাব সম্পাদনা</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">ব্যাংকের নাম <span class="text-danger">*</span></label>
                        <input type="text" name="bank_name" class="form-control" value="{{ $acc->bank_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">শাখার নাম <span class="text-danger">*</span></label>
                        <input type="text" name="branch_name" class="form-control" value="{{ $acc->branch_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">হিসাব নম্বর <span class="text-danger">*</span></label>
                        <input type="text" name="account_number" class="form-control" value="{{ $acc->account_number }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">হিসাবের শিরোনাম <span class="text-danger">*</span></label>
                        <input type="text" name="account_title" class="form-control" value="{{ $acc->account_title }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">হিসাবের ধরন <span class="text-danger">*</span></label>
                        <select name="account_type" class="form-select" required>
                            <option value="Current" @selected($acc->account_type === 'Current')>Current</option>
                            <option value="SND" @selected($acc->account_type === 'SND')>SND</option>
                            <option value="STD" @selected($acc->account_type === 'STD')>STD</option>
                            <option value="Savings" @selected($acc->account_type === 'Savings')>Savings</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">জিএল চার্ট অব একাউন্ট <span class="text-danger">*</span></label>
                        <select name="chart_of_account_id" class="form-select" required>
                            @foreach ($bankCoas as $c)
                                <option value="{{ $c->id }}" @selected($acc->chart_of_account_id == $c->id)>
                                    {{ $c->account_code }} - {{ $c->account_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">আপডেট করুন</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Assign Users Modal -->
    <div class="modal fade" id="assignModal{{ $acc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('bank_accounts.assign_users', $acc) }}" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-user-plus me-2 text-success"></i>ব্যবহারকারী অ্যাসাইন - {{ $acc->account_number }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <p class="small text-muted mb-3">যেসব ব্যবহারকারীকে এই ব্যাংক হিসাব হতে লেনদেন বা স্টেটমেন্ট দেখার অনুমতি দিতে চান তাদের নির্বাচন করুন:</p>
                    <div class="d-flex flex-column gap-2" style="max-height: 300px; overflow-y: auto;">
                        @foreach ($users as $u)
                            <div class="form-check p-2 border rounded-3 bg-light">
                                <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="bankuser_{{ $acc->id }}_{{ $u->id }}" class="form-check-input ms-0 me-2" @checked($acc->users->contains('id', $u->id))>
                                <label class="form-check-label fw-medium text-dark" for="bankuser_{{ $acc->id }}_{{ $u->id }}" style="cursor: pointer;">
                                    {{ $u->name_bn ?: $u->name }} ({{ $u->username }})
                                    <small class="text-muted d-block">{{ $u->designation ?: 'হিসাব শাখা' }}</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer bg-light border-top px-4 py-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">অ্যাসাইন সংরক্ষণ করুন</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('bank_accounts.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2 text-success"></i>নতুন ব্যাংক হিসাব যুক্তকরণ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">ব্যাংকের নাম <span class="text-danger">*</span></label>
                    <input type="text" name="bank_name" class="form-control" placeholder="যেমন: Sonali Bank PLC" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">শাখার নাম <span class="text-danger">*</span></label>
                    <input type="text" name="branch_name" class="form-control" placeholder="যেমন: WAPDA Bhaban Corporate Branch" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">হিসাব নম্বর <span class="text-danger">*</span></label>
                    <input type="text" name="account_number" class="form-control" placeholder="যেমন: 0010234567890" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">হিসাবের শিরোনাম <span class="text-danger">*</span></label>
                    <input type="text" name="account_title" class="form-control" placeholder="যেমন: BPDB Central Revenue Account" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">হিসাবের ধরন <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select" required>
                        <option value="Current">Current</option>
                        <option value="SND">SND</option>
                        <option value="STD">STD</option>
                        <option value="Savings">Savings</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">জিএল চার্ট অব একাউন্ট <span class="text-danger">*</span></label>
                    <select name="chart_of_account_id" class="form-select" required>
                        @foreach ($bankCoas as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->account_code }} - {{ $c->account_name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">প্রারম্ভিক ব্যালেন্স (৳) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="0.00" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                <button type="submit" class="btn btn-success rounded-pill">সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('bank_accounts.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-arrow-up me-2 text-primary"></i>এক্সেল / সিএসভি ফাইল আপলোড</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    আপনার তৈরি এক্সেল বা সিএসভি ফাইল আপলোড করে এক ক্লিকে সকল ব্যাংক হিসাব ইম্পোর্ট বা আপডেট করতে পারেন।
                </p>
                <div class="mb-3">
                    <a href="{{ route('bank_accounts.template') }}" class="btn btn-sm btn-outline-info rounded-pill mb-3">
                        <i class="fa-solid fa-download me-1"></i>নমুনা সিএসভি টেমপ্লেট ডাউনলোড করুন
                    </a>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">ফাইল নির্বাচন করুন <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                <button type="submit" class="btn btn-primary rounded-pill">আপলোড ও ইম্পোর্ট</button>
            </div>
        </form>
    </div>
</div>
@endsection
