@extends('layouts.app')

@section('title', 'কস্ট সেন্টার বাজেট বরাদ্দ | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-coins text-success me-2"></i>কস্ট সেন্টার ভিত্তিক বাজেট বরাদ্দ (Cost Center Budget)
            </h4>
            <p class="text-muted small mb-0">দপ্তর ও বিদ্যুৎ কেন্দ্র অনুযায়ী বিভিন্ন পরিচালন ব্যয় খাতে মূল ও সংশোধিত বাজেট এন্ট্রি</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('budget.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">অর্থবছর <span class="text-danger">*</span></label>
                    <select name="fiscal_year_id" class="form-select form-select-sm" required onchange="this.form.submit()">
                        @foreach ($fiscalYears as $fy)
                            <option value="{{ $fy->id }}" @selected($selectedFyId == $fy->id)>
                                {{ $fy->name }} অর্থবছর {{ $fy->is_current ? '(সক্রিয়)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">কস্ট সেন্টার / দপ্তর <span class="text-danger">*</span></label>
                    <select name="cost_center_id" class="form-select form-select-sm" required onchange="this.form.submit()">
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}" @selected($selectedCcId == $cc->id)>
                                {{ $cc->code }} - {{ $cc->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>লোড করুন
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedFyId && $selectedCcId)
        <form method="POST" action="{{ route('budget.store') }}">
            @csrf
            <input type="hidden" name="fiscal_year_id" value="{{ $selectedFyId }}">
            <input type="hidden" name="cost_center_id" value="{{ $selectedCcId }}">

            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>ব্যয় খাত ভিত্তিক বাজেট বরাদ্দ তালিকা
                    </h6>
                    <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>বাজেট সংরক্ষণ করুন
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th style="width: 12%;">হিসাব কোড</th>
                                <th style="width: 28%;">ব্যয়ের খাত (Expense Head)</th>
                                <th style="width: 15%; text-align: right;">মূল বাজেট (৳)</th>
                                <th style="width: 15%; text-align: right;">সংশোধিত বাজেট (৳)</th>
                                <th style="width: 12%; text-align: right;">প্রকৃত ব্যয় (৳)</th>
                                <th style="width: 18%;">মন্তব্য / নোট</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($budgets as $idx => $b)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $b['account_code'] }}</td>
                                    <td>
                                        <input type="hidden" name="allocations[{{ $idx }}][chart_of_account_id]" value="{{ $b['chart_of_account_id'] }}">
                                        <strong>{{ $b['account_name_bn'] }}</strong>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="allocations[{{ $idx }}][original_budget]" class="form-control form-control-sm text-end" value="{{ $b['original_budget'] }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="allocations[{{ $idx }}][revised_budget]" class="form-control form-control-sm text-end" value="{{ $b['revised_budget'] }}">
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ $b['utilized_amount'] > 0 ? '৳ ' . number_format($b['utilized_amount'], 2) : '-' }}
                                    </td>
                                    <td>
                                        <input type="text" name="allocations[{{ $idx }}][notes]" class="form-control form-control-sm" value="{{ $b['notes'] }}" placeholder="নোট...">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white p-3 text-end border-top">
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i>বাজেট সংরক্ষণ করুন
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection
