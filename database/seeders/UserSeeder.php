<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\Role;
use App\Models\User;
use App\Models\Voucher;
use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'system_admin')->first();
        if (! $adminRole) {
            $adminRole = Role::create([
                'name'         => 'system_admin',
                'display_name' => 'সিস্টেম অ্যাডমিনিস্ট্রেটর',
                'description'  => 'সম্পূর্ণ সিস্টেম ও ইউজার নিয়ন্ত্রণ',
            ]);
        }

        $allCostCenters  = CostCenter::all();
        $allBankAccounts = BankAccount::all();
        $allLeafCoa      = ChartOfAccount::leaf()->get();

        // ১. সিস্টেম অ্যাডমিনিস্ট্রেটর ১: ১৪০০১১১১১ (Nur Ahammad Akib)
        $user1 = User::updateOrCreate(
            ['username' => '140011111'],
            [
                'erp_no'           => '140011111',
                'name'             => 'Nur Ahammad Akib',
                'name_bn'          => 'নূর আহাম্মদ আকিব',
                'email'            => '140011111@bpdb.gov.bd',
                'phone'            => '01711000001',
                'designation'      => 'সিনিয়র সহকারী পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
                'designation_code' => '897',
                'cadre'            => 'Finance Cadre',
                'post_type'        => 'Officer',
                'office_name'      => 'হিসাব পরিদপ্তর, ওয়াপদা ভবন, ঢাকা',
                'password'         => Hash::make('Systemadmin123@'),
                'is_active'        => true,
            ]
        );
        $user1->roles()->sync([$adminRole->id]);
        $user1->costCenters()->sync($allCostCenters->pluck('id'));
        $user1->bankAccounts()->sync($allBankAccounts->pluck('id'));
        $user1->chartOfAccounts()->sync($allLeafCoa->pluck('id'));

        // ২. সিস্টেম অ্যাডমিনিস্ট্রেটর ২: ১৪০০৩৮৭৬৪ (Md. Asifuzzaman)
        $user2 = User::updateOrCreate(
            ['username' => '140038764'],
            [
                'erp_no'           => '140038764',
                'name'             => 'Md. Asifuzzaman',
                'name_bn'          => 'মোঃ আসিফুজ্জামান',
                'email'            => '140038764@bpdb.gov.bd',
                'phone'            => '01711000002',
                'designation'      => 'নির্বাহী প্রকৌশলী / সিস্টেম অ্যাডমিনিস্ট্রেটর',
                'designation_code' => '138',
                'cadre'            => 'Technical',
                'post_type'        => 'Officer',
                'office_name'      => 'কম্পিউটার সেন্টার, ওয়াপদা ভবন, ঢাকা',
                'password'         => Hash::make('Systemadmin123@'),
                'is_active'        => true,
            ]
        );
        $user2->roles()->sync([$adminRole->id]);
        $user2->costCenters()->sync($allCostCenters->pluck('id'));
        $user2->bankAccounts()->sync($allBankAccounts->pluck('id'));
        $user2->chartOfAccounts()->sync($allLeafCoa->pluck('id'));

        // ৩. পূর্বে তৈরিকৃত ভাউচার ও অডিট লগগুলোর ইউজার রেফারেন্স নতুন অ্যাডমিনের সাথে লিঙ্ক করা
        Voucher::whereNotIn('prepared_by_id', [$user1->id, $user2->id])
            ->orWhereNull('prepared_by_id')
            ->update(['prepared_by_id' => $user1->id]);

        Voucher::whereNotIn('reviewed_by_id', [$user1->id, $user2->id])
            ->whereNotNull('reviewed_by_id')
            ->update(['reviewed_by_id' => $user2->id]);

        Voucher::whereNotIn('approved_by_id', [$user1->id, $user2->id])
            ->whereNotNull('approved_by_id')
            ->update(['approved_by_id' => $user1->id]);

        AuditLog::whereNotIn('user_id', [$user1->id, $user2->id])
            ->whereNotNull('user_id')
            ->update(['user_id' => $user1->id]);

        // ৪. এই দুইজন ছাড়া অন্য সকল পুরাতন ডেমো লগইন আইডি (admin, director, officer_rao, accountant1, auditor1) সম্পূর্ণ মুছে ফেলা
        $otherUsers = User::whereNotIn('username', ['140011111', '140038764'])->get();
        foreach ($otherUsers as $oldUser) {
            $oldUser->roles()->detach();
            $oldUser->costCenters()->detach();
            $oldUser->bankAccounts()->detach();
            $oldUser->chartOfAccounts()->detach();
            $oldUser->delete();
        }
    }
}
