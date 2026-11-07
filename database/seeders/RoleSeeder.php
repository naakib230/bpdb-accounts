<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'            => 'system_admin',
                'display_name'    => 'System Administrator',
                'display_name_bn' => 'সিস্টেম অ্যাডমিনিস্ট্রেটর',
                'description'     => 'সর্বোচ্চ সিস্টেম নিয়ন্ত্রণ, কনফিগারেশন ও ব্যবহারকারী ব্যবস্থাপনা',
            ],
            [
                'name'            => 'director_accounts',
                'display_name'    => 'Director of Accounts',
                'display_name_bn' => 'পরিচালক (হিসাব)',
                'description'     => 'বিউবোর প্রধান হিসাব পরিচালনা, অনুমোদন ও আর্থিক প্রতিবেদন পর্যালোচনা',
            ],
            [
                'name'            => 'accounts_officer',
                'display_name'    => 'Accounts Officer / RAO Head',
                'display_name_bn' => 'হিসাব কর্মকর্তা / আহিদ প্রধান',
                'description'     => 'আঞ্চলিক ও দপ্তর ভিত্তিক হিসাব তদারকি, ভাউচার যাচাই ও অনুমোদন',
            ],
            [
                'name'            => 'accountant',
                'display_name'    => 'Accountant / Cashier',
                'display_name_bn' => 'হিসাবরক্ষক / ক্যাশিয়ার',
                'description'     => 'দৈনন্দিন ক্যাশ ও জার্নাল ভাউচার পোস্টিং, বিল যাচাই ও এন্ট্রি',
            ],
            [
                'name'            => 'auditor',
                'display_name'    => 'Auditor / Viewer',
                'display_name_bn' => 'নিরীক্ষক / পরিদর্শক',
                'description'     => 'আর্থিক হিসাব নিরীক্ষা, লেজার ও ট্রায়াল ব্যালেন্স পর্যবেক্ষণ',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
