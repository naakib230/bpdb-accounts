<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $sonaliCoa = ChartOfAccount::where('account_code', '139')->first() ?? ChartOfAccount::first();
        $janataCoa = ChartOfAccount::where('account_code', '133')->first() ?? $sonaliCoa;
        $agraniCoa = ChartOfAccount::where('account_code', '135')->first() ?? $sonaliCoa;

        $dhakaCc = CostCenter::where('code', 'CC-101')->first();
        $ctgCc   = CostCenter::where('code', 'CC-102')->first();
        $ghorashalCc = CostCenter::where('code', 'CC-106')->first();

        $bankAccounts = [
            [
                'bank_name'           => 'Sonali Bank PLC',
                'branch_name'         => 'WAPDA Bhaban Corporate Branch, Dhaka',
                'account_number'      => '0010234567890',
                'account_title'       => 'BPDB Central Operation & Salary Account',
                'account_type'        => 'Current',
                'chart_of_account_id' => $sonaliCoa?->id ?? 1,
                'cost_center_id'      => $dhakaCc?->id ?? 1,
                'opening_balance'     => 15000000.00,
                'current_balance'     => 15000000.00,
            ],
            [
                'bank_name'           => 'Janata Bank PLC',
                'branch_name'         => 'Dilkusha Corporate Branch, Dhaka',
                'account_number'      => '0100456789123',
                'account_title'       => 'BPDB Bulk Power Collection SND Account',
                'account_type'        => 'SND',
                'chart_of_account_id' => $janataCoa?->id ?? 1,
                'cost_center_id'      => $dhakaCc?->id ?? 1,
                'opening_balance'     => 8500000.00,
                'current_balance'     => 8500000.00,
            ],
            [
                'bank_name'           => 'Agrani Bank PLC',
                'branch_name'         => 'Agrabad Corporate Branch, Chattogram',
                'account_number'      => '0200789123456',
                'account_title'       => 'BPDB RAO Chattogram Regional Account',
                'account_type'        => 'Current',
                'chart_of_account_id' => $agraniCoa?->id ?? 1,
                'cost_center_id'      => $ctgCc?->id ?? 2,
                'opening_balance'     => 6200000.00,
                'current_balance'     => 6200000.00,
            ],
            [
                'bank_name'           => 'Sonali Bank PLC',
                'branch_name'         => 'Palash Branch, Narsingdi',
                'account_number'      => '0010998877665',
                'account_title'       => 'Ghorashal Power Station O&M Account',
                'account_type'        => 'Current',
                'chart_of_account_id' => $sonaliCoa?->id ?? 1,
                'cost_center_id'      => $ghorashalCc?->id ?? 6,
                'opening_balance'     => 4500000.00,
                'current_balance'     => 4500000.00,
            ],
        ];

        foreach ($bankAccounts as $acc) {
            BankAccount::updateOrCreate(['account_number' => $acc['account_number']], $acc);
        }
    }
}
