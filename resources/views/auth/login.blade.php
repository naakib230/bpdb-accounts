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
                বিউবো অ্যাকাউন্টস পোর্টালে স্বাগত। ক্যাশ ও জার্নাল ভাউচার পোস্টিং, লেজার, ট্রায়াল ব্যালেন্স এবং বছর সমাপনী কাজের জন্য আপনার নির্ধারিত ভূমিকা ও ইউজারনেম দিয়ে লগইন করুন।
            </div>
        </div>
    </div>

    <div class="row justify-content-center align-items-stretch g-4 g-xl-5">
        <!-- বাম পাশের কার্ড: ব্যবহারকারী ম্যানুয়াল ও ভূমিকা নির্দেশিকা -->
        <div class="col-12 col-lg-6 order-2 order-lg-1">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-xl-5 bg-white h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
                        <div class="me-2 text-success fs-3">
                            <i class="fa-solid fa-book-bookmark"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">অ্যাকাউন্টিং ম্যানুয়াল ও সহায়িকা</h5>
                            <small class="text-muted">BPDB Accounts Portal Guidelines & Hierarchy</small>
                        </div>
                    </div>

                    <p class="text-secondary small mb-4">
                        বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ডের আর্থিক স্বচ্ছতা ও ডাবল-এন্ট্রি বুক-কিপিং বাস্তবায়নে নির্ধারিত পদমর্যাদা অনুযায়ী অ্যাক্সেস নিশ্চিত করা হয়েছে:
                    </p>

                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-success me-2">পরিচালক (হিসাব)</span>
                                <span class="small text-muted">Director of Accounts</span>
                            </div>
                            <small class="text-secondary">সার্বিক আর্থিক বিবরণী, উদ্বৃত্তপত্র, বছর সমাপনী এবং কস্ট সেন্টার ভিত্তিক বাজেট পর্যালোচনা ও চূড়ান্ত অনুমোদন।</small>
                        </div>

                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-primary me-2">হিসাব কর্মকর্তা (RAO)</span>
                                <span class="small text-muted">Accounts Officer</span>
                            </div>
                            <small class="text-secondary">আঞ্চলিক দপ্তর ও বিদ্যুৎ কেন্দ্রের ভাউচার অনুমোদন, ব্যাংক সমন্বয় ও লেজার পরীক্ষণ।</small>
                        </div>

                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge bg-warning text-dark me-2">হিসাবরক্ষক / ক্যাশিয়ার</span>
                                <span class="small text-muted">Accountant / Cashier</span>
                            </div>
                            <small class="text-secondary">দৈনন্দিন ক্যাশ ভাউচার ও জার্নাল ভাউচার পোস্টিং, বিল এন্ট্রি ও ব্যাংক স্টেটমেন্ট এন্ট্রি।</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <div class="bg-light p-3 rounded-3 border small">
                        <strong class="text-dark d-block mb-1"><i class="fa-solid fa-key text-success me-1"></i>টেস্ট লগইন তথ্য (Demo Accounts):</strong>
                        <div class="row g-2 text-muted">
                            <div class="col-6"><strong>অ্যাডমিন:</strong> <code>admin</code> (পাস: <code>password123</code>)</div>
                            <div class="col-6"><strong>পরিচালক:</strong> <code>director</code> (পাস: <code>password123</code>)</div>
                            <div class="col-6"><strong>হিসাব কর্মকর্তা:</strong> <code>officer_rao</code> (পাস: <code>password123</code>)</div>
                            <div class="col-6"><strong>হিসাবরক্ষক:</strong> <code>accountant1</code> (পাস: <code>password123</code>)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ডান পাশের কার্ড: লগইন ফর্ম -->
        <div class="col-12 col-lg-5 order-1 order-lg-2">
            <div class="card shadow-sm border-0 rounded-4 p-4 p-xl-5 text-center bg-white h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="mb-4">
                        <img src="{{ asset('images/bpdb-logo.png') }}" alt="বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড লোগো" width="90" height="90" class="d-block mx-auto mb-3">
                        <h4 class="fw-bold text-success mb-1">বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড</h4>
                        <p class="text-muted small mb-0">অ্যাকাউন্টস ও ফিন্যান্সিয়াল ম্যানেজমেন্ট পোর্টাল</p>
                    </div>

                    <form method="POST" action="{{ route('login.store') }}" class="text-start">
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold text-secondary small">ইউজারনেম / ERP নম্বর</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" value="{{ old('username', 'admin') }}" placeholder="ইউজারনেম লিখুন" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold text-secondary small">পাসওয়ার্ড</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" value="password123" placeholder="পাসওয়ার্ড লিখুন" required>
                                <button type="button" class="input-group-text bg-light border-start-0 text-muted toggle-password" onclick="togglePass()">
                                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
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
