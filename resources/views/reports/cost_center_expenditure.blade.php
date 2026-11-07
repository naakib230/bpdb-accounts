@extends('layouts.app')

@section('title', 'কস্ট সেন্টার ভিত্তিক খরচ বিবরণী | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-chart-pie text-success me-2"></i>কস্ট সেন্টার ভিত্তিক খরচ বিবরণী (Cost Center Expenditure)
            </h4>
            <p class="text-muted small mb-0">দপ্তর ও বিদ্যুৎ কেন্দ্র ভিত্তিক বাজেট বরাদ্দ, প্রকৃত খরচ ও বাস্তবায়নের শতকরা হার</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm no-print">
                <i class="fa-solid fa-print me-1"></i>প্রিন্ট রিপোর্ট
            </button>
        </div>
    </div>

    <!-- Summary Total Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted small">অর্থবছর:</span>
                <strong class="text-dark ms-1">{{ $currentFy ? $currentFy->name : 'নির্ধারিত নেই' }}</strong>
            </div>
            <div>
                <span class="text-muted small">বিউবোর সর্বমোট ব্যয়:</span>
                <strong class="fs-5 text-danger ms-2">৳ {{ number_format($totalExpAll, 2) }}</strong>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th style="width: 10%;">কোড</th>
                        <th style="width: 30%;">কস্ট সেন্টারের নাম (Cost Center)</th>
                        <th style="width: 15%;">অবস্থান / জোন</th>
                        <th style="width: 15%; text-align: right;">বাজেট বরাদ্দ (৳)</th>
                        <th style="width: 15%; text-align: right;">প্রকৃত ব্যয় (৳)</th>
                        <th style="width: 15%; text-align: center;">বাস্তবায়ন অগ্রগতি</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $d)
                        <tr>
                            <td class="fw-bold text-dark">{{ $d['code'] }}</td>
                            <td>
                                <strong class="text-dark">{{ $d['name_bn'] }}</strong>
                            </td>
                            <td class="small text-muted">{{ $d['location'] }}</td>
                            <td class="text-end fw-semibold">৳ {{ number_format($d['allocated'], 2) }}</td>
                            <td class="text-end fw-bold text-danger">৳ {{ number_format($d['spent'], 2) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $d['percent'] }}%;"></div>
                                    </div>
                                    <span class="small fw-bold text-dark" style="min-width: 40px;">{{ $d['percent'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">কোনো তথ্য পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
