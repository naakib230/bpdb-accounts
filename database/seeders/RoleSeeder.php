<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            // --- Central Roles (কেন্দ্রীয় দপ্তর / বিকেসি ও প্রশাসন) ---
            [
                'name'            => 'system_admin',
                'display_name'    => 'System Administrator',
                'display_name_bn' => 'সিস্টেম অ্যাডমিনিস্ট্রেটর',
                'description'     => 'সম্পূর্ণ সিস্টেম ও ইউজার নিয়ন্ত্রণ, কনফিগারেশন ও অফিস ব্যবস্থাপনা',
            ],
            [
                'name'            => 'dir_accounts',
                'display_name'    => 'Director of Accounts',
                'display_name_bn' => 'পরিচালক (হিসাব)',
                'description'     => 'বিউবোর সার্বিক প্রধান হিসাব পরিচালনা, চূড়ান্ত অনুমোদন ও আর্থিক প্রতিবেদন পর্যালোচনা',
            ],
            [
                'name'            => 'director_accounts',
                'display_name'    => 'Director of Accounts (Legacy)',
                'display_name_bn' => 'পরিচালক (হিসাব)',
                'description'     => 'বিউবোর প্রধান হিসাব পরিচালনা ও অনুমোদন',
            ],
            [
                'name'            => 'bkc_addl_dir',
                'display_name'    => 'BKC Additional Director',
                'display_name_bn' => 'বিকেসি অতিরিক্ত পরিচালক',
                'description'     => 'কেন্দ্রীয় বিকেসি শাখা পরিচালনা, নিরীক্ষা ও আর্থিক সমন্বয়',
            ],
            [
                'name'            => 'bkc_dd_1',
                'display_name'    => 'BKC Deputy Director 1',
                'display_name_bn' => 'বিকেসি উপপরিচালক - ১',
                'description'     => 'বিকেসি ১ম উপপরিচালক ডেস্ক ও অনুমোদন',
            ],
            [
                'name'            => 'bkc_dd_2',
                'display_name'    => 'BKC Deputy Director 2',
                'display_name_bn' => 'বিকেসি উপপরিচালক - ২',
                'description'     => 'বিকেসি ২য় উপপরিচালক ডেস্ক ও প্রতিবেদন যাচাই',
            ],
            [
                'name'            => 'bkc_ad_1',
                'display_name'    => 'BKC Assistant Director 1',
                'display_name_bn' => 'বিকেসি সহকারী পরিচালক - ১',
                'description'     => 'বিকেসি সহকারী পরিচালক শাখা - ১',
            ],
            [
                'name'            => 'bkc_ad_2',
                'display_name'    => 'BKC Assistant Director 2',
                'display_name_bn' => 'বিকেসি সহকারী পরিচালক - ২',
                'description'     => 'বিকেসি সহকারী পরিচালক শাখা - ২',
            ],
            [
                'name'            => 'bkc_accountant',
                'display_name'    => 'BKC Accountant',
                'display_name_bn' => 'বিকেসি হিসাবরক্ষক',
                'description'     => 'কেন্দ্রীয় বিকেসি হিসাবরক্ষণ ও সংকলন',
            ],
            [
                'name'            => 'bkc_aa',
                'display_name'    => 'BKC Assistant Accountant',
                'display_name_bn' => 'বিকেসি সহকারী হিসাবরক্ষক',
                'description'     => 'বিকেসি সহকারী হিসাবরক্ষণ কার্যাবলী',
            ],
            [
                'name'            => 'bkc_saa',
                'display_name'    => 'BKC Senior Accounts Assistant',
                'display_name_bn' => 'বিকেসি উচ্চমান হিসাব সহকারী',
                'description'     => 'বিকেসি নথি পর্যালোচনা ও ডাটা প্রসেসিং',
            ],
            [
                'name'            => 'bkc_jaa',
                'display_name'    => 'BKC Junior Accounts Assistant',
                'display_name_bn' => 'বিকেসি নিম্নমান হিসাব সহকারী',
                'description'     => 'বিকেসি ডাটা এন্ট্রি ও দৈনন্দিন নথি সংরক্ষণ',
            ],

            // --- Regional Accounts Office (RAO) Roles (আহিদ ভিত্তিক রোল) ---
            [
                'name'            => 'rao_initiator',
                'display_name'    => 'RAO Initiator',
                'display_name_bn' => 'আহিদ প্রস্তুতকারী / এন্ট্রি প্রদানকারী',
                'description'     => 'আঞ্চলিক হিসাব দপ্তরের ভাউচার তৈরি ও প্রাথমিক ডাটা এন্ট্রি',
            ],
            [
                'name'            => 'rao_certifier',
                'display_name'    => 'RAO Certifier',
                'display_name_bn' => 'আহিদ প্রত্যয়নকারী / নিরীক্ষক',
                'description'     => 'আঞ্চলিক হিসাব দপ্তরের ভাউচার নিরীক্ষণ, প্রত্যয়ন ও লেজার পরীক্ষা',
            ],
            [
                'name'            => 'rao_approver',
                'display_name'    => 'RAO Approver',
                'display_name_bn' => 'আহিদ অনুমোদনকারী / হিসাব কর্মকর্তা',
                'description'     => 'আঞ্চলিক হিসাব দপ্তরের ভাউচার ও ট্রায়াল ব্যালেন্স চূড়ান্ত অনুমোদন',
            ],

            // --- Compatibility / Legacy Support Roles ---
            [
                'name'            => 'accounts_officer',
                'display_name'    => 'Accounts Officer / RAO Head',
                'display_name_bn' => 'হিসাব কর্মকর্তা / আহিদ প্রধান',
                'description'     => 'আঞ্চলিক ও দপ্তর ভিত্তিক হিসাব তদারকি ও অনুমোদন',
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
