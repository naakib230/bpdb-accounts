<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>{{ $voucher->voucher_number }} - প্রিন্ট ভাউচার | বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'SolaimanLipi', 'Kalpurush', 'Segoe UI', Arial, sans-serif;
            background: #fff;
            color: #000;
            padding: 25px;
            font-size: 11pt;
        }
        .bpdb-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .bpdb-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 5px;
        }
        .table-voucher th, .table-voucher td {
            border: 1px solid #000 !important;
            padding: 6px 8px;
            font-size: 10.5pt;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 70px;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
            width: 22%;
            border-top: 1px dashed #000;
            padding-top: 5px;
            font-size: 10pt;
            font-weight: bold;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print mb-3 text-end">
        <button onclick="window.print()" class="btn btn-dark btn-sm rounded-pill px-3">
            <i class="fa-solid fa-print me-1"></i>প্রিন্ট করুন (Print)
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm rounded-pill px-3 ms-2">
            বন্ধ করুন (Close)
        </button>
    </div>

    <div class="bpdb-header">
        <img src="{{ asset('images/bpdb-logo.png') }}" alt="বিউবো" class="bpdb-logo">
        <h4 class="fw-bold mb-0">বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড (বিউবো)</h4>
        <h6 class="text-uppercase mb-1">BANGLADESH POWER DEVELOPMENT BOARD</h6>
        <p class="mb-1 fw-bold fs-5 text-decoration-underline">
            {{ $voucher->isCashVoucher() ? 'ক্যাশ ভাউচার (CASH VOUCHER)' : 'সাধারণ জার্নাল ভাউচার (JOURNAL VOUCHER)' }}
        </p>
        <small class="fw-semibold">{{ $voucher->costCenter?->name_bn }} ({{ $voucher->costCenter?->name }})</small>
    </div>

    <!-- Voucher Metadata -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <strong>ভাউচার নম্বর:</strong> {{ $voucher->voucher_number }}
        </div>
        <div class="col-6 text-end">
            <strong>তারিখ:</strong> {{ $voucher->date->format('d/m/Y') }}
        </div>
        <div class="col-6">
            <strong>অর্থবছর:</strong> {{ $voucher->fiscalYear?->name }}
        </div>
        <div class="col-6 text-end">
            <strong>স্ট্যাটাস:</strong> {{ $voucher->bangla_status }}
        </div>

        @if ($voucher->bankAccount)
            <div class="col-6">
                <strong>ব্যাংকের নাম ও শাখা:</strong> {{ $voucher->bankAccount->bank_name }}, {{ $voucher->bankAccount->branch_name }}
            </div>
            <div class="col-6 text-end">
                <strong>হিসাব নম্বর:</strong> {{ $voucher->bankAccount->account_number }}
            </div>
        @endif

        @if ($voucher->cheque_number)
            <div class="col-6">
                <strong>চেক নম্বর:</strong> {{ $voucher->cheque_number }}
            </div>
            <div class="col-6 text-end">
                <strong>চেকের তারিখ:</strong> {{ $voucher->cheque_date ? $voucher->cheque_date->format('d/m/Y') : '-' }}
            </div>
        @endif

        @if ($voucher->payee_payer_name)
            <div class="col-12">
                <strong>প্রাপক / প্রদানকারী:</strong> {{ $voucher->payee_payer_name }}
            </div>
        @endif
    </div>

    <!-- Entries Table -->
    <table class="table table-voucher w-100 mb-3">
        <thead>
            <tr class="table-light text-center">
                <th style="width: 5%;">ক্রঃ</th>
                <th style="width: 15%;">হিসাব কোড</th>
                <th style="width: 40%;">হিসাব খাত ও বিবরণ</th>
                <th style="width: 20%;">ডেবিট (টাকা)</th>
                <th style="width: 20%;">ক্রেডিট (টাকা)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($voucher->entries as $idx => $entry)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center fw-bold">{{ $entry->chartOfAccount?->account_code }}</td>
                    <td>
                        <div class="fw-bold">{{ $entry->chartOfAccount?->account_name_bn }}</div>
                        <small>{{ $entry->line_narration ?: $voucher->narration }}</small>
                    </td>
                    <td class="text-end fw-bold">
                        {{ $entry->debit_amount > 0 ? number_format($entry->debit_amount, 2) : '' }}
                    </td>
                    <td class="text-end fw-bold">
                        {{ $entry->credit_amount > 0 ? number_format($entry->credit_amount, 2) : '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold table-light">
                <td colspan="3" class="text-end">সর্বমোট টাকা:</td>
                <td class="text-end">৳ {{ number_format($voucher->total_debit, 2) }}</td>
                <td class="text-end">৳ {{ number_format($voucher->total_credit, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="mb-4">
        <strong>সার্বিক বিবরণ (Narration):</strong>
        <span>{{ $voucher->narration }}</span>
    </div>

    <!-- Official 4 Signatures Section -->
    <div class="signature-section">
        <div class="sig-box">
            <div>{{ $voucher->preparedBy?->name_bn ?: $voucher->preparedBy?->name }}</div>
            <div>প্রস্তুতকারী (Accountant)</div>
        </div>
        <div class="sig-box">
            <div>যাচাইকারী</div>
            <div>সহকারী হিসাব কর্মকর্তা</div>
        </div>
        <div class="sig-box">
            <div>{{ $voucher->approvedBy?->name_bn ?: $voucher->approvedBy?->name }}</div>
            <div>হিসাব কর্মকর্তা (Accounts Officer)</div>
        </div>
        <div class="sig-box">
            <div>পরিচালক (হিসাব)</div>
            <div>বিউবো, ঢাকা</div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            // Auto open print dialog if desired
        });
    </script>
</body>
</html>
