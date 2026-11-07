@extends('layouts.app')

@section('title', 'আমার প্রোফাইল | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-user-circle text-success me-2"></i>আমার প্রোফাইল ও নিরাপত্তা
        </h4>
        <p class="text-muted small mb-0">ব্যবহারকারী তথ্য হালনাগাদ ও পাসওয়ার্ড পরিবর্তন</p>
    </div>

    <div class="row g-4">
        <!-- Profile Update Card -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="position-relative flex-shrink-0">
                        @if ($user->photo_url)
                            <img src="{{ $user->photo_url }}" alt="{{ $user->name_bn ?: $user->name }}" class="rounded-circle border border-2 border-success shadow-sm object-fit-cover" style="width: 72px; height: 72px;">
                        @else
                            <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center border border-2 border-success" style="width: 72px; height: 72px;">
                                <i class="fa-solid fa-user fs-2"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">{{ $user->name_bn ?: $user->name }}</h5>
                        <div class="text-muted small mb-1">{{ $user->designation ?: 'হিসাব কর্মকর্তা' }} (কোড: {{ $user->designation_code ?: 'N/A' }})</div>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                            ERP No: {{ $user->erp_no ?: $user->username }}
                        </span>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-3">ব্যক্তিগত ও দাপ্তরিক তথ্য</h5>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ইউজারনেম / ERP</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->username }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">বর্তমান ভূমিকা (Active Role)</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->activeRole()?->display_name_bn ?: $user->activeRole()?->display_name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">পূর্ণ নাম (বাংলা)</label>
                            <input type="text" name="name_bn" class="form-control" value="{{ old('name_bn', $user->name_bn) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">পূর্ণ নাম (ইংরেজি) <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ইমেইল</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">মোবাইল নম্বর</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top">
                            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-lock text-warning me-2"></i>পাসওয়ার্ড পরিবর্তন (ঐচ্ছিক)</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">নতুন পাসওয়ার্ড</label>
                            <input type="password" name="password" class="form-control" placeholder="নতুন পাসওয়ার্ড লিখুন">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">পাসওয়ার্ড নিশ্চিতকরণ</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="পুনরায় নতুন পাসওয়ার্ড লিখুন">
                        </div>

                        <div class="col-12 text-end mt-4">
                            <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                                <i class="fa-solid fa-check me-1"></i>তথ্য সংরক্ষণ করুন
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Assigned Scope Card -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-sitemap text-primary me-2"></i>অ্যাসাইনকৃত কস্ট সেন্টারসমূহ
                </h6>
                @if ($user->isSystemAdmin() || $user->isDirectorAccounts())
                    <div class="alert alert-success small mb-0 border-0">
                        <i class="fa-solid fa-shield-halved me-1"></i>আপনার পদমর্যাদা অনুযায়ী আপনি বিউবোর <strong>সকল কস্ট সেন্টারের</strong> হিসাব পরিচালনার অধিকার রাখেন।
                    </div>
                @else
                    <ul class="list-group list-group-flush small">
                        @forelse ($user->costCenters as $cc)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>{{ $cc->name_bn }}</span>
                                <span class="badge bg-light text-dark border">{{ $cc->code }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">কোনো কস্ট সেন্টার নির্ধারিত নেই।</li>
                        @endforelse
                    </ul>
                @endif
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-building-columns text-success me-2"></i>অ্যাসাইনকৃত ব্যাংক হিসাবসমূহ
                </h6>
                @if ($user->isSystemAdmin() || $user->isDirectorAccounts())
                    <div class="alert alert-success small mb-0 border-0">
                        <i class="fa-solid fa-shield-halved me-1"></i>আপনার পদমর্যাদা অনুযায়ী আপনি বিউবোর <strong>সকল ব্যাংক হিসাবের</strong> কার্যক্রম পরিচালনার অধিকার রাখেন।
                    </div>
                @else
                    <ul class="list-group list-group-flush small">
                        @forelse ($user->bankAccounts as $ba)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <div>
                                    <div class="fw-bold">{{ $ba->bank_name }}</div>
                                    <small class="text-muted">{{ $ba->account_title }}</small>
                                </div>
                                <span class="badge bg-light text-dark border">{{ $ba->account_number }}</span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted px-0">কোনো ব্যাংক হিসাব নির্ধারিত নেই।</li>
                        @endforelse
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
