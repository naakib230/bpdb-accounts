@extends('layouts.app')

@section('title', 'আর্থিক অবস্থার বিবরণী (Balance Sheet) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-landmark text-success me-2"></i>আর্থিক অবস্থার বিবরণী (Balance Sheet / উদ্বৃত্তপত্র)
            </h4>
            <p class="text-muted small mb-0">নির্দিষ্ট তারিখ পর্যন্ত বিউবোর সার্বিক সম্পদ, দায় ও মালিকানা স্বত্ব বিবরণী</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm no-print">
                <i class="fa-solid fa-print me-1"></i>প্রিন্ট বিবরণী
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reports.balance_sheet') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">তারিখ পর্যন্ত (As of Date)</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fa-solid fa-calculator me-1"></i>হিসাব করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Sheet Equation Verification Alert -->
    @if ($isBalanced)
        <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0">
            <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
            <div>
                <strong class="d-block">আর্থিক সমীকরণ সম্পূর্ণ সমন্বিত! (Assets = Liabilities + Equity)</strong>
                <small>মোট সম্পদ (৳ {{ number_format($totalAssets, 2) }}) এবং মোট দায় ও মূলধন (৳ {{ number_format($totalLiabilitiesAndEquity, 2) }}) সমান রয়েছে।</small>
            </div>
        </div>
    @else
        <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-danger"></i>
            <div>
                <strong class="d-block">ব্যালেন্স শিট অসমন্বিত!</strong>
                <small>মোট সম্পদ ও দায়ের মধ্যে অমিল রয়েছে।</small>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left: Assets (সম্পদসমূহ) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden h-100">
                <div class="card-header bg-success text-white p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-building-columns me-2"></i>সম্পদসমূহ (ASSETS)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <!-- Current Assets -->
                    <div class="p-3 bg-light border-bottom fw-bold text-dark">
                        ১. চলতি সম্পদ (Current Assets)
                    </div>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach ($currentAssets as $a)
                                <tr>
                                    <td class="ps-4" style="width: 65%;">
                                        {{ $a['name_bn'] }} <small class="text-muted">({{ $a['code'] }})</small>
                                    </td>
                                    <td class="text-end pe-4 fw-semibold">৳ {{ number_format($a['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light fw-bold">
                                <td class="ps-4">মোট চলতি সম্পদ (A):</td>
                                <td class="text-end pe-4 text-success">৳ {{ number_format($totalCurrentAssets, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Non-Current / Fixed Assets -->
                    <div class="p-3 bg-light border-bottom border-top fw-bold text-dark">
                        ২. স্থায়ী সম্পদসমূহ (Non-Current / Fixed Assets)
                    </div>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach ($fixedAssets as $a)
                                <tr>
                                    <td class="ps-4" style="width: 65%;">
                                        {{ $a['name_bn'] }} <small class="text-muted">({{ $a['code'] }})</small>
                                    </td>
                                    <td class="text-end pe-4 fw-semibold">৳ {{ number_format($a['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light fw-bold">
                                <td class="ps-4">মোট স্থায়ী সম্পদ (B):</td>
                                <td class="text-end pe-4 text-success">৳ {{ number_format($totalFixedAssets, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-success bg-opacity-10 border-top p-3 d-flex justify-content-between align-items-center">
                    <strong class="fs-6 text-success">সর্বমোট সম্পদ (A + B):</strong>
                    <strong class="fs-5 text-success">৳ {{ number_format($totalAssets, 2) }}</strong>
                </div>
            </div>
        </div>

        <!-- Right: Liabilities & Equity (দায় ও মালিকানা স্বত্ব) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden h-100">
                <div class="card-header bg-dark text-white p-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice-dollar me-2 text-warning"></i>দায় ও মালিকানা স্বত্ব (LIABILITIES & EQUITY)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <!-- Current Liabilities -->
                    <div class="p-3 bg-light border-bottom fw-bold text-dark">
                        ১. চলতি দায় (Current Liabilities)
                    </div>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach ($currentLiabilities as $a)
                                <tr>
                                    <td class="ps-4" style="width: 65%;">
                                        {{ $a['name_bn'] }} <small class="text-muted">({{ $a['code'] }})</small>
                                    </td>
                                    <td class="text-end pe-4 fw-semibold">৳ {{ number_format($a['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light fw-bold">
                                <td class="ps-4">মোট চলতি দায় (A):</td>
                                <td class="text-end pe-4 text-danger">৳ {{ number_format($totalCurrentLiabilities, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Long Term Liabilities -->
                    <div class="p-3 bg-light border-bottom border-top fw-bold text-dark">
                        ২. দীর্ঘমেয়াদী দায় (Long-Term Liabilities)
                    </div>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach ($longTermLiabilities as $a)
                                <tr>
                                    <td class="ps-4" style="width: 65%;">
                                        {{ $a['name_bn'] }} <small class="text-muted">({{ $a['code'] }})</small>
                                    </td>
                                    <td class="text-end pe-4 fw-semibold">৳ {{ number_format($a['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-light fw-bold">
                                <td class="ps-4">মোট দীর্ঘমেয়াদী দায় (B):</td>
                                <td class="text-end pe-4 text-danger">৳ {{ number_format($totalLongTermLiabilities, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Equity & Reserves -->
                    <div class="p-3 bg-light border-bottom border-top fw-bold text-dark">
                        ৩. মূলধন ও মালিকানা স্বত্ব (Equity & Reserves)
                    </div>
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach ($equityAccounts as $a)
                                <tr>
                                    <td class="ps-4" style="width: 65%;">
                                        {{ $a['name_bn'] }} <small class="text-muted">({{ $a['code'] }})</small>
                                    </td>
                                    <td class="text-end pe-4 fw-semibold">৳ {{ number_format($a['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td class="ps-4">
                                    চলতি অর্থবছরের নিট উদ্বৃত্ত / (ঘাটতি)
                                </td>
                                <td class="text-end pe-4 fw-bold {{ $currentYearSurplus >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳ {{ number_format($currentYearSurplus, 2) }}
                                </td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td class="ps-4">মোট মালিকানা স্বত্ব (C):</td>
                                <td class="text-end pe-4 text-primary">৳ {{ number_format($totalEquity, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-dark bg-opacity-10 border-top p-3 d-flex justify-content-between align-items-center">
                    <strong class="fs-6 text-dark">সর্বমোট দায় ও মালিকানা স্বত্ব (A + B + C):</strong>
                    <strong class="fs-5 text-dark">৳ {{ number_format($totalLiabilitiesAndEquity, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
