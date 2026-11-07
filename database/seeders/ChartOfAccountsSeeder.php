<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // ১. পূর্বের সকল চার্ট অব অ্যাকাউন্টস ও সংশ্লিষ্ট রেফারেন্স মুছে ফেলা
        DB::table('bank_reconciliation_items')->delete();
        DB::table('bank_reconciliations')->delete();
        DB::table('voucher_entries')->delete();
        DB::table('vouchers')->delete();
        DB::table('user_bank_accounts')->delete();
        DB::table('bank_accounts')->delete();
        DB::table('budgets')->delete();
        DB::table('user_chart_of_accounts')->delete();
        DB::table('chart_of_accounts')->delete();

        // ২. নতুন CSV ফাইল হতে ১,৫৭৫টি চার্ট অব অ্যাকাউন্টস লোড করা
        $csvPath = database_path('data/bpdb_chart_of_accounts.csv');
        if (! file_exists($csvPath)) {
            return;
        }

        $now = now();
        $batch = [];
        $handle = fopen($csvPath, 'r');

        if ($handle !== false) {
            // Header read
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle, 4000, ',')) !== false) {
                if (empty($row[0])) continue;

                $code          = trim($row[0]);
                $name          = trim($row[1] ?? $code);
                $incomeBalance = trim($row[2] ?? '');
                $categoryRaw   = trim($row[3] ?? '');
                $controlHead   = trim($row[4] ?? '') ?: null;
                $directPosting = strtolower(trim($row[5] ?? 'true')) === 'true';

                $accountType = match (strtolower($categoryRaw)) {
                    'assets', 'asset'           => 'asset',
                    'liabilities', 'liability' => 'liability',
                    'equity'                    => 'equity',
                    'income', 'revenue'         => 'revenue',
                    'expense', 'expenses'       => 'expense',
                    default                     => (strtolower($incomeBalance) === 'income statement' ? 'expense' : 'asset'),
                };

                $normalBalance = match ($accountType) {
                    'asset', 'expense'               => 'debit',
                    'liability', 'equity', 'revenue' => 'credit',
                    default                          => 'debit',
                };

                // Contra accounts (Accumulated depreciation, provision)
                if (in_array($code, ['115', '116', '122', '143'], true)) {
                    $normalBalance = 'credit';
                }

                $batch[] = [
                    'account_code'    => $code,
                    'account_name'    => $name,
                    'account_name_bn' => $name,
                    'account_type'    => $accountType,
                    'income_balance'  => $incomeBalance ?: (in_array($accountType, ['asset', 'liability', 'equity']) ? 'Balance Sheet' : 'Income Statement'),
                    'control_head'    => $controlHead,
                    'parent_id'       => null,
                    'normal_balance'  => $normalBalance,
                    'opening_balance' => 0.00,
                    'current_balance' => 0.00,
                    'is_leaf'         => $directPosting,
                    'direct_posting'  => $directPosting,
                    'is_active'       => true,
                    'description'     => $controlHead ? "Control Head: {$controlHead}" : null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];

                if (count($batch) >= 200) {
                    DB::table('chart_of_accounts')->insert($batch);
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                DB::table('chart_of_accounts')->insert($batch);
            }

            fclose($handle);
        }
    }
}
