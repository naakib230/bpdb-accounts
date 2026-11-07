@extends('layouts.app')

@section('title', 'চার্ট অব অ্যাকাউন্টস (COA) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-folder-tree text-success me-2"></i>চার্ট অব অ্যাকাউন্টস (Chart of Accounts)
            </h4>
            <p class="text-muted small mb-0">বিউবোর প্রমিত হিসাব কোড কাঠামো, হিসাব খাত ব্যবস্থাপনা, ইউজার অ্যাসাইন এবং এক্সেল আপলোড-ডাউনলোড</p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <button class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fa-solid fa-plus me-1"></i>নতুন হিসাব কোড
            </button>
            <a href="{{ route('chart_of_accounts.export') }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i>এক্সেল ডাউনলোড
            </a>
            <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fa-solid fa-file-arrow-up me-1"></i>এক্সেল আপলোড
            </button>
        </div>
    </div>

    <!-- Category Filter Pills -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('chart_of_accounts.index') }}" class="btn btn-sm rounded-pill {{ empty($type) ? 'btn-success' : 'btn-outline-secondary bg-white' }}">
            সকল হিসাব (All Accounts)
        </a>
        <a href="{{ route('chart_of_accounts.index', ['type' => 'asset']) }}" class="btn btn-sm rounded-pill {{ $type === 'asset' ? 'btn-success' : 'btn-outline-secondary bg-white' }}">
            সম্পদ (Assets)
        </a>
        <a href="{{ route('chart_of_accounts.index', ['type' => 'liability']) }}" class="btn btn-sm rounded-pill {{ $type === 'liability' ? 'btn-success' : 'btn-outline-secondary bg-white' }}">
            দায় (Liabilities)
        </a>
        <a href="{{ route('chart_of_accounts.index', ['type' => 'equity']) }}" class="btn btn-sm rounded-pill {{ $type === 'equity' ? 'btn-success' : 'btn-outline-secondary bg-white' }}">
            মালিকানা স্বত্ব (Equity)
        </a>
        <a href="{{ route('chart_of_accounts.index', ['type' => 'revenue']) }}" class="btn btn-sm rounded-pill {{ $type === 'revenue' ? 'btn-success' : 'btn-outline-secondary bg-white' }}">
            রাজস্ব / আয় (Revenue)
        </a>
        <a href="{{ route('chart_of_accounts.index', ['type' => 'expense']) }}" class="btn btn-sm rounded-pill {{ $type === 'expense' ? 'btn-success' : 'btn-outline-secondary bg-white' }}">
            ব্যয় / খরচ (Expenses)
        </a>
    </div>

    <!-- Table Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 12%;">হিসাব কোড</th>
                        <th style="width: 25%;">হিসাব খাতের নাম</th>
                        <th style="width: 10%;">ধরন (Type)</th>
                        <th style="width: 10%;">স্বাভাবিক জের</th>
                        <th style="width: 13%; text-align: right;">প্রারম্ভিক ব্যালেন্স (৳)</th>
                        <th style="width: 18%;">দায়িত্বপ্রাপ্ত ইউজার</th>
                        <th style="width: 12%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $acc)
                        <tr class="{{ ! $acc->is_leaf ? 'table-light fw-bold' : '' }}">
                            <td class="fw-bold {{ ! $acc->is_leaf ? 'text-dark' : 'text-success' }}">
                                {{ $acc->account_code }}
                            </td>
                            <td>
                                <div>{{ $acc->account_name_bn }}</div>
                                <small class="text-muted">{{ $acc->account_name }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ strtoupper($acc->account_type) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $acc->normal_balance === 'debit' ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary' }}">
                                    {{ strtoupper($acc->normal_balance) }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ $acc->opening_balance > 0 ? '৳ ' . number_format($acc->opening_balance, 2) : '-' }}
                            </td>
                            <td>
                                @if ($acc->is_leaf)
                                    @forelse ($acc->users as $u)
                                        <span class="badge bg-success bg-opacity-10 text-success me-1 mb-1">
                                            <i class="fa-solid fa-user me-1"></i>{{ $u->name_bn ?: $u->name }}
                                        </span>
                                    @empty
                                        <span class="text-muted small">সকলের জন্য উন্মুক্ত</span>
                                    @endforelse
                                @else
                                    <span class="text-muted small">মূল খাত (Parent)</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if ($acc->is_leaf)
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal{{ $acc->id }}" title="ইউজার দায়িত্ব বণ্টন">
                                            <i class="fa-solid fa-user-plus"></i>
                                        </button>
                                        <a href="{{ route('ledger.index', ['chart_of_account_id' => $acc->id]) }}" class="btn btn-outline-info" title="খতিয়ান দেখুন">
                                            <i class="fa-solid fa-book-open"></i>
                                        </a>
                                    @endif
                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $acc->id }}" title="সম্পাদনা">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal{{ $acc->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('chart_of_accounts.update', $acc) }}" class="modal-content">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>হিসাব কোড সম্পাদনা</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">হিসাব কোড <span class="text-danger">*</span></label>
                                            <input type="text" name="account_code" class="form-control" value="{{ $acc->account_code }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">বাংলা নাম <span class="text-danger">*</span></label>
                                            <input type="text" name="account_name_bn" class="form-control" value="{{ $acc->account_name_bn }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">ইংরেজি নাম <span class="text-danger">*</span></label>
                                            <input type="text" name="account_name" class="form-control" value="{{ $acc->account_name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">হিসাবের ধরন <span class="text-danger">*</span></label>
                                            <select name="account_type" class="form-select" required>
                                                <option value="asset" @selected($acc->account_type === 'asset')>Asset (সম্পদ)</option>
                                                <option value="liability" @selected($acc->account_type === 'liability')>Liability (দায়)</option>
                                                <option value="equity" @selected($acc->account_type === 'equity')>Equity (মালিকানা স্বত্ব)</option>
                                                <option value="revenue" @selected($acc->account_type === 'revenue')>Revenue (আয়)</option>
                                                <option value="expense" @selected($acc->account_type === 'expense')>Expense (ব্যয়)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">স্বাভাবিক ব্যালেন্স <span class="text-danger">*</span></label>
                                            <select name="normal_balance" class="form-select" required>
                                                <option value="debit" @selected($acc->normal_balance === 'debit')>Debit</option>
                                                <option value="credit" @selected($acc->normal_balance === 'credit')>Credit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                                        <button type="submit" class="btn btn-success rounded-pill">আপডেট করুন</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Assign Users Modal -->
                        <div class="modal fade" id="assignModal{{ $acc->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('chart_of_accounts.assign_users', $acc) }}" class="modal-content">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-success"></i>ব্যবহারকারী দায়িত্ব বণ্টন - {{ $acc->account_code }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="small text-muted mb-3">যেসব ব্যবহারকারীকে এই হিসাব খাতের অধীনে ভাউচার পোস্টিংয়ের অনুমতি দিতে চান তাদের নির্বাচন করুন:</p>
                                        <div class="d-flex flex-column gap-2" style="max-height: 300px; overflow-y: auto;">
                                            @foreach ($users as $u)
                                                <div class="form-check p-2 border rounded-3 bg-light">
                                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="coauser_{{ $acc->id }}_{{ $u->id }}" class="form-check-input ms-0 me-2" @checked($acc->users->contains('id', $u->id))>
                                                    <label class="form-check-label fw-medium text-dark" for="coauser_{{ $acc->id }}_{{ $u->id }}">
                                                        {{ $u->name_bn ?: $u->name }} ({{ $u->username }})
                                                        <small class="text-muted d-block">{{ $u->designation ?: 'হিসাব শাখা' }}</small>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                                        <button type="submit" class="btn btn-success rounded-pill">অ্যাসাইন সংরক্ষণ করুন</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">কোনো হিসাব খাত পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($accounts->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('chart_of_accounts.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2 text-success"></i>নতুন হিসাব কোড তৈরি</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">হিসাব কোড <span class="text-danger">*</span></label>
                    <input type="text" name="account_code" class="form-control" placeholder="যেমন: 502150" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">বাংলা নাম <span class="text-danger">*</span></label>
                    <input type="text" name="account_name_bn" class="form-control" placeholder="যেমন: আইসিটি ও সফটওয়্যার রক্ষণাবেক্ষণ" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">ইংরেজি নাম <span class="text-danger">*</span></label>
                    <input type="text" name="account_name" class="form-control" placeholder="যেমন: ICT & Software Maintenance" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">হিসাবের ধরন <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-select" required>
                        <option value="expense">Expense (ব্যয় / পরিচালনা খরচ)</option>
                        <option value="revenue">Revenue (রাজস্ব / আয়)</option>
                        <option value="asset">Asset (সম্পদ)</option>
                        <option value="liability">Liability (দায়)</option>
                        <option value="equity">Equity (মালিকানা স্বত্ব)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">স্বাভাবিক জের <span class="text-danger">*</span></label>
                    <select name="normal_balance" class="form-select" required>
                        <option value="debit">Debit</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">প্রারম্ভিক ব্যালেন্স (৳)</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="0.00">
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_leaf" value="1" id="is_leaf" class="form-check-input" checked>
                    <label class="form-check-label small" for="is_leaf">এটি সরাসরি পোস্টিংযোগ্য লিফ অ্যাকাউন্ট (Leaf Account)</label>
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
        <form method="POST" action="{{ route('chart_of_accounts.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-arrow-up me-2 text-primary"></i>এক্সেল / সিএসভি ফাইল আপলোড</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    আপনার তৈরি এক্সেল বা সিএসভি ফাইল আপলোড করে এক ক্লিকে সকল হিসাব কোড ইম্পোর্ট বা আপডেট করতে পারেন।
                </p>
                <div class="mb-3">
                    <a href="{{ route('chart_of_accounts.template') }}" class="btn btn-sm btn-outline-info rounded-pill mb-3">
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
