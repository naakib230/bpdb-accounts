@extends('layouts.app')

@section('title', 'রেওয়ামিল (Trial Balance) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-scale-balanced text-success me-2"></i>রেওয়ামিল (Trial Balance)
            </h4>
            <p class="text-muted small mb-0">সকল হিসাব খাতের প্রারম্ভিক, চলতি লেনদেন ও সমাপনী ডেবিট-ক্রেডিট জের যাচাইকরণ</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('trial_balance.export', request()->all()) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-file-excel me-1"></i>এক্সেল / সিএসভি ডাউনলোড
            </a>
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm no-print">
                <i class="fa-solid fa-print me-1"></i>প্রিন্ট
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('trial_balance.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">তারিখ পর্যন্ত (As of Date)</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}">
                </div>

                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">কস্ট সেন্টার (ঐচ্ছিক ফিল্টার)</label>
                    <select name="cost_center_id" class="form-select form-select-sm">
                        <option value="">সকল কস্ট সেন্টার (All BPDB Offices)</option>
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}" @selected($costCenterId == $cc->id)>
                                {{ $cc->code }} - {{ $cc->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i>ফিল্টার করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Balance Status Alert -->
    @if ($isBalanced)
        <div class="alert alert-success d-flex align-items-center shadow-sm rounded-4 mb-4 border-0">
            <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
            <div>
                <strong class="d-block">রেওয়ামিল সম্পূর্ণ নির্ভুল ও সমন্বিত!</strong>
                <small>মোট সমাপনী ডেবিট (৳ {{ number_format($grandTotalClosingDr, 2) }}) এবং ক্রেডিট (৳ {{ number_format($grandTotalClosingCr, 2) }}) সমান রয়েছে।</small>
            </div>
        </div>
    @else
        <div class="alert alert-danger d-flex align-items-center shadow-sm rounded-4 mb-4 border-0">
            <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-danger"></i>
            <div>
                <strong class="d-block">রেওয়ামিলে ডেবিট ও ক্রেডিট অমিল রয়েছে!</strong>
                <small>পার্থক্য: ৳ {{ number_format(abs($grandTotalClosingDr - $grandTotalClosingCr), 2) }}। অনুগ্রহ করে ভাউচার ও খতিয়ান যাচাই করুন।</small>
            </div>
        </div>
    @endif

    <!-- Trial Balance Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 10%;">হিসাব কোড</th>
                        <th rowspan="2" style="width: 25%;">হিসাব খাতের নাম</th>
                        <th rowspan="2" style="width: 10%;">ধরন</th>
                        <th colspan="2" class="text-center bg-light border-bottom">প্রারম্ভিক জের (Opening)</th>
                        <th colspan="2" class="text-center bg-light border-bottom">চলতি লেনদেন (Transactions)</th>
                        <th colspan="2" class="text-center bg-light border-bottom">সমাপনী জের (Closing)</th>
                    </tr>
                    <tr>
                        <th class="text-end" style="width: 9%;">ডেবিট (৳)</th>
                        <th class="text-end" style="width: 9%;">ক্রেডিট (৳)</th>
                        <th class="text-end" style="width: 9%;">ডেবিট (৳)</th>
                        <th class="text-end" style="width: 9%;">ক্রেডিট (৳)</th>
                        <th class="text-end" style="width: 9%;">ডেবিট (৳)</th>
                        <th class="text-end" style="width: 10%;">ক্রেডিট (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td class="fw-bold">{{ $r['code'] }}</td>
                            <td>
                                <a href="{{ route('ledger.index', ['chart_of_account_id' => \App\Models\ChartOfAccount::where('account_code', $r['code'])->value('id')]) }}" class="text-decoration-none text-dark fw-medium">
                                    {{ $r['name_bn'] }}
                                </a>
                                <small class="text-muted d-block">{{ $r['name'] }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ strtoupper($r['type']) }}</span>
                            </td>
                            <td class="text-end">{{ $r['opening_dr'] > 0 ? number_format($r['opening_dr'], 2) : '-' }}</td>
                            <td class="text-end">{{ $r['opening_cr'] > 0 ? number_format($r['opening_cr'], 2) : '-' }}</td>
                            <td class="text-end text-success">{{ $r['period_dr'] > 0 ? number_format($r['period_dr'], 2) : '-' }}</td>
                            <td class="text-end text-primary">{{ $r['period_cr'] > 0 ? number_format($r['period_cr'], 2) : '-' }}</td>
                            <td class="text-end fw-bold text-success">{{ $r['closing_dr'] > 0 ? number_format($r['closing_dr'], 2) : '-' }}</td>
                            <td class="text-end fw-bold text-primary">{{ $r['closing_cr'] > 0 ? number_format($r['closing_cr'], 2) : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">কোনো রেকর্ড পাওয়া যায়নি।</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light border-top border-2">
                    <tr class="fw-bold">
                        <td colspan="3" class="text-end">সর্বমোট যোগফল (Grand Total):</td>
                        <td class="text-end">৳ {{ number_format($grandTotalOpeningDr, 2) }}</td>
                        <td class="text-end">৳ {{ number_format($grandTotalOpeningCr, 2) }}</td>
                        <td class="text-end text-success">৳ {{ number_format($grandTotalPeriodDr, 2) }}</td>
                        <td class="text-end text-primary">৳ {{ number_format($grandTotalPeriodCr, 2) }}</td>
                        <td class="text-end text-success fs-6">৳ {{ number_format($grandTotalClosingDr, 2) }}</td>
                        <td class="text-end text-primary fs-6">৳ {{ number_format($grandTotalClosingCr, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
