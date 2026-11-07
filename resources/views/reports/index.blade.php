@extends('layouts.app')

@section('title', 'আর্থিক প্রতিবেদন ও বিবরণী কেন্দ্র | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-chart-pie text-success me-2"></i>আর্থিক প্রতিবেদন ও বিবরণী কেন্দ্র (Financial Reports Hub)
        </h4>
        <p class="text-muted small mb-0">বিউবোর উদ্বৃত্তপত্র, আয়-ব্যয় বিবরণী, ব্যাংক ও ক্যাশ খাতা এবং বাজেট বিশ্লেষণ</p>
    </div>

    <div class="row g-4">
        <!-- 1. Balance Sheet -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon green mb-3">
                        <i class="fa-solid fa-landmark"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">আর্থিক অবস্থার বিবরণী (Balance Sheet)</h5>
                    <p class="text-secondary small mb-3">
                        নির্দিষ্ট তারিখ পর্যন্ত বিউবোর সার্বিক সম্পদ (চলতি ও স্থায়ী), দায় এবং সরকারের মূলধন ও পুঞ্জীভূত মালিকানা স্বত্ব বিবরণী।
                    </p>
                </div>
                <div>
                    <a href="{{ route('reports.balance_sheet') }}" class="btn btn-outline-success rounded-pill w-100">
                        বিবরণী দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Income Statement -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon blue mb-3">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">আয়-ব্যয় বিবরণী (Income Statement)</h5>
                    <p class="text-secondary small mb-3">
                        নির্দিষ্ট অর্থবছরে বিদ্যুৎ বিক্রয় রাজস্ব, উৎপাদন ব্যয় (জ্বালানি, আইপিপি ক্রয়) ও পরিচালন ব্যয়ের বিপরীতে নিট উদ্বৃত্ত/ঘাটতি।
                    </p>
                </div>
                <div>
                    <a href="{{ route('reports.income_statement') }}" class="btn btn-outline-primary rounded-pill w-100">
                        বিবরণী দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Bank & Cash Book -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon gold mb-3">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">ব্যাংক ও ক্যাশ খাতা (Cash Book)</h5>
                    <p class="text-secondary small mb-3">
                        সোনালী, জনতা, অগ্রণী ব্যাংক এবং নগদ ক্যাশ তহবিলের জমা-খরচের পূর্ণাঙ্গ খতিয়ান ও চেক নম্বর ভিত্তিক লেনদেন হিসাব।
                    </p>
                </div>
                <div>
                    <a href="{{ route('reports.bank_book') }}" class="btn btn-outline-warning text-dark rounded-pill w-100">
                        খাতা দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. Cost Center Expenditure -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon red mb-3">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">কস্ট সেন্টার ভিত্তিক খরচ বিবরণী</h5>
                    <p class="text-secondary small mb-3">
                        ঘোড়াশাল, সিদ্ধিরগঞ্জ, কর্ণফুলী ও আঞ্চলিক বিদ্যুৎ বিতরণ দপ্তরসমূহের মোট পরিচালন খরচ ও ব্যয়ের অনুপাত পর্যালোচনা।
                    </p>
                </div>
                <div>
                    <a href="{{ route('reports.cost_center_expenditure') }}" class="btn btn-outline-danger rounded-pill w-100">
                        প্রতিবেদন দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 5. Budget vs Actual Variance -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon purple mb-3">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">বাজেট ভ্যারিয়েন্স রিপোর্ট (Variance)</h5>
                    <p class="text-secondary small mb-3">
                        অনুমোদিত মূল ও সংশোধিত বাজেটের বিপরীতে প্রকৃত খরচের তুলনা, শতকরা বাস্তবায়ন হার ও সাশ্রয়/অতিরিক্ত ব্যয় চিহ্নিতকরণ।
                    </p>
                </div>
                <div>
                    <a href="{{ route('reports.budget_variance') }}" class="btn btn-outline-info text-dark rounded-pill w-100">
                        রিপোর্ট দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 6. General Ledger & Trial Balance Shortcut -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="stat-icon green mb-3">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">সাধারণ খতিয়ান ও রেওয়ামিল</h5>
                    <p class="text-secondary small mb-3">
                        হিসাব খাতের বিশদ খতিয়ান এবং ডেবিট-ক্রেডিট নির্ভুলতা যাচাইয়ের জন্য স্বয়ংক্রিয় রেওয়ামিল (Trial Balance)।
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('ledger.index') }}" class="btn btn-outline-success btn-sm rounded-pill flex-fill">
                        খতিয়ান (Ledger)
                    </a>
                    <a href="{{ route('trial_balance.index') }}" class="btn btn-outline-primary btn-sm rounded-pill flex-fill">
                        রেওয়ামিল (Trial Balance)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
