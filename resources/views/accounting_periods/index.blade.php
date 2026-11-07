@extends('layouts.app')

@section('title', 'অর্থবছর ও পিরিয়ড সেটআপ | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-calendar-days text-success me-2"></i>অর্থবছর ও হিসাব পিরিয়ড সেটআপ (Accounting Periods)
            </h4>
            <p class="text-muted small mb-0">বিউবোর আর্থিক অর্থবছর তৈরি, সক্রিয় অর্থবছর নির্ধারণ ও মাসিক পিরিয়ড লক/আনলক</p>
        </div>

        <div class="d-flex gap-2 mt-3 mt-md-0">
            <button class="btn btn-success rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createFyModal">
                <i class="fa-solid fa-plus me-1"></i>নতুন অর্থবছর তৈরি
            </button>
        </div>
    </div>

    <!-- Fiscal Years Accordion / Cards -->
    @foreach ($fiscalYears as $fy)
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
            <div class="card-header bg-white p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-5 fw-bold text-dark">{{ $fy->name }} অর্থবছর</span>
                    <span class="text-muted small">({{ $fy->start_date->format('d/m/Y') }} হতে {{ $fy->end_date->format('d/m/Y') }})</span>

                    @if ($fy->is_current)
                        <span class="badge bg-success ms-2"><i class="fa-solid fa-circle-check me-1"></i>বর্তমান সক্রিয় অর্থবছর</span>
                    @endif

                    @if ($fy->is_closed)
                        <span class="badge bg-secondary ms-1"><i class="fa-solid fa-lock me-1"></i>সমাপ্ত ও লককৃত</span>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    @if (! $fy->is_current && ! $fy->is_closed)
                        <form method="POST" action="{{ route('accounting_periods.set_current', $fy) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-success btn-sm rounded-pill">
                                <i class="fa-solid fa-check-double me-1"></i>সক্রিয় অর্থবছর করুন
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Periods Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-custom">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">পিরিয়ড নং</th>
                            <th style="width: 25%;">পিরিয়ডের নাম (Month)</th>
                            <th style="width: 25%;">সময়সীমা</th>
                            <th style="width: 15%;">বর্তমান অবস্থা</th>
                            <th style="width: 25%; text-align: end;">অবস্থা পরিবর্তন (Status Action)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($fy->periods as $p)
                            <tr>
                                <td class="fw-bold text-muted">{{ $p->period_number }}</td>
                                <td class="fw-bold text-dark">{{ $p->period_name }}</td>
                                <td class="small">{{ $p->start_date->format('d/m/Y') }} হতে {{ $p->end_date->format('d/m/Y') }}</td>
                                <td>
                                    @if ($p->status === 'open')
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold">উন্মুক্ত (Open)</span>
                                    @elseif ($p->status === 'closed')
                                        <span class="badge bg-warning bg-opacity-10 text-warning fw-bold">বন্ধ (Closed)</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold">লককৃত (Locked)</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('accounting_periods.update_status', $p) }}" class="d-inline-flex gap-1">
                                        @csrf
                                        <button type="submit" name="status" value="open" class="btn btn-sm {{ $p->status === 'open' ? 'btn-success' : 'btn-outline-secondary' }} py-0 px-2" title="উন্মুক্ত করুন">
                                            উন্মুক্ত
                                        </button>
                                        <button type="submit" name="status" value="closed" class="btn btn-sm {{ $p->status === 'closed' ? 'btn-warning' : 'btn-outline-secondary' }} py-0 px-2" title="বন্ধ করুন">
                                            বন্ধ
                                        </button>
                                        <button type="submit" name="status" value="locked" class="btn btn-sm {{ $p->status === 'locked' ? 'btn-danger' : 'btn-outline-secondary' }} py-0 px-2" title="লক করুন">
                                            লক
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">এই অর্থবছরের কোনো পিরিয়ড তৈরি করা হয়নি।</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

<!-- Create FY Modal -->
<div class="modal fade" id="createFyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('accounting_periods.store_year') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-plus me-2 text-success"></i>নতুন অর্থবছর তৈরি</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">অর্থবছরের নাম <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="যেমন: 2026-2027" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">শুরুর তারিখ <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" value="{{ date('Y') }}-07-01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">শেষ তারিখ <span class="text-danger">*</span></label>
                    <input type="date" name="end_date" class="form-control" value="{{ date('Y') + 1 }}-06-30" required>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="is_current" value="1" id="is_current" class="form-check-input">
                    <label class="form-check-label small" for="is_current">এটিকে এখনই বর্তমান সক্রিয় অর্থবছর হিসেবে নির্ধারণ করুন</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">বাতিল</button>
                <button type="submit" class="btn btn-success rounded-pill">তৈরি করুন</button>
            </div>
        </form>
    </div>
</div>
@endsection
