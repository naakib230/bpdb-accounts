<?php

namespace Database\Seeders;

use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $currentFy = FiscalYear::where('is_current', true)->first();
        if (! $currentFy) return;

        $period = AccountingPeriod::where('fiscal_year_id', $currentFy->id)->first();
        $admin = User::where('username', 'admin')->first();
        $director = User::where('username', 'director')->first();
        $accountant = User::where('username', 'accountant1')->first();

        $ccHead = CostCenter::where('code', 'CC-101')->first();
        $ccGhorashal = CostCenter::where('code', 'CC-106')->first();

        $bankSonali = BankAccount::where('bank_name', 'Sonali Bank PLC')->first();
        $bankJanata = BankAccount::where('bank_name', 'Janata Bank PLC')->first();

        // Chart of Accounts lookup
        $coaCash       = ChartOfAccount::where('account_code', '101010')->first();
        $coaBankSonali = ChartOfAccount::where('account_code', '101020')->first();
        $coaBankJanata = ChartOfAccount::where('account_code', '101030')->first();
        $coaBulkRev    = ChartOfAccount::where('account_code', '401010')->first();
        $coaFuel       = ChartOfAccount::where('account_code', '501010')->first();
        $coaOffSalary  = ChartOfAccount::where('account_code', '502010')->first();
        $coaStaffSal   = ChartOfAccount::where('account_code', '502020')->first();
        $coaHouseRent  = ChartOfAccount::where('account_code', '502030')->first();
        $coaStationery = ChartOfAccount::where('account_code', '502070')->first();
        $coaTaxPayable = ChartOfAccount::where('account_code', '201030')->first();
        $coaGpfPayable = ChartOfAccount::where('account_code', '201040')->first();
        $coaAdvance    = ChartOfAccount::where('account_code', '101060')->first();
        $coaPayable    = ChartOfAccount::where('account_code', '201010')->first();

        // Voucher 1: CV (Bank Payment for Fuel)
        $v1 = Voucher::updateOrCreate(
            ['voucher_number' => 'CV-202507-0001'],
            [
                'voucher_type'         => 'cash_voucher',
                'cash_type'            => 'bank_payment',
                'date'                 => '2025-07-05',
                'fiscal_year_id'       => $currentFy->id,
                'accounting_period_id' => $period?->id,
                'cost_center_id'       => $ccGhorashal->id,
                'bank_account_id'      => $bankSonali->id,
                'cheque_number'        => 'CHQ-889101',
                'cheque_date'          => '2025-07-05',
                'payee_payer_name'     => 'তিতাস গ্যাস ট্রান্সমিশন ও ডিস্ট্রিবিউশন কোঃ লিঃ',
                'total_debit'          => 4500000.00,
                'total_credit'         => 4500000.00,
                'status'               => 'posted',
                'narration'            => 'ঘোড়াশাল বিদ্যুৎ কেন্দ্রের জুলাই মাসের গ্যাস বিল পরিশোধ বাবদ চেক প্রদান।',
                'prepared_by_id'       => $accountant->id,
                'approved_by_id'       => $director->id,
                'approved_at'          => '2025-07-06 11:30:00',
            ]
        );
        $v1->entries()->delete();
        $v1->entries()->create([
            'chart_of_account_id' => $coaFuel->id,
            'cost_center_id'      => $ccGhorashal->id,
            'debit_amount'        => 4500000.00,
            'credit_amount'       => 0.00,
            'line_narration'      => 'বিদ্যুৎ উৎপাদন গ্যাস জ্বালানি খরচ',
        ]);
        $v1->entries()->create([
            'chart_of_account_id' => $coaBankSonali->id,
            'cost_center_id'      => $ccGhorashal->id,
            'debit_amount'        => 0.00,
            'credit_amount'       => 4500000.00,
            'line_narration'      => 'সোনালী ব্যাংক হতে চেক মারফত পরিশোধ',
        ]);

        // Voucher 2: CV (Cash Payment for Stationery)
        $v2 = Voucher::updateOrCreate(
            ['voucher_number' => 'CV-202507-0002'],
            [
                'voucher_type'         => 'cash_voucher',
                'cash_type'            => 'cash_payment',
                'date'                 => '2025-07-10',
                'fiscal_year_id'       => $currentFy->id,
                'accounting_period_id' => $period?->id,
                'cost_center_id'       => $ccHead->id,
                'payee_payer_name'     => 'মেসার্স রহমান স্টেশনারি',
                'total_debit'          => 45000.00,
                'total_credit'         => 45000.00,
                'status'               => 'posted',
                'narration'            => 'হিসাব পরিদপ্তরের দৈনন্দিন দাপ্তরিক রেজিস্টার ও এ-ফোর কাগজ ক্রয়।',
                'prepared_by_id'       => $accountant->id,
                'approved_by_id'       => $director->id,
                'approved_at'          => '2025-07-10 16:00:00',
            ]
        );
        $v2->entries()->delete();
        $v2->entries()->create([
            'chart_of_account_id' => $coaStationery->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 45000.00,
            'credit_amount'       => 0.00,
            'line_narration'      => 'মুদ্রণ ও স্টেশনারি মালামাল বিল',
        ]);
        $v2->entries()->create([
            'chart_of_account_id' => $coaCash->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 0.00,
            'credit_amount'       => 45000.00,
            'line_narration'      => 'পেটি ক্যাশ হতে নগদ প্রদান',
        ]);

        // Voucher 3: CV (Bank Receipt for Bulk Power Sale)
        $v3 = Voucher::updateOrCreate(
            ['voucher_number' => 'CV-202507-0003'],
            [
                'voucher_type'         => 'cash_voucher',
                'cash_type'            => 'bank_receipt',
                'date'                 => '2025-07-15',
                'fiscal_year_id'       => $currentFy->id,
                'accounting_period_id' => $period?->id,
                'cost_center_id'       => $ccHead->id,
                'bank_account_id'      => $bankJanata->id,
                'payee_payer_name'     => 'ঢাকা পাওয়ার ডিস্ট্রিবিউশন কোম্পানি লিঃ (ডিপিডিসি)',
                'total_debit'          => 12000000.00,
                'total_credit'         => 12000000.00,
                'status'               => 'posted',
                'narration'            => 'ডিপিডিসি হতে পাইকারি বিদ্যুৎ সরবরাহ বাবদ জুলাই মাসের আংশিক বিল প্রাপ্তি।',
                'prepared_by_id'       => $accountant->id,
                'approved_by_id'       => $director->id,
                'approved_at'          => '2025-07-15 14:00:00',
            ]
        );
        $v3->entries()->delete();
        $v3->entries()->create([
            'chart_of_account_id' => $coaBankJanata->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 12000000.00,
            'credit_amount'       => 0.00,
            'line_narration'      => 'জনতা ব্যাংক একাউন্টে রাজস্ব জমা',
        ]);
        $v3->entries()->create([
            'chart_of_account_id' => $coaBulkRev->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 0.00,
            'credit_amount'       => 12000000.00,
            'line_narration'      => 'পাইকারি বিদ্যুৎ বিক্রয় রাজস্ব আয়',
        ]);

        // Voucher 4: JV (Monthly Payroll Voucher)
        $v4 = Voucher::updateOrCreate(
            ['voucher_number' => 'JV-202507-0001'],
            [
                'voucher_type'         => 'journal_voucher',
                'date'                 => '2025-07-28',
                'fiscal_year_id'       => $currentFy->id,
                'accounting_period_id' => $period?->id,
                'cost_center_id'       => $ccHead->id,
                'total_debit'          => 2750000.00,
                'total_credit'         => 2750000.00,
                'status'               => 'posted',
                'narration'            => 'হিসাব পরিদপ্তরের কর্মকর্তা ও কর্মচারীদের জুলাই ২০২৫ মাসের বেতন ও ভাতাদি সমন্বয় জার্নাল ভাউচার।',
                'prepared_by_id'       => $accountant->id,
                'approved_by_id'       => $director->id,
                'approved_at'          => '2025-07-28 17:00:00',
            ]
        );
        $v4->entries()->delete();
        $v4->entries()->create([
            'chart_of_account_id' => $coaOffSalary->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 850000.00,
            'credit_amount'       => 0.00,
            'line_narration'      => 'কর্মকর্তাদের মূল বেতন',
        ]);
        $v4->entries()->create([
            'chart_of_account_id' => $coaStaffSal->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 1250000.00,
            'credit_amount'       => 0.00,
            'line_narration'      => 'কর্মচারীদের মূল বেতন',
        ]);
        $v4->entries()->create([
            'chart_of_account_id' => $coaHouseRent->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 650000.00,
            'credit_amount'       => 0.00,
            'line_narration'      => 'বাড়ি ভাড়া ভাতা',
        ]);
        $v4->entries()->create([
            'chart_of_account_id' => $coaBankSonali->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 0.00,
            'credit_amount'       => 2400000.00,
            'line_narration'      => 'সোনালী ব্যাংক স্যালারি অ্যাকাউন্ট হতে নিট প্রদান',
        ]);
        $v4->entries()->create([
            'chart_of_account_id' => $coaGpfPayable->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 0.00,
            'credit_amount'       => 250000.00,
            'line_narration'      => 'জিপিএফ ফান্ড কর্তন প্রদেয়',
        ]);
        $v4->entries()->create([
            'chart_of_account_id' => $coaTaxPayable->id,
            'cost_center_id'      => $ccHead->id,
            'debit_amount'        => 0.00,
            'credit_amount'       => 100000.00,
            'line_narration'      => 'উৎস আয়কর কর্তন প্রদেয়',
        ]);
    }
}
