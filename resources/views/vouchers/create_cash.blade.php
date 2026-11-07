@extends('layouts.app')

@section('title', 'নতুন ক্যাশ ভাউচার পোস্টিং | বিউবো অ্যাকাউন্টস')

@section('content')
<div class="container-fluid px-0">
    <div class="mb-4">
        <a href="{{ route('vouchers.index') }}" class="text-decoration-none text-secondary small">
            <i class="fa-solid fa-arrow-left me-1"></i>ভাউচার তালিকায় ফিরে যান
        </a>
        <h4 class="fw-bold text-dark mt-2 mb-1">
            <i class="fa-solid fa-money-bill-transfer text-success me-2"></i>নতুন ক্যাশ / ব্যাংক ভাউচার পোস্টিং
        </h4>
        <p class="text-muted small mb-0">নগদ প্রদান, নগদ প্রাপ্তি, ব্যাংক চেক প্রদান অথবা ব্যাংক রাজস্ব প্রাপ্তি পোস্টিং ফরম</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
        <form method="POST" action="{{ route('vouchers.store_cash') }}">
            @csrf

            <div class="row g-3 mb-4">
                <!-- 1. Voucher Date -->
                <div class="col-12 col-md-3">
                    <label for="date" class="form-label fw-semibold text-secondary small">ভাউচারের তারিখ <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
                </div>

                <!-- 2. Cash Voucher Type -->
                <div class="col-12 col-md-4">
                    <label for="cash_type" class="form-label fw-semibold text-secondary small">লেনদেনের ধরন (Transaction Type) <span class="text-danger">*</span></label>
                    <select class="form-select" id="cash_type" name="cash_type" required onchange="toggleCashFields()">
                        <option value="bank_payment" @selected(old('cash_type') === 'bank_payment')>ব্যাংক চেক মারফত ব্যয় প্রদান (Bank Payment)</option>
                        <option value="cash_payment" @selected(old('cash_type') === 'cash_payment')>নগদ ক্যাশ হতে ব্যয় প্রদান (Cash Payment)</option>
                        <option value="bank_receipt" @selected(old('cash_type') === 'bank_receipt')>ব্যাংক হিসাবে রাজস্ব/অর্থ প্রাপ্তি (Bank Receipt)</option>
                        <option value="cash_receipt" @selected(old('cash_type') === 'cash_receipt')>নগদ ক্যাশ রাজস্ব/অর্থ প্রাপ্তি (Cash Receipt)</option>
                    </select>
                </div>

                <!-- 3. Cost Center -->
                <div class="col-12 col-md-5">
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
            </div>

            <!-- Bank Specific Fields (Conditional) -->
            <div class="row g-3 mb-4 p-3 rounded-3 bg-light border" id="bankFieldsRow">
                <div class="col-12 col-md-6">
                    <label for="bank_account_id" class="form-label fw-semibold text-secondary small">সংশ্লিষ্ট ব্যাংক হিসাব <span class="text-danger">*</span></label>
                    <select class="form-select" id="bank_account_id" name="bank_account_id">
                        <option value="">ব্যাংক হিসাব নির্বাচন করুন</option>
                        @foreach ($bankAccounts as $ba)
                            <option value="{{ $ba->id }}" @selected(old('bank_account_id') == $ba->id)>
                                {{ $ba->bank_name }} - {{ $ba->account_number }} ({{ $ba->account_title }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="cheque_number" class="form-label fw-semibold text-secondary small">চেক নম্বর (Cheque No)</label>
                    <input type="text" class="form-control" id="cheque_number" name="cheque_number" value="{{ old('cheque_number') }}" placeholder="যেমন: CHQ-123456">
                </div>

                <div class="col-12 col-md-3">
                    <label for="cheque_date" class="form-label fw-semibold text-secondary small">চেকের তারিখ (Cheque Date)</label>
                    <input type="date" class="form-control" id="cheque_date" name="cheque_date" value="{{ old('cheque_date') }}">
                </div>
            </div>

            <!-- Contra Account & Amount -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label for="payee_payer_name" class="form-label fw-semibold text-secondary small">প্রাপক / প্রদানকারীর নাম</label>
                    <input type="text" class="form-control" id="payee_payer_name" name="payee_payer_name" value="{{ old('payee_payer_name') }}" placeholder="যেমন: সরবরাহকারী/কর্মকর্তা/গ্রাহক">
                </div>

                <div class="col-12 col-md-5">
                    <label for="contra_account_id" class="form-label fw-semibold text-secondary small" id="contraLabel">
                        বিপরীত হিসাব খাত (Expense/Revenue Account Head) <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="contra_account_id" name="contra_account_id" required>
                        <option value="">হিসাব খাত নির্বাচন করুন</option>
                        @foreach ($expenseAndAssetAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('contra_account_id') == $acc->id)>
                                {{ $acc->account_code }} - {{ $acc->account_name_bn }} ({{ ucfirst($acc->account_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label for="amount" class="form-label fw-semibold text-secondary small">টাকার পরিমাণ (Amount in BDT) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light fw-bold">৳</span>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                    </div>
                </div>
            </div>

            <!-- Narration -->
            <div class="mb-4">
                <label for="narration" class="form-label fw-semibold text-secondary small">ভাউচারের বিবরণ ও ব্যাখ্যা (Narration) <span class="text-danger">*</span></label>
                <textarea class="form-control" id="narration" name="narration" rows="3" placeholder="লেনদেনের বিস্তারিত বিবরণ, অনুমোদনের রেফারেন্স ইত্যাদি উল্লেখ করুন" required>{{ old('narration') }}</textarea>
            </div>

            <!-- Submission Buttons -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="{{ route('vouchers.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    বাতিল
                </a>
                <div class="d-flex gap-2">
                    <button type="submit" name="submit_action" value="save_draft" class="btn btn-outline-success rounded-pill px-4">
                        <i class="fa-solid fa-file-pen me-1"></i>খসড়া হিসেবে সংরক্ষণ
                    </button>
                    <button type="submit" name="submit_action" value="submit_for_approval" class="btn btn-success rounded-pill px-4 shadow-sm">
                        <i class="fa-solid fa-paper-plane me-1"></i>অনুমোদনের জন্য পেশ করুন
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleCashFields() {
        var type = document.getElementById('cash_type').value;
        var row = document.getElementById('bankFieldsRow');
        var bankSelect = document.getElementById('bank_account_id');

        if (type.startsWith('bank_')) {
            row.style.display = 'flex';
            bankSelect.required = true;
        } else {
            row.style.display = 'none';
            bankSelect.required = false;
        }
    }
    document.addEventListener('DOMContentLoaded', toggleCashFields);
</script>
@endpush
@endsection
