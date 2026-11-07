@extends('layouts.app')

@section('title', 'বাজেট ভ্যারিয়েন্স রিপোর্ট | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-bullseye text-success me-2"></i>বাজেট ভ্যারিয়েন্স রিপোর্ট (Budget vs Actual Variance)
            </h4>
            <p class="text-muted small mb-0">কস্ট সেন্টারের বিভিন্ন ব্যয় খাতে অনুমোদিত বাজেট বনাম প্রকৃত ব্যয়ের পার্থক্য বিশ্লেষণ</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm no-print">
                <i class="fa-solid fa-print me-1"></i>প্রিন্ট
            </button>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reports.budget_variance') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label class="form-label small text-muted mb-1">কস্ট সেন্টার নির্বাচন করুন <span class="text-danger">*</span></label>
                    <select name="cost_center_id" class="form-select form-select-sm" required onchange="this.form.submit()">
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}" @selected($selectedCc && $selectedCc->id == $cc->id)>
                                {{ $cc->code }} - {{ $cc->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedCc)
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th style="width: 15%;">হিসাব কোড</th>
                            <th style="width: 35%;">ব্যয়ের খাত (Expense Head)</th>
                            <th style="width: 15%; text-align: right;">অনুমোদিত বাজেট (৳)</th>
                            <th style="width: 15%; text-align: right;">প্রকৃত ব্যয় (৳)</th>
                            <th style="width: 15%; text-align: right;">অবশিষ্ট / ভ্যারিয়েন্স (৳)</th>
                            <th style="width: 10%; text-align: center;">ব্যবহার (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($budgets as $b)
                            <tr>
                                <td class="fw-bold">{{ $b['account_code'] }}</td>
                                <td>{{ $b['account_name'] }}</td>
                                <td class="text-end fw-semibold">৳ {{ number_format($b['budget'], 2) }}</td>
                                <td class="text-end fw-bold text-danger">৳ {{ number_format($b['actual'], 2) }}</td>
                                <td class="text-end fw-bold {{ $b['variance'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    ৳ {{ number_format($b['variance'], 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $b['percentage'] > 90 ? 'bg-danger' : ($b['percentage'] > 50 ? 'bg-warning text-dark' : 'bg-success') }}">
                                        {{ $b['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">এই কস্ট সেন্টারের কোনো বাজেট বরাদ্দ পাওয়া যায়নি।</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light border-top border-2 fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">সর্বমোট:</td>
                            <td class="text-end">৳ {{ number_format($totalBudget, 2) }}</td>
                            <td class="text-end text-danger">৳ {{ number_format($totalActual, 2) }}</td>
                            <td class="text-end text-success">৳ {{ number_format($totalBudget - $totalActual, 2) }}</td>
                            <td class="text-center">
                                {{ $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
