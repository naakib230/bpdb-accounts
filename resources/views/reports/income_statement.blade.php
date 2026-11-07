@extends('layouts.app')

@section('title', 'আয়-ব্যয় বিবরণী (Income Statement) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-chart-line text-success me-2"></i>আয়-ব্যয় বিবরণী (Income Statement / Profit & Loss)
            </h4>
            <p class="text-muted small mb-0">নির্দিষ্ট সময়সীমায় বিউবোর পরিচালন রাজস্ব আয়, পরিচালন ব্যয় ও নিট উদ্বৃত্ত বিবরণী</p>
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
            <form method="GET" action="{{ route('reports.income_statement') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">শুরুর তারিখ (From Date)</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">শেষ তারিখ (To Date)</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i>হিসাব করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Income Statement Card -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-body p-4">
            <!-- 1. Revenue Section -->
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                <h5 class="fw-bold text-success mb-0">
                    <i class="fa-solid fa-circle-arrow-down me-2"></i>১. পরিচালন ও অন্যান্য রাজস্ব আয় (Revenue)
                </h5>
                <strong class="fs-6 text-success">মোট আয়: ৳ {{ number_format($totalRevenue, 2) }}</strong>
            </div>

            <table class="table table-sm align-middle mb-4">
                <thead>
                    <tr class="table-light">
                        <th style="width: 15%;">হিসাব কোড</th>
                        <th style="width: 60%;">আয়ের বিবরণ (Revenue Head)</th>
                        <th style="width: 25%; text-align: right;">টাকার পরিমাণ (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($revenues as $r)
                        <tr>
                            <td class="fw-bold">{{ $r['code'] }}</td>
                            <td>{{ $r['name_bn'] }}</td>
                            <td class="text-end fw-semibold text-success">৳ {{ number_format($r['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-3 text-muted">এই সময়সীমায় কোনো রাজস্ব লেনদেন পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 2. Expenditure Section -->
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom border-top pt-3">
                <h5 class="fw-bold text-danger mb-0">
                    <i class="fa-solid fa-circle-arrow-up me-2"></i>২. বিদ্যুৎ উৎপাদন ও পরিচালনা ব্যয় (Expenditure)
                </h5>
                <strong class="fs-6 text-danger">মোট ব্যয়: ৳ {{ number_format($totalExpense, 2) }}</strong>
            </div>

            <table class="table table-sm align-middle mb-4">
                <thead>
                    <tr class="table-light">
                        <th style="width: 15%;">হিসাব কোড</th>
                        <th style="width: 60%;">ব্যয়ের বিবরণ (Expense Head)</th>
                        <th style="width: 25%; text-align: right;">টাকার পরিমাণ (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $e)
                        <tr>
                            <td class="fw-bold">{{ $e['code'] }}</td>
                            <td>{{ $e['name_bn'] }}</td>
                            <td class="text-end fw-semibold text-danger">৳ {{ number_format($e['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-3 text-muted">এই সময়সীমায় কোনো ব্যয় লেনদেন পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- 3. Net Operating Income Summary -->
            <div class="p-4 rounded-4 {{ $netOperatingIncome >= 0 ? 'bg-success bg-opacity-10 border border-success' : 'bg-danger bg-opacity-10 border border-danger' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-1 {{ $netOperatingIncome >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $netOperatingIncome >= 0 ? 'চলতি সময়সীমার নিট পরিচালন উদ্বৃত্ত (Net Surplus):' : 'চলতি সময়সীমার নিট পরিচালন ঘাটতি (Net Deficit):' }}
                        </h5>
                        <small class="text-secondary">মোট রাজস্ব আয় হতে মোট বিদ্যুৎ উৎপাদন ও পরিচালন ব্যয় বাদ দিয়ে নির্ধারিত</small>
                    </div>
                    <h3 class="fw-bold mb-0 {{ $netOperatingIncome >= 0 ? 'text-success' : 'text-danger' }}">
                        ৳ {{ number_format($netOperatingIncome, 2) }}
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
