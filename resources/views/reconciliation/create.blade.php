@extends('layouts.app')

@section('title', 'নতুন ব্যাংক সমন্বয় বিবরণী | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('reconciliation.index') }}" class="text-decoration-none text-secondary small">
            <i class="fa-solid fa-arrow-left me-1"></i>ব্যাংক সমন্বয় তালিকায় ফিরে যান
        </a>
        <h4 class="fw-bold text-dark mt-2 mb-1">
            <i class="fa-solid fa-handshake-simple text-success me-2"></i>নতুন ব্যাংক সমন্বয় বিবরণী তৈরি
        </h4>
        <p class="text-muted small mb-0">ব্যাংক বিবরণীর সমাপনী স্থিতি এবং ক্যাশ বুকের স্থিতির পার্থক্য সমন্বয়</p>
    </div>

    <!-- Bank Selection Form -->
    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
        <form method="GET" action="{{ route('reconciliation.create') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small fw-semibold text-secondary">ব্যাংক হিসাব নির্বাচন করুন <span class="text-danger">*</span></label>
                <select name="bank_account_id" class="form-select" required onchange="this.form.submit()">
                    @foreach ($bankAccounts as $ba)
                        <option value="{{ $ba->id }}" @selected($selectedBank && $selectedBank->id == $ba->id)>
                            {{ $ba->bank_name }} - {{ $ba->account_number }} ({{ $ba->account_title }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small fw-semibold text-secondary">বিবরণীর তারিখ (Statement Date) <span class="text-danger">*</span></label>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate }}" onchange="this.form.submit()">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-outline-success w-100">
                    <i class="fa-solid fa-rotate me-1"></i>ব্যালেন্স রিফ্রেশ
                </button>
            </div>
        </form>
    </div>

    @if ($selectedBank)
        <form method="POST" action="{{ route('reconciliation.store') }}" id="recForm">
            @csrf
            <input type="hidden" name="bank_account_id" value="{{ $selectedBank->id }}">
            <input type="hidden" name="statement_date" value="{{ $asOfDate }}">
            <input type="hidden" name="cash_book_balance" id="cashBookBalance" value="{{ $cashBookBalance }}">

            <!-- Balances Comparison Card -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <small class="text-muted d-block mb-1">ক্যাশ বুক অনুযায়ী ব্যালেন্স (Cash Book Balance):</small>
                        <h4 class="fw-bold text-primary mb-0">৳ {{ number_format($cashBookBalance, 2) }}</h4>
                        <small class="text-muted">লেজার ডাটা হতে স্বয়ংক্রিয়ভাবে সংগৃহীত</small>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <small class="text-muted d-block mb-1">ব্যাংক বিবরণী অনুযায়ী ব্যালেন্স (Bank Statement Ending Balance): <span class="text-danger">*</span></small>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">৳</span>
                            <input type="number" step="0.01" name="bank_statement_balance" id="bankStatementBalance" class="form-control fs-5 fw-bold" placeholder="0.00" required oninput="calculateReconciliation()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reconciling Adjustment Items Table -->
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-list-check me-2 text-primary"></i>সমন্বয়কারী আইটেমসমূহ (Reconciling Items)
                    </h6>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="addReconcileRow()">
                        <i class="fa-solid fa-plus me-1"></i>আইটেম যোগ করুন
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="recTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">ধরন (Type)</th>
                                <th style="width: 15%;">তারিখ</th>
                                <th style="width: 15%;">চেক / রেফারেন্স নং</th>
                                <th style="width: 20%;">বিবরণ (Description)</th>
                                <th style="width: 10%;">প্রভাব</th>
                                <th style="width: 10%; text-align: right;">টাকা (৳)</th>
                                <th style="width: 5%; text-align: center;">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody id="recBody">
                            <!-- Rows added dynamically -->
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr>
                                <th colspan="4" class="text-end fw-bold">সমন্বিত ব্যালেন্স (Reconciled Balance):</th>
                                <th colspan="2" class="text-end">
                                    <span id="reconciledBalanceDisplay" class="fw-bold text-success fs-6">৳ 0.00</span>
                                </th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end fw-bold">পার্থক্য (Difference with Cash Book):</th>
                                <th colspan="2" class="text-end">
                                    <span id="differenceDisplay" class="fw-bold text-danger fs-6">৳ 0.00</span>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-semibold text-secondary">নোট বা মন্তব্য</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="ব্যাংক সমন্বয়ের সারসংক্ষেপ..."></textarea>
            </div>

            <!-- Submit Button -->
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
                <a href="{{ route('reconciliation.index') }}" class="btn btn-outline-secondary rounded-pill px-4">বাতিল</a>
                <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i>ব্যাংক সমন্বয় সংরক্ষণ করুন
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
    var itemIndex = 0;

    function addReconcileRow() {
        var tbody = document.getElementById('recBody');
        var tr = document.createElement('tr');

        tr.innerHTML = `
            <td>
                <select name="items[${itemIndex}][type]" class="form-select form-select-sm" required onchange="handleTypeChange(this, ${itemIndex})">
                    <option value="outstanding_cheque">ইস্যুকৃত চেক যা ব্যাংকে উপস্থাপিত হয়নি (Outstanding Cheque)</option>
                    <option value="deposit_in_transit">জমা দেওয়া চেক যা ব্যাংকে ক্রেডিট হয়নি (Deposit in Transit)</option>
                    <option value="bank_charge">ব্যাংক চার্জ ও ভ্যাট কর্তন (Bank Charge)</option>
                    <option value="interest_credited">ব্যাংক সুদ জমা (Interest Credited)</option>
                    <option value="other_adjustment">অন্যান্য সমন্বয় (Other Adjustment)</option>
                </select>
            </td>
            <td>
                <input type="date" name="items[${itemIndex}][item_date]" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
            </td>
            <td>
                <input type="text" name="items[${itemIndex}][reference_no]" class="form-control form-control-sm" placeholder="চেক/ভাউচার নং">
            </td>
            <td>
                <input type="text" name="items[${itemIndex}][description]" class="form-control form-control-sm" placeholder="বিবরণ" required>
            </td>
            <td>
                <select name="items[${itemIndex}][effect]" id="effect_${itemIndex}" class="form-select form-select-sm item-effect" onchange="calculateReconciliation()">
                    <option value="less">বিয়োগ (-) Less</option>
                    <option value="add">যোগ (+) Add</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0.01" name="items[${itemIndex}][amount]" class="form-control form-control-sm text-end item-amount" placeholder="0.00" required oninput="calculateReconciliation()">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="this.closest('tr').remove(); calculateReconciliation();">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        itemIndex++;
        calculateReconciliation();
    }

    function handleTypeChange(select, idx) {
        var effectSelect = document.getElementById('effect_' + idx);
        if (select.value === 'outstanding_cheque') {
            effectSelect.value = 'less';
        } else if (select.value === 'deposit_in_transit') {
            effectSelect.value = 'add';
        } else if (select.value === 'bank_charge') {
            effectSelect.value = 'less';
        } else if (select.value === 'interest_credited') {
            effectSelect.value = 'add';
        }
        calculateReconciliation();
    }

    function calculateReconciliation() {
        var bankBalance = parseFloat(document.getElementById('bankStatementBalance').value) || 0;
        var cashBookBalance = parseFloat(document.getElementById('cashBookBalance').value) || 0;

        var rows = document.querySelectorAll('#recBody tr');
        var adjusted = bankBalance;

        rows.forEach(function (row) {
            var effect = row.querySelector('.item-effect').value;
            var amount = parseFloat(row.querySelector('.item-amount').value) || 0;
            if (effect === 'add') {
                adjusted += amount;
            } else {
                adjusted -= amount;
            }
        });

        var diff = adjusted - cashBookBalance;

        document.getElementById('reconciledBalanceDisplay').innerText = '৳ ' + adjusted.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        var diffSpan = document.getElementById('differenceDisplay');
        diffSpan.innerText = '৳ ' + diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (Math.abs(diff) < 0.01) {
            diffSpan.className = 'fw-bold text-success fs-6';
        } else {
            diffSpan.className = 'fw-bold text-danger fs-6';
        }
    }
</script>
@endpush
@endsection
