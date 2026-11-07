@extends('layouts.app')

@section('title', 'কস্ট সেন্টার ব্যবস্থাপনা | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-sitemap text-success me-2"></i>কস্ট সেন্টার ব্যবস্থাপনা (Cost Centers)
            </h4>
            <p class="text-muted small mb-0">বিউবোর দপ্তর ও কেন্দ্রসমূহ পরিচালনা, ইউজার অ্যাসাইন এবং এক্সেল আপলোড-ডাউনলোড</p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <button class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fa-solid fa-plus me-1"></i>নতুন কস্ট সেন্টার
            </button>
            <a href="{{ route('cost_centers.export') }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
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
                        <th style="width: 15%;">কোড</th>
                        <th style="width: 45%;">দপ্তর / কস্ট সেন্টারের নাম</th>
                        <th style="width: 25%;">অ্যাসাইনকৃত ব্যবহারকারী (Users)</th>
                        <th style="width: 15%; text-align: end;">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($costCenters as $cc)
                        <tr>
                            <td class="fw-bold text-success">{{ $cc->code }}</td>
                            <td>
                                <strong class="text-dark">{{ $cc->name_bn }}</strong>
                                <small class="text-muted d-block">{{ $cc->name }}</small>
                            </td>
                            <td>
                                @forelse ($cc->users as $u)
                                    <span class="badge bg-success bg-opacity-10 text-success me-1 mb-1">
                                        <i class="fa-solid fa-user me-1"></i>{{ $u->name_bn ?: $u->name }}
                                    </span>
                                @empty
                                    <span class="text-muted small">কোনো ইউজার অ্যাসাইন নেই</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal{{ $cc->id }}" title="ইউজার দায়িত্ব বণ্টন">
                                        <i class="fa-solid fa-user-plus"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $cc->id }}" title="সম্পাদনা">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">কোনো কস্ট সেন্টার পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($costCenters->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $costCenters->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
</div>

<!-- Edit & Assign Modals (Outside table and card) -->
@foreach ($costCenters as $cc)
    <!-- Edit Modal -->
    <div class="modal fade" id="editModal{{ $cc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('cost_centers.update', $cc) }}" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i>কস্ট সেন্টার সম্পাদনা</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">কোড <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ $cc->code }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">বাংলা নাম <span class="text-danger">*</span></label>
                        <input type="text" name="name_bn" class="form-control" value="{{ $cc->name_bn }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">ইংরেজি নাম <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $cc->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">অবস্থান (Location)</label>
                        <input type="text" name="location" class="form-control" value="{{ $cc->location }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">জোন / বিভাগ</label>
                        <input type="text" name="division_or_zone" class="form-control" value="{{ $cc->division_or_zone }}">
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
    <div class="modal fade" id="assignModal{{ $cc->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('cost_centers.assign_users', $cc) }}" class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                @csrf
                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark mb-0"><i class="fa-solid fa-user-plus me-2 text-success"></i>ব্যবহারকারী অ্যাসাইন - {{ $cc->name_bn }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <p class="small text-muted mb-3">যেসব ব্যবহারকারীকে এই কস্ট সেন্টারের হিসাব প্রস্তুত ও দেখার অনুমতি দিতে চান তাদের নির্বাচন করুন:</p>
                    <div class="d-flex flex-column gap-2" style="max-height: 300px; overflow-y: auto;">
                        @foreach ($users as $u)
                            <div class="form-check p-2 border rounded-3 bg-light">
                                <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" id="user_{{ $cc->id }}_{{ $u->id }}" class="form-check-input ms-0 me-2" @checked($cc->users->contains('id', $u->id))>
                                <label class="form-check-label fw-medium text-dark" for="user_{{ $cc->id }}_{{ $u->id }}" style="cursor: pointer;">
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

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('cost_centers.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-plus-circle me-2 text-success"></i>নতুন কস্ট সেন্টার তৈরি</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">কোড (Code) <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" placeholder="যেমন: CC-111" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">বাংলা নাম <span class="text-danger">*</span></label>
                    <input type="text" name="name_bn" class="form-control" placeholder="যেমন: বিতরণ বিভাগ, বরিশাল" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">ইংরেজি নাম <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="যেমন: Distribution Division, Barishal" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">অবস্থান (Location)</label>
                    <input type="text" name="location" class="form-control" placeholder="যেমন: Band Road, Barishal">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">জোন / বিভাগ</label>
                    <input type="text" name="division_or_zone" class="form-control" placeholder="যেমন: Barishal Zone">
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
        <form method="POST" action="{{ route('cost_centers.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-arrow-up me-2 text-primary"></i>এক্সেল / সিএসভি ফাইল আপলোড</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    আপনার তৈরি এক্সেল বা সিএসভি ফাইল আপলোড করে এক ক্লিকে সকল কস্ট সেন্টার ইম্পোর্ট বা আপডেট করতে পারেন।
                </p>
                <div class="mb-3">
                    <a href="{{ route('cost_centers.template') }}" class="btn btn-sm btn-outline-info rounded-pill mb-3">
                        <i class="fa-solid fa-download me-1"></i>নমুনা সিএসভি টেমপ্লেট ডাউনলোড করুন
                    </a>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">এক্সেল বা সিএসভি ফাইল নির্বাচন করুন <span class="text-danger">*</span></label>
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
