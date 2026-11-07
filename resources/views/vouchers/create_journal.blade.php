@extends('layouts.app')

@section('title', 'নতুন জার্নাল ভাউচার পোস্টিং | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('vouchers.index') }}" class="text-decoration-none text-secondary small">
            <i class="fa-solid fa-arrow-left me-1"></i>ভাউচার তালিকায় ফিরে যান
        </a>
        <h4 class="fw-bold text-dark mt-2 mb-1">
            <i class="fa-solid fa-book-journal-whills text-primary me-2"></i>নতুন সাধারণ জার্নাল ভাউচার (JV) পোস্টিং
        </h4>
        <p class="text-muted small mb-0">ডাবল-এন্ট্রি নিয়মে ডেবিট ও ক্রেডিট সমন্বয়কারী জার্নাল ভাউচার তৈরি করুন</p>
    </div>

    <form method="POST" action="{{ route('vouchers.store_journal') }}" id="jvForm">
        @csrf

        <!-- Top Header Info -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label for="date" class="form-label fw-semibold text-secondary small">ভাউচারের তারিখ <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="cost_center_id" class="form-label fw-semibold text-secondary small">কস্ট সেন্টার / দপ্তর <span class="text-danger">*</span></label>
                    <select class="form-select" id="cost_center_id" name="cost_center_id" required>
                        <option value="">কস্ট সেন্টার নির্বাচন করুন</option>
                        @foreach ($costCenters as $cc)
                            <option value="{{ $cc->id }}" @selected(old('cost_center_id') == $cc->id)>
                                {{ $cc->code }} - {{ $cc->name_bn }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-5">
                    <label for="narration" class="form-label fw-semibold text-secondary small">ভাউচারের সার্বিক বিবরণ (Narration) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="narration" name="narration" value="{{ old('narration') }}" placeholder="যেমন: জুলাই মাসের কর্মকর্তা ও কর্মচারীদের বেতন সমন্বয় জার্নাল" required>
                </div>
            </div>
        </div>

        <!-- Dynamic Multi-line Entries Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4 overflow-hidden">
            <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-list-check me-2 text-primary"></i>জার্নাল লেনদেন এন্ট্রি (Double Entry Lines)
                </h6>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="addRow()">
                    <i class="fa-solid fa-plus me-1"></i>সারি যোগ করুন (Add Row)
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0" id="entriesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 40%;">হিসাব খাত (Chart of Account) <span class="text-danger">*</span></th>
                            <th style="width: 15%;">ডেবিট (টাকা)</th>
                            <th style="width: 15%;">ক্রেডিট (টাকা)</th>
                            <th style="width: 20%;">নির্দিষ্ট বিবরণ (Line Narration)</th>
                            <th style="width: 5%; text-align: center;">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody id="entriesBody">
                        <!-- Initial Row 1 -->
                        <tr class="entry-row">
                            <td class="row-num text-muted fw-bold">1</td>
                            <td>
                                <select name="entries[0][chart_of_account_id]" class="form-select form-select-sm coa-select" required>
                                    <option value="">হিসাব খাত নির্বাচন করুন</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->account_code }} - {{ $acc->account_name_bn }} ({{ ucfirst($acc->account_type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="entries[0][debit_amount]" class="form-control form-control-sm text-end debit-input" placeholder="0.00" oninput="calculateTotals(this, 'debit')">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="entries[0][credit_amount]" class="form-control form-control-sm text-end credit-input" placeholder="0.00" oninput="calculateTotals(this, 'credit')">
                            </td>
                            <td>
                                <input type="text" name="entries[0][line_narration]" class="form-control form-control-sm" placeholder="ঐচ্ছিক বিবরণ">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRow(this)" disabled>
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Initial Row 2 -->
                        <tr class="entry-row">
                            <td class="row-num text-muted fw-bold">2</td>
                            <td>
                                <select name="entries[1][chart_of_account_id]" class="form-select form-select-sm coa-select" required>
                                    <option value="">হিসাব খাত নির্বাচন করুন</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->account_code }} - {{ $acc->account_name_bn }} ({{ ucfirst($acc->account_type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="entries[1][debit_amount]" class="form-control form-control-sm text-end debit-input" placeholder="0.00" oninput="calculateTotals(this, 'debit')">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="entries[1][credit_amount]" class="form-control form-control-sm text-end credit-input" placeholder="0.00" oninput="calculateTotals(this, 'credit')">
                            </td>
                            <td>
                                <input type="text" name="entries[1][line_narration]" class="form-control form-control-sm" placeholder="ঐচ্ছিক বিবরণ">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRow(this)" disabled>
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>

                    <!-- Calculation & Validation Footer -->
                    <tfoot class="table-light border-top border-2">
                        <tr>
                            <th colspan="2" class="text-end fw-bold">সর্বমোট যোগফল (Total):</th>
                            <th class="text-end">
                                <span id="totalDebitDisplay" class="fw-bold text-dark fs-6">৳ 0.00</span>
                            </th>
                            <th class="text-end">
                                <span id="totalCreditDisplay" class="fw-bold text-dark fs-6">৳ 0.00</span>
                            </th>
                            <th colspan="2">
                                <div id="balanceStatusBadge" class="badge bg-secondary p-2 d-inline-block">
                                    <i class="fa-solid fa-calculator me-1"></i>হিসাব অমিল (পার্থক্য: ৳ 0.00)
                                </div>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
            <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                বাতিল
            </a>
            <div class="d-flex gap-2">
                <button type="submit" name="submit_action" value="save_draft" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="fa-solid fa-file-pen me-1"></i>খসড়া হিসেবে সংরক্ষণ
                </button>
                <button type="submit" name="submit_action" value="submit_for_approval" id="submitBtn" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="fa-solid fa-paper-plane me-1"></i>অনুমোদনের জন্য পেশ করুন
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    var rowIndex = 2;

    function addRow() {
        var tbody = document.getElementById('entriesBody');
        var tr = document.createElement('tr');
        tr.className = 'entry-row';

        var optionsHtml = document.querySelector('.coa-select').innerHTML;

        tr.innerHTML = `
            <td class="row-num text-muted fw-bold">${tbody.children.length + 1}</td>
            <td>
                <select name="entries[${rowIndex}][chart_of_account_id]" class="form-select form-select-sm coa-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="entries[${rowIndex}][debit_amount]" class="form-control form-control-sm text-end debit-input" placeholder="0.00" oninput="calculateTotals(this, 'debit')">
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="entries[${rowIndex}][credit_amount]" class="form-control form-control-sm text-end credit-input" placeholder="0.00" oninput="calculateTotals(this, 'credit')">
            </td>
            <td>
                <input type="text" name="entries[${rowIndex}][line_narration]" class="form-control form-control-sm" placeholder="ঐচ্ছিক বিবরণ">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRow(this)">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);
        rowIndex++;
        updateRowNumbers();
    }

    function removeRow(btn) {
        var tbody = document.getElementById('entriesBody');
        if (tbody.children.length <= 2) {
            alert('কমপক্ষে দুইটি এন্ট্রি সারি থাকতে হবে।');
            return;
        }
        btn.closest('tr').remove();
        updateRowNumbers();
        recalc();
    }

    function updateRowNumbers() {
        var rows = document.querySelectorAll('#entriesBody tr');
        rows.forEach(function (row, idx) {
            row.querySelector('.row-num').innerText = idx + 1;
            var delBtn = row.querySelector('.btn-outline-danger');
            if (delBtn) {
                delBtn.disabled = (rows.length <= 2);
            }
        });
    }

    function calculateTotals(input, type) {
        var row = input.closest('tr');
        var otherInput = type === 'debit' ? row.querySelector('.credit-input') : row.querySelector('.debit-input');
        if (parseFloat(input.value) > 0) {
            otherInput.value = '';
        }
        recalc();
    }

    function recalc() {
        var totalDebit = 0;
        var totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(function (inp) {
            totalDebit += parseFloat(inp.value) || 0;
        });

        document.querySelectorAll('.credit-input').forEach(function (inp) {
            totalCredit += parseFloat(inp.value) || 0;
        });

        document.getElementById('totalDebitDisplay').innerText = '৳ ' + totalDebit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('totalCreditDisplay').innerText = '৳ ' + totalCredit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        var diff = Math.abs(totalDebit - totalCredit);
        var badge = document.getElementById('balanceStatusBadge');

        if (totalDebit > 0 && diff < 0.01) {
            badge.className = 'badge bg-success p-2 d-inline-block';
            badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>ডেবিট ও ক্রেডিট সমান - ভাউচার প্রস্তুত';
        } else {
            badge.className = 'badge bg-danger p-2 d-inline-block';
            badge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>অমিল: ৳ ' + diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    document.getElementById('jvForm').addEventListener('submit', function (e) {
        var totalDebit = 0;
        var totalCredit = 0;

        document.querySelectorAll('.debit-input').forEach(function (inp) {
            totalDebit += parseFloat(inp.value) || 0;
        });

        document.querySelectorAll('.credit-input').forEach(function (inp) {
            totalCredit += parseFloat(inp.value) || 0;
        });

        var diff = Math.abs(totalDebit - totalCredit);
        if (totalDebit <= 0 || diff > 0.01) {
            e.preventDefault();
            alert('ভাউচার জমা দেওয়ার পূর্বে অবশ্যই মোট ডেবিট এবং মোট ক্রেডিট সমান হতে হবে!');
        }
    });
</script>
@endpush
@endsection
