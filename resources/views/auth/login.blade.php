@extends('layouts.app')

@section('title', 'লগইন | বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড - অ্যাকাউন্টস পোর্টাল')

@section('content')
<div class="container-xxl py-4 py-lg-5">
    <!-- রানিং ব্যানার / Marquee Ticker -->
    <div class="d-flex align-items-center rounded-pill bg-white shadow-sm border p-1 px-3 mb-4 mb-lg-5">
        <div class="flex-shrink-0 me-3">
            <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="fa-solid fa-bullhorn fa-beat-fade me-1"></i>
                <span>জরুরি নির্দেশনা</span>
            </span>
        </div>
        <div class="flex-grow-1 overflow-hidden">
            <div class="fw-semibold text-dark text-truncate">
                <i class="fa-solid fa-circle-exclamation text-danger me-1"></i>
                বিউবো অ্যাকাউন্টস পোর্টালে স্বাগত। শুধুমাত্র বিউবো অর্থ ও হিসাব ক্যাডারের অনুমোদিত কর্মকর্তা/কর্মচারীগণ এবং সিস্টেম অ্যাডমিনিস্ট্রেটরগণ এই পোর্টালে লগইন করতে পারবেন।
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- লগইন কার্ড -->
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-xl-5 text-center bg-white">
                <div>
                    <div class="mb-4">
                        <img src="{{ asset('images/bpdb-logo.png') }}" alt="বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড লোগো" width="90" height="90" class="d-block mx-auto mb-3">
                        <h4 class="fw-bold text-success mb-1">বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড</h4>
                        <p class="text-muted small mb-0">অ্যাকাউন্টস ও ফিন্যান্সিয়াল ম্যানেজমেন্ট পোর্টাল</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show text-start small mb-3" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show text-start small mb-3" role="alert">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show text-start small mb-3" role="alert">
                            <i class="fa-solid fa-circle-xmark me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="text-start">
                        @csrf
                        <div class="mb-3">
                            <label for="loginIdentifier" class="form-label fw-semibold text-secondary small">ইউজার আইডি (ERP No. অথবা জাতীয় পরিচয়পত্র / NID)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-id-badge"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0 @error('identifier') is-invalid @enderror" id="loginIdentifier" name="identifier" value="{{ old('identifier') }}" placeholder="যেমন: 140011111 অথবা 1985..." required autofocus autocomplete="username">
                            </div>
                            @error('identifier')
                                <div class="invalid-feedback d-block small mt-1 text-danger fw-semibold">{{ $message }}</div>
                            @enderror
                            <div class="form-text small text-muted">১০, ১৩ অথবা ১৭ ডিজিটের NID নম্বর দিয়েও লগইন করা যাবে।</div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold text-secondary small">পাসওয়ার্ড (Password)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0 @error('password') is-invalid @enderror" id="password" name="password" placeholder="পাসওয়ার্ড লিখুন" required autocomplete="current-password">
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password" onclick="togglePass()">
                                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block small mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label small text-secondary" for="remember">তথ্য মনে রাখুন</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold rounded-3 shadow-sm">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>লগইন করুন
                        </button>
                    </form>

                    <div class="mt-4 pt-3 border-top text-center">
                        <p class="small text-muted mb-2">প্রথমবার লগইন করছেন বা পাসওয়ার্ড সেট করেননি?</p>
                        <a href="{{ route('first_time') }}" class="btn btn-outline-success btn-sm px-3 rounded-pill fw-semibold">
                            <i class="fa-solid fa-user-plus me-1"></i>প্রথমবার অ্যাকাউন্ট অ্যাক্টিভেশন
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted">
                        © {{ date('Y') }} বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড (বিউবো) | সর্বস্বত্ব সংরক্ষিত
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePass() {
        var input = document.getElementById('password');
        var icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endpush
@endsection
