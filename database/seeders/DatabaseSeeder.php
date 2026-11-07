<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            FiscalYearSeeder::class,
            ChartOfAccountsSeeder::class,
            CostCenterSeeder::class,
            BankAccountSeeder::class,
            UserSeeder::class,
            BudgetSeeder::class,
            VoucherSeeder::class,
        ]);
    }
}
