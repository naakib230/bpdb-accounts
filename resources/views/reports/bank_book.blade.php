@extends('layouts.app')

@section('title', 'ব্যাংক ও ক্যাশ খাতা (Cash / Bank Book) | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-building-columns text-success me-2"></i>ব্যাংক ও ক্যাশ খাতা (Bank & Cash Book)
            </h4>
            <p class="text-muted small mb-0">ব্যাংক হিসাব ও নগদ তহবিলের জমা-খরচ রেজিস্টার ও চলমান ব্যালেন্স</p>
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
            <form method="GET" action="{{ route('reports.bank_book') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label small text-muted mb-1">ব্যাংক হিসাব নির্বাচন করুন <span class="text-danger">*</span></label>
                    <select name="bank_account_id" class="form-select form-select-sm" required onchange="this.form.submit()">
                        @foreach ($bankAccounts as $ba)
                            <option value="{{ $ba->id }}" @selected($selectedBank && $selectedBank->id == $ba->id)>
                                {{ $ba->bank_name }} - {{ $ba->account_number }} ({{ $ba->account_title }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-1">শুরুর তারিখ</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
                </div>
                <div class="col-6 col-md-3">
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

    @if ($selectedBank)
        <!-- Bank Summary Card -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div>
                    <h5 class="fw-bold text-success mb-1">
                        {{ $selectedBank->bank_name }} - {{ $selectedBank->account_number }}
                    </h5>
                    <div class="text-muted small">
                        শাখা: {{ $selectedBank->branch_name }} | ধরন: {{ $selectedBank->account_type }} | শিরোনাম: {{ $selectedBank->account_title }}
                    </div>
                </div>
                <div class="d-flex gap-4 mt-3 mt-md-0 text-md-end">
                    <div>
                        <small class="text-muted d-block">প্রারম্ভিক জের:</small>
                        <strong class="fs-6 text-dark">৳ {{ number_format($openingBalance, 2) }}</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">বর্তমান স্থিতি:</small>
                        <strong class="fs-6 text-success">৳ {{ number_format($closingBalance, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead>
                        <tr>
                            <th style="width: 10%;">তারিখ</th>
                            <th style="width: 15%;">ভাউচার নং</th>
                            <th style="width: 12%;">চেক নং</th>
                            <th style="width: 20%;">প্রাপক / প্রদানকারী</th>
                            <th style="width: 23%;">বিবরণ (Narration)</th>
                            <th style="width: 10%; text-align: right;">জমা (Debit)</th>
                            <th style="width: 10%; text-align: right;">খরচ (Credit)</th>
                            <th style="width: 10%; text-align: right;">ব্যালেন্স</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td>{{ date('d/m/Y', strtotime($fromDate)) }}</td>
                            <td>-</td>
                            <td>-</td>
                            <td colspan="2" class="fw-bold">প্রারম্ভিক ব্যালেন্স (Opening Balance)</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end fw-bold">৳ {{ number_format($openingBalance, 2) }}</td>
                        </tr>

                        @forelse ($transactions as $t)
                            <tr>
                                <td>{{ $t['date'] }}</td>
                                <td class="fw-bold text-dark">{{ $t['voucher_number'] }}</td>
                                <td class="small">{{ $t['cheque_number'] ?: '-' }}</td>
                                <td class="small">{{ $t['payee'] ?: '-' }}</td>
                                <td class="small">{{ $t['narration'] }}</td>
                                <td class="text-end fw-semibold text-success">{{ $t['debit'] > 0 ? number_format($t['debit'], 2) : '-' }}</td>
                                <td class="text-end fw-semibold text-danger">{{ $t['credit'] > 0 ? number_format($t['credit'], 2) : '-' }}</td>
                                <td class="text-end fw-bold text-dark">৳ {{ number_format($t['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">এই সময়সীমায় কোনো ব্যাংক লেনদেন পাওয়া যায়নি।</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light border-top border-2 fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">সর্বমোট চলতি লেনদেন:</td>
                            <td class="text-end text-success">৳ {{ number_format($totalDr, 2) }}</td>
                            <td class="text-end text-danger">৳ {{ number_format($totalCr, 2) }}</td>
                            <td class="text-end text-dark">৳ {{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
