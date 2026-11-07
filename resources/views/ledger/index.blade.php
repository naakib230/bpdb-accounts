@extends('layouts.app')

@section('title', 'সাধারণ খতিয়ান (General Ledger) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-book-open text-success me-2"></i>সাধারণ খতিয়ান (General Ledger)
            </h4>
            <p class="text-muted small mb-0">হিসাব খাত অনুযায়ী লেনদেন খতিয়ান ও চলমান ব্যালেন্স বিবরণী</p>
        </div>

        @if ($selectedAccount)
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('ledger.export', request()->all()) }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                    <i class="fa-solid fa-file-excel me-1"></i>এক্সেল / সিএসভি ডাউনলোড
                </a>
                <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm no-print">
                    <i class="fa-solid fa-print me-1"></i>প্রিন্ট
                </button>
            </div>
        @endif
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 no-print">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('ledger.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-1">হিসাব খাত (Chart of Account) <span class="text-danger">*</span></label>
                    <select name="chart_of_account_id" class="form-select form-select-sm" required onchange="this.form.submit()">
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" @selected($selectedAccount && $selectedAccount->id == $acc->id)>
                                {{ $acc->account_code }} - {{ $acc->account_name_bn }} ({{ ucfirst($acc->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">কস্ট সেন্টার (ঐচ্ছিক ফিল্টার)</label>
                    <select name="cost_center_id" class="form-select form-select-sm">
                        <option value="">সকল কস্ট সেন্টার (All BPDB)</option>
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}" @selected($costCenterId == $cc->id)>
                                {{ $cc->code }} - {{ $cc->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">শুরুর তারিখ</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">শেষ তারিখ</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
                </div>

                <div class="col-12 col-md-1">
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ledger Content -->
    @if ($selectedAccount)
        <!-- Account Info Header -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h5 class="fw-bold text-success mb-1">
                        {{ $selectedAccount->account_code }} - {{ $selectedAccount->account_name_bn }}
                    </h5>
                    <div class="text-muted small">
                        ইংরেজি নাম: {{ $selectedAccount->account_name }} | ধরন: <span class="badge bg-light text-dark border">{{ strtoupper($selectedAccount->account_type) }}</span> | স্বাভাবিক জের: <span class="badge bg-light text-dark border">{{ strtoupper($selectedAccount->normal_balance) }}</span>
                    </div>
                </div>

                <div class="d-flex gap-4 mt-3 mt-md-0 text-md-end">
                    <div>
                        <small class="text-muted d-block">প্রারম্ভিক জের (Opening):</small>
                        <strong class="fs-6 text-dark">৳ {{ number_format($openingBalance, 2) }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">সমাপনী জের (Closing):</small>
                        <strong class="fs-6 text-success">৳ {{ number_format($closingBalance, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th style="width: 10%;">তারিখ</th>
                            <th style="width: 15%;">ভাউচার নং</th>
                            <th style="width: 12%;">ধরন</th>
                            <th style="width: 18%;">কস্ট সেন্টার</th>
                            <th style="width: 25%;">বিবরণ (Narration)</th>
                            <th style="width: 10%; text-align: right;">ডেবিট (টাকা)</th>
                            <th style="width: 10%; text-align: right;">ক্রেডিট (টাকা)</th>
                            <th style="width: 10%; text-align: right;">ব্যালেন্স (টাকা)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening Balance Row -->
                        <tr class="table-light">
                            <td>{{ date('d/m/Y', strtotime($fromDate)) }}</td>
                            <td>-</td>
                            <td><span class="badge bg-secondary">প্রারম্ভিক জের</span></td>
                            <td>-</td>
                            <td class="fw-semibold">প্রারম্ভিক ব্যালেন্স (Opening Balance)</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end fw-bold">৳ {{ number_format($openingBalance, 2) }}</td>
                        </tr>

                        <!-- Transaction Rows -->
                        @forelse ($ledgerData as $item)
                            <tr>
                                <td>{{ $item['date'] }}</td>
                                <td class="fw-bold">
                                    <span class="text-dark">{{ $item['voucher_number'] }}</span>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $item['voucher_type'] }}</span></td>
                                <td class="small">{{ $item['cost_center'] }}</td>
                                <td class="small">{{ $item['narration'] }}</td>
                                <td class="text-end fw-semibold text-success">
                                    {{ $item['debit'] > 0 ? number_format($item['debit'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-semibold text-primary">
                                    {{ $item['credit'] > 0 ? number_format($item['credit'], 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    ৳ {{ number_format($item['running_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    নির্বাচিত সময়ে এই হিসাব খাতে কোনো লেনদেন পাওয়া যায়নি।
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light border-top border-2">
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">সর্বমোট চলতি লেনদেন:</td>
                            <td class="text-end text-success">৳ {{ number_format($totalDebit, 2) }}</td>
                            <td class="text-end text-primary">৳ {{ number_format($totalCredit, 2) }}</td>
                            <td class="text-end text-dark">৳ {{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
