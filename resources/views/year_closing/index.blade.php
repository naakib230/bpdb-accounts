@extends('layouts.app')

@section('title', 'বছর সমাপনী প্রক্রিয়া (Year Closing) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-lock text-success me-2"></i>বছর সমাপনী প্রক্রিয়া (Year-End Closing Procedure)
        </h4>
        <p class="text-muted small mb-0">অর্থবছরের সকল রাজস্ব ও ব্যয় হিসাবের জের পুঞ্জীভূত সঞ্চিতিতে স্থানান্তর এবং অর্থবছর সমাপ্তকরণ</p>
    </div>

    @if ($currentFy && ! $currentFy->is_closed)
        <div class="row g-4 mb-4">
            <!-- Left: Financial Summary -->
            <div class="col-12 col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="fa-solid fa-chart-pie text-primary me-2"></i>চলতি অর্থবছর ({{ $currentFy->name }}) আর্থিক সারসংক্ষেপ
                    </h5>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block">মোট অর্জিত রাজস্ব আয়:</small>
                                <h4 class="fw-bold text-success mb-0">৳ {{ number_format($revenueTotal, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <small class="text-muted d-block">মোট উৎপাদন ও পরিচালন ব্যয়:</small>
                                <h4 class="fw-bold text-danger mb-0">৳ {{ number_format($expenseTotal, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded-3 {{ $netSurplus >= 0 ? 'bg-success bg-opacity-10 border border-success' : 'bg-danger bg-opacity-10 border border-danger' }}">
                                <small class="text-muted d-block">নিট পরিচালন উদ্বৃত্ত / (ঘাটতি):</small>
                                <h3 class="fw-bold mb-0 {{ $netSurplus >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳ {{ number_format($netSurplus, 2) }}
                                </h3>
                                <small class="text-secondary">এই অর্থ স্থানান্তরিত হবে: <strong>৩০১০২০ - পুঞ্জীভূত উদ্বৃত্ত (Retained Earnings)</strong> খাতে।</small>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="fw-bold text-dark mb-2">সমাপনী পূর্ববর্তী যাচাইকরণ (Pre-Closing Checks):</h6>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                @if ($unpostedCount === 0)
                                    <i class="fa-solid fa-circle-check text-success me-2"></i>
                                    <span>কোনো খসড়া বা অপেক্ষমাণ ভাউচার নেই (সব ভাউচার পোস্টকৃত)</span>
                                @else
                                    <i class="fa-solid fa-circle-xmark text-danger me-2"></i>
                                    <span class="text-danger fw-bold">{{ $unpostedCount }} টি ভাউচার এখনও অপেক্ষমাণ/খসড়া রয়েছে!</span>
                                @endif
                            </li>
                            <li class="mb-2">
                                <i class="fa-solid fa-circle-check text-success me-2"></i>
                                <span>রেওয়ামিল (Trial Balance) ডেবিট ও ক্রেডিট সমান এবং সমন্বিত</span>
                            </li>
                            <li>
                                <i class="fa-solid fa-circle-check text-success me-2"></i>
                                <span>পরবর্তী অর্থবছর সেটআপ করা রয়েছে</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right: Action Card -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="p-3 rounded-circle bg-danger bg-opacity-10 text-danger me-3">
                                <i class="fa-solid fa-triangle-exclamation fs-3"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">বছর সমাপ্তি নিশ্চিতকরণ</h5>
                                <small class="text-muted">Year-End Closing Confirmation</small>
                            </div>
                        </div>

                        <div class="alert alert-warning small border-0">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            <strong>সতর্কতা:</strong> অর্থবছর সমাপনী সম্পন্ন করার পর এই অর্থবছরে আর কোনো নতুন ভাউচার তৈরি বা পরিবর্তন করা যাবে না। সকল রাজস্ব ও ব্যয় শূন্য হয়ে পরবর্তী বছরের প্রারম্ভিক জের প্রস্তুত হবে।
                        </div>

                        <p class="small text-secondary">
                            আপনি কি <strong>{{ $currentFy->name }}</strong> অর্থবছর আনুষ্ঠানিকভাবে সমাপ্ত ও লক করতে চান?
                        </p>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <form method="POST" action="{{ route('year_closing.process') }}" onsubmit="return confirm('আপনি কি নিশ্চিত যে এই অর্থবছর সমাপ্ত ও লক করবেন?')">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100 py-2 rounded-pill shadow-sm fw-bold" @disabled($unpostedCount > 0)>
                                <i class="fa-solid fa-lock me-2"></i>{{ $currentFy->name }} অর্থবছর সমাপ্ত ও লক করুন
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info shadow-sm rounded-4 p-4 border-0 mb-4">
            <h5 class="fw-bold"><i class="fa-solid fa-circle-info me-2"></i>বর্তমান সক্রিয় অর্থবছর সমাপ্ত</h5>
            <p class="mb-0">বর্তমান সক্রিয় অর্থবছর ইতোমধ্যে সমাপ্ত বা কোনো সক্রিয় অর্থবছর নির্বাচন করা নেই। নতুন অর্থবছরের জন্য <a href="{{ route('accounting_periods.index') }}" class="alert-link">অর্থবছর সেটআপ</a> পাতায় যান।</p>
        </div>
    @endif

    <!-- History of Fiscal Years -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="card-header bg-white p-3 border-bottom">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-clock-rotate-left me-2 text-secondary"></i>বিউবো অর্থবছর সমাপনী ইতিহাস (Fiscal Years History)
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>অর্থবছর</th>
                        <th>সময়সীমা</th>
                        <th>অবস্থা</th>
                        <th>সমাপ্তির তারিখ ও সময়</th>
                        <th>সমাপ্তকারী কর্মকর্তা</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($allFiscalYears as $fy)
                        <tr>
                            <td class="fw-bold text-dark">{{ $fy->name }}</td>
                            <td>{{ $fy->start_date->format('d/m/Y') }} হতে {{ $fy->end_date->format('d/m/Y') }}</td>
                            <td>
                                @if ($fy->is_closed)
                                    <span class="badge bg-secondary"><i class="fa-solid fa-lock me-1"></i>সমাপ্ত ও লককৃত</span>
                                @elseif ($fy->is_current)
                                    <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i>সক্রিয় অর্থবছর</span>
                                @else
                                    <span class="badge bg-info">আসন্ন / প্রস্তুত</span>
                                @endif
                            </td>
                            <td>{{ $fy->closed_at ? $fy->closed_at->format('d/m/Y h:i A') : '-' }}</td>
                            <td>{{ $fy->closedByUser?->name_bn ?: ($fy->closedByUser?->name ?: '-') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
