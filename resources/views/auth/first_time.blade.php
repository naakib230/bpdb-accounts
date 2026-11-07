@extends('layouts.app')

@section('title', 'প্রথমবার অ্যাকাউন্ট অ্যাক্টিভেশন | বিউবো অ্যাকাউন্টস পোর্টাল')

@section('content')
<div class="container-xxl py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-xl-5 bg-white">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/bpdb-logo.png') }}" alt="বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড লোগো" width="85" height="85" class="d-block mx-auto mb-3">
                    <h4 class="fw-bold text-success mb-1">বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড</h4>
                    <p class="text-muted small mb-0">অ্যাকাউন্টস পোর্টাল - প্রথমবার অ্যাকাউন্ট অ্যাক্টিভেশন</p>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @php
                    $verification = session('first_time_verification');
                @endphp

                @if(! $verification)
                    <!-- ধাপ ১: ERP অথবা NID দিয়ে পরিচয় ও পদবী যাচাই -->
                    <div class="alert alert-light border small text-secondary mb-4">
                        <i class="fa-solid fa-circle-info text-primary me-1"></i>
                        বিউবো কেন্দ্রীয় ERP হতে আপনার পদবী ও পরিচয় যাচাই করে নতুন পাসওয়ার্ড নির্ধারণ করুন। শুধুমাত্র অর্থ ও হিসাব ক্যাডারের অনুমোদিত পদবীধারী কর্মকর্তাবৃন্দ যুক্ত হতে পারবেন।
                    </div>

                    <form method="POST" action="{{ route('first_time.verify') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="identifier" class="form-label fw-semibold text-secondary small">ইউজার আইডি (ERP No. অথবা জাতীয় পরিচয়পত্র / NID)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-id-badge"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0 @error('identifier') is-invalid @enderror" id="identifier" name="identifier" value="{{ old('identifier') }}" placeholder="যেমন: 140011111 অথবা 1985..." required autofocus>
                            </div>
                            @error('identifier')
                                <div class="invalid-feedback d-block small mt-1 text-danger fw-semibold">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted">আপনার ৯ ডিজিটের বিউবো ERP নম্বর অথবা ১০/১৩/১৭ ডিজিটের NID প্রদান করুন।</div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold rounded-3 shadow-sm mb-3">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>পরিচয় ও পদবী যাচাই করুন
                        </button>
                    </form>
                @else
                    <!-- ধাপ ২: যাচাইকৃত তথ্যের সারসংক্ষেপ ও পাসওয়ার্ড নির্ধারণ -->
                    <div class="card bg-light border-success border-2 rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-circle-check text-success fs-4 me-2"></i>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">{{ $verification['name_bn'] ?: $verification['name_en'] }}</h6>
                                <small class="text-muted">ERP No: <strong class="text-dark">{{ $verification['erp_no'] }}</strong></small>
                            </div>
                        </div>
                        <div class="border-top pt-2 mt-1 small">
                            <div class="row g-1">
                                <div class="col-4 text-muted">পদবী:</div>
                                <div class="col-8 fw-semibold text-dark">{{ $verification['designation'] }} (কোড: {{ $verification['designation_code'] }})</div>
                                <div class="col-4 text-muted">কর্মস্থল:</div>
                                <div class="col-8 text-dark">{{ $verification['office_name'] }}</div>
                                <div class="col-4 text-muted">ক্যাডার:</div>
                                <div class="col-8 text-success fw-bold"><i class="fa-solid fa-check-double me-1"></i>Finance Cadre (অনুমোদিত)</div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('first_time.activate') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-secondary small">নতুন পাসওয়ার্ড (New Password)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="কমপক্ষে ৬ অক্ষরের পাসওয়ার্ড" required autofocus>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block small mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-semibold text-secondary small">পাসওয়ার্ড নিশ্চিতকরণ (Confirm Password)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control border-start-0 ps-0" id="password_confirmation" name="password_confirmation" placeholder="পাসওয়ার্ড পুনরায় লিখুন" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold rounded-3 shadow-sm mb-3">
                            <i class="fa-solid fa-shield-check me-2"></i>অ্যাকাউন্ট সক্রিয় ও পাসওয়ার্ড সংরক্ষণ করুন
                        </button>
                    </form>
                @endif

                <div class="border-top pt-3 text-center">
                    <a href="{{ route('login') }}" class="text-decoration-none small text-success fw-semibold">
                        <i class="fa-solid fa-arrow-left me-1"></i>লগইন পেজে ফিরে যান
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
