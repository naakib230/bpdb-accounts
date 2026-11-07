@extends('layouts.app')

@section('title', 'ড্যাশবোর্ড | বিউবো অ্যাকাউন্টস পোর্টাল')

@section('content')
<div class="container-fluid px-0">
    <!-- Top Greeting Banner -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-white p-4 rounded-4 shadow-sm border mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                স্বাগতম, {{ auth()->user()->name_bn ?: auth()->user()->name }}!
            </h4>
            <div class="d-flex flex-wrap align-items-center gap-2 text-secondary small">
                <span><i class="fa-solid fa-building me-1 text-success"></i>{{ auth()->user()->office_name ?: 'বিউবো প্রধান কার্যালয়, ঢাকা' }}</span>
                <span>•</span>
                <span><i class="fa-solid fa-id-card me-1 text-primary"></i>পদবি: {{ auth()->user()->designation ?: 'হিসাব কর্মকর্তা' }}</span>
                <span>•</span>
                <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">
                    <i class="fa-solid fa-circle-check me-1"></i>সক্রিয় অর্থবছর: {{ $currentFy ? $currentFy->name : 'নির্ধারিত নেই' }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('vouchers.create_cash') }}" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i>ক্যাশ ভাউচার
            </a>
            <a href="{{ route('vouchers.create_journal') }}" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i>জার্নাল ভাউচার
            </a>
        </div>
    </div>

    <!-- 4 Key Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">মোট ব্যাংক ও নগদ উদ্বৃত্ত</div>
                    <h3 class="stat-value text-success">৳ {{ number_format($totalBankBalance + $cashInHandBalance, 2) }}</h3>
                    <small class="text-muted"><i class="fa-solid fa-vault me-1"></i>ব্যাংক: ৳ {{ number_format($totalBankBalance, 2) }}</small>
                </div>
                <div class="stat-icon green">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">চলতি অর্থবছরের মোট রাজস্ব/আয়</div>
                    <h3 class="stat-value text-primary">৳ {{ number_format($revenue, 2) }}</h3>
                    <small class="text-muted"><i class="fa-solid fa-receipt me-1"></i>বিদ্যুৎ বিক্রয় ও অন্যান্য আয়</small>
                </div>
                <div class="stat-icon blue">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">চলতি অর্থবছরের মোট ব্যয়</div>
                    <h3 class="stat-value text-danger">৳ {{ number_format($expenditure, 2) }}</h3>
                    <small class="text-muted"><i class="fa-solid fa-fire-burner me-1"></i>জ্বালানি, বেতন ও পরিচালনা</small>
                </div>
                <div class="stat-icon red">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div>
                    <div class="stat-title">নিট পরিচালন উদ্বৃত্ত / (ঘাটতি)</div>
                    <h3 class="stat-value {{ $netSurplus >= 0 ? 'text-success' : 'text-danger' }}">
                        ৳ {{ number_format($netSurplus, 2) }}
                    </h3>
                    <small class="text-muted">
                        <i class="fa-solid {{ $netSurplus >= 0 ? 'fa-circle-check text-success' : 'fa-circle-xmark text-danger' }} me-1"></i>
                        {{ $netSurplus >= 0 ? 'উদ্বৃত্ত তহবিল' : 'ঘাটতি বাজেট' }}
                    </small>
                </div>
                <div class="stat-icon {{ $netSurplus >= 0 ? 'gold' : 'purple' }}">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Row -->
    <div class="row g-4">
        <!-- Left Column: Recent Vouchers -->
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-clock-rotate-left me-2 text-success"></i>সাম্প্রতিক ভাউচারসমূহ (Recent Vouchers)
                    </h6>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-outline-success btn-sm rounded-pill">
                        সকল ভাউচার দেখুন <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-custom">
                            <thead>
                                <tr>
                                    <th>ভাউচার নং</th>
                                    <th>তারিখ</th>
                                    <th>ধরন</th>
                                    <th>কস্ট সেন্টার</th>
                                    <th>পরিমাণ (টাকা)</th>
                                    <th>স্ট্যাটাস</th>
                                    <th class="text-end">অ্যাকশন</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentVouchers as $v)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $v->voucher_number }}</td>
                                        <td>{{ $v->date->format('d/m/Y') }}</td>
                                        <td>
                                            @if ($v->isCashVoucher())
                                                <span class="badge bg-success bg-opacity-10 text-success fw-semibold">
                                                    <i class="fa-solid fa-money-bill me-1"></i>ক্যাশ ভাউচার
                                                </span>
                                            @else
                                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                                    <i class="fa-solid fa-book-journal-whills me-1"></i>জার্নাল ভাউচার
                                                </span>
                                            @endif
                                        </td>
                                        <td class="small">{{ $v->costCenter?->name_bn ?: $v->costCenter?->name }}</td>
                                        <td class="fw-semibold">৳ {{ number_format($v->total_debit, 2) }}</td>
                                        <td>
                                            @if ($v->status === 'posted')
                                                <span class="badge bg-success">পোস্টকৃত</span>
                                            @elseif ($v->status === 'submitted')
                                                <span class="badge bg-warning text-dark">অপেক্ষমাণ</span>
                                            @elseif ($v->status === 'rejected')
                                                <span class="badge bg-danger">প্রত্যাখ্যাত</span>
                                            @else
                                                <span class="badge bg-secondary">খসড়া</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('vouchers.show', $v) }}" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" title="বিস্তারিত">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('vouchers.print', $v) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill py-0 px-2 ms-1" title="প্রিন্ট">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">কোনো ভাউচার পাওয়া যায়নি।</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Budget Breakdown & Recent Activity -->
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-chart-simple me-2 text-primary"></i>কস্ট সেন্টার বাজেট ব্যয় অগ্রগতি
                    </h6>
                </div>
                <div class="card-body p-3">
                    @forelse ($costCenterBudgets as $b)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-semibold text-dark text-truncate" style="max-width: 200px;">
                                    {{ $b['name_bn'] }}
                                </span>
                                <span class="small fw-bold text-success">{{ $b['percent'] }}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $b['percent'] }}%;" aria-valuenow="{{ $b['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted" style="font-size: 0.72rem;">
                                <span>ব্যয়: ৳ {{ number_format($b['spent']) }}</span>
                                <span>বরাদ্দ: ৳ {{ number_format($b['allocated']) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">কোনো বাজেট তথ্য পাওয়া যায়নি।</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Audit Logs -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom p-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-shield-halved me-2 text-warning"></i>সাম্প্রতিক কার্যক্রম (Audit Trail)
                    </h6>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0">
                        @forelse ($recentLogs as $log)
                            <li class="d-flex align-items-start gap-2 mb-2 pb-2 border-bottom border-light">
                                <i class="fa-solid fa-circle-dot text-success mt-1 fs-6"></i>
                                <div>
                                    <div class="small fw-semibold text-dark">{{ $log->description }}</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        {{ $log->user?->name_bn ?: $log->user?->name }} • {{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-muted small">কোনো লগ পাওয়া যায়নি।</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
