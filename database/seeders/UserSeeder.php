<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole    = Role::where('name', 'system_admin')->first();
        $directorRole = Role::where('name', 'director_accounts')->first();
        $officerRole  = Role::where('name', 'accounts_officer')->first();
        $acctRole     = Role::where('name', 'accountant')->first();
        $auditorRole  = Role::where('name', 'auditor')->first();

        $allCostCenters = CostCenter::all();
        $allBankAccounts = BankAccount::all();
        $allLeafCoa = ChartOfAccount::leaf()->get();

        // 1. System Admin
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name'        => 'System Administrator',
                'name_bn'     => 'সিস্টেম অ্যাডমিনিস্ট্রেটর',
                'email'       => 'admin@bpdb.gov.bd',
                'phone'       => '01711000001',
                'designation' => 'সিস্টেম অ্যানালিস্ট / অ্যাডমিন',
                'office_name' => 'কম্পিউটার সেন্টার, বিউবো, ঢাকা',
                'password'    => Hash::make('password123'),
                'is_active'   => true,
            ]
        );
        $admin->roles()->sync([$adminRole->id]);

        // 2. Director of Accounts
        $director = User::updateOrCreate(
            ['username' => 'director'],
            [
                'name'        => 'Md. Rafiqul Islam',
                'name_bn'     => 'মো. রফিকুল ইসলাম',
                'email'       => 'director.accounts@bpdb.gov.bd',
                'phone'       => '01711000002',
                'designation' => 'পরিচালক (হিসাব)',
                'office_name' => 'হিসাব পরিদপ্তর, ওয়াপদা ভবন, ঢাকা',
                'password'    => Hash::make('password123'),
                'is_active'   => true,
            ]
        );
        $director->roles()->sync([$directorRole->id, $officerRole->id]);
        $director->costCenters()->sync($allCostCenters->pluck('id'));
        $director->bankAccounts()->sync($allBankAccounts->pluck('id'));
        $director->chartOfAccounts()->sync($allLeafCoa->pluck('id'));

        // 3. Accounts Officer (RAO Chattogram)
        $officer = User::updateOrCreate(
            ['username' => 'officer_rao'],
            [
                'name'        => 'Tanvir Ahmed',
                'name_bn'     => 'তানভীর আহমেদ',
                'email'       => 'rao.ctg@bpdb.gov.bd',
                'phone'       => '01711000003',
                'designation' => 'উপ-পরিচালক (হিসাব)',
                'office_name' => 'আঞ্চলিক হিসাব দপ্তর (আহিদ), বিউবো, চট্টগ্রাম',
                'password'    => Hash::make('password123'),
                'is_active'   => true,
            ]
        );
        $officer->roles()->sync([$officerRole->id, $acctRole->id]);
        $ctgCenters = CostCenter::whereIn('code', ['CC-102', 'CC-107', 'CC-110'])->get();
        $officer->costCenters()->sync($ctgCenters->pluck('id'));
        $ctgBanks = BankAccount::where('bank_name', 'like', '%Agrani%')->get();
        $officer->bankAccounts()->sync($ctgBanks->pluck('id'));
        $officer->chartOfAccounts()->sync($allLeafCoa->pluck('id'));

        // 4. Senior Accountant / Cashier (Head Office)
        $accountant = User::updateOrCreate(
            ['username' => 'accountant1'],
            [
                'name'        => 'Md. Nasir Uddin',
                'name_bn'     => 'মো. নাসির উদ্দিন',
                'email'       => 'accountant.dhaka@bpdb.gov.bd',
                'phone'       => '01711000004',
                'designation' => 'উচ্চমান হিসাব সহকারী / ক্যাশিয়ার',
                'office_name' => 'হিসাব পরিদপ্তর, বিউবো, ঢাকা',
                'password'    => Hash::make('password123'),
                'is_active'   => true,
            ]
        );
        $accountant->roles()->sync([$acctRole->id]);
        $dhakaCenters = CostCenter::whereIn('code', ['CC-101', 'CC-106', 'CC-108', 'CC-109'])->get();
        $accountant->costCenters()->sync($dhakaCenters->pluck('id'));
        $dhakaBanks = BankAccount::whereIn('bank_name', ['Sonali Bank PLC', 'Janata Bank PLC'])->get();
        $accountant->bankAccounts()->sync($dhakaBanks->pluck('id'));
        $accountant->chartOfAccounts()->sync($allLeafCoa->pluck('id'));

        // 5. Auditor
        $auditor = User::updateOrCreate(
            ['username' => 'auditor1'],
            [
                'name'        => 'Farhana Sultana',
                'name_bn'     => 'ফারহানা সুলতানা',
                'email'       => 'audit@bpdb.gov.bd',
                'phone'       => '01711000005',
                'designation' => 'নিরীক্ষা কর্মকর্তা',
                'office_name' => 'অভ্যন্তরীণ নিরীক্ষা পরিদপ্তর, বিউবো, ঢাকা',
                'password'    => Hash::make('password123'),
                'is_active'   => true,
            ]
        );
        $auditor->roles()->sync([$auditorRole->id]);
        $auditor->costCenters()->sync($allCostCenters->pluck('id'));
        $auditor->bankAccounts()->sync($allBankAccounts->pluck('id'));
    }
}
