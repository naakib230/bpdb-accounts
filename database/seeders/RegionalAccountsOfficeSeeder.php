<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use App\Models\BankAccount;
use App\Models\RegionalAccountsOffice;
use Illuminate\Database\Seeder;

class RegionalAccountsOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $offices = [
            [
                'code'      => '1026',
                'name'      => 'RAO, Katakhali, Rajshahi',
                'name_bn'   => 'আহিদ, কাটাখালী, বিউবো, রাজশাহী',
                'address'   => 'Katakhali, Rajshahi',
                'type'      => 'regional',
            ],
            [
                'code'      => '139',
                'name'      => 'RAO, P&CO, Dhaka',
                'name_bn'   => 'আহিদ, পিএন্ডসিও, বিউবো, ঢাকা',
                'address'   => 'Biddut Bhabon (7th FLOUR) 1 No. Abdul Goni Road, Dhaka-1000',
                'type'      => 'regional',
            ],
            [
                'code'      => '141',
                'name'      => 'RAO, P&D, Chittagong',
                'name_bn'   => 'আহিদ, পিএন্ডডি, বিউবো, চট্টগ্রাম',
                'address'   => 'Biddut Bhabon, Agrabad Chittagong.',
                'type'      => 'regional',
            ],
            [
                'code'      => '142',
                'name'      => 'RAO, CES, Chittagong',
                'name_bn'   => 'আহিদ, সিইএস, বিউবো, চট্টগ্রাম',
                'address'   => 'Biddut bhabon (4th Flour) Agrabad, Chittagong',
                'type'      => 'regional',
            ],
            [
                'code'      => '143',
                'name'      => 'RAO, Tangail',
                'name_bn'   => 'আহিদ, বিউবো, টাঙাইল।',
                'address'   => 'RAO Tangail',
                'type'      => 'regional',
            ],
            [
                'code'      => '144',
                'name'      => 'RAO, BPDB, Mymensingh',
                'name_bn'   => 'আহিদ, বিউবো, ময়মনসিংহ।',
                'address'   => '137, Ganga Das Guha Road, Batirkol, Bidyut Bhaban, PDB, Mymensingh.',
                'type'      => 'regional',
            ],
            [
                'code'      => '145',
                'name'      => 'RAO, Shambhugonj, Mymensingh',
                'name_bn'   => 'আহিদ, বিউবো, শম্ভুগঞ্জ, ময়মনসিংহ',
                'address'   => 'Resonal Accounting Office, Shambhugonj, Mymensingh',
                'type'      => 'regional',
            ],
            [
                'code'      => '147',
                'name'      => 'RAO, Comilla',
                'name_bn'   => 'আহিদ, বিউবো, কুমিল্লা',
                'address'   => 'BPDB, Jangalia, Comilla',
                'type'      => 'regional',
            ],
            [
                'code'      => '148',
                'name'      => 'RAO, Noakhali',
                'name_bn'   => 'আহিদ, বিউবো, নোয়াখালী',
                'address'   => 'BPDB, Noakhali',
                'type'      => 'regional',
            ],
            [
                'code'      => '149',
                'name'      => 'RAO, Sylhet',
                'name_bn'   => 'আহিদ, বিউবো, সিলেট',
                'address'   => 'RAO, Sylhet',
                'type'      => 'regional',
            ],
            [
                'code'      => '150',
                'name'      => 'RAO, Rangpur Power Station',
                'name_bn'   => 'আহিদ, বিউবো, রংপুর',
                'address'   => 'Rao, BPDB, Rangpur',
                'type'      => 'generation',
            ],
            [
                'code'      => '151',
                'name'      => 'RAO, Coxs Bazar',
                'name_bn'   => 'আহিদ, কক্সবাজার',
                'address'   => 'RAO, Coxs Bazar',
                'type'      => 'regional',
            ],
            [
                'code'      => '152',
                'name'      => 'Deputy Director (Accts), RAO, Power Station, Barishal',
                'name_bn'   => 'আহিদ, বিউবো, বরিশাল',
                'address'   => 'Dapdapia, Rupatali, Barishal',
                'type'      => 'regional',
            ],
            [
                'code'      => '154',
                'name'      => 'RAO, Chittagong Power Station, Raozan',
                'name_bn'   => 'আহিদ, চট্টগ্রাম বিদ্যুৎ কেন্দ্র, বিউবো, চট্টগ্রাম',
                'address'   => 'Raozan, CHITTAGONG',
                'type'      => 'generation',
            ],
            [
                'code'      => '156',
                'name'      => 'RAO, Siddhiganj Power Station',
                'name_bn'   => 'আহিদ, সিদ্ধিরগঞ্জ বিদ্যুৎ কেন্দ্র, বিউবো, সিদ্ধিরগঞ্জ, নারায়নগঞ্জ',
                'address'   => 'SIDDHIRGANJ, Narayangonj',
                'type'      => 'generation',
            ],
            [
                'code'      => '157',
                'name'      => 'RAO, Ghorashal Power Station',
                'name_bn'   => 'আহিদ, ঘোড়াশাল বিদ্যুৎ কেন্দ্র, বিউবো, পলাশ, নরসিংদী',
                'address'   => 'Palash, Norsingdi',
                'type'      => 'generation',
            ],
            [
                'code'      => '158',
                'name'      => 'RAO, Shahjibazar Power Station',
                'name_bn'   => 'আহিদ, শাহজিবাজার বিদ্যুৎ কেন্দ্র, বিউবো, শাহজিবাজার',
                'address'   => 'RAO, Shahjibazar, Power Station, Hobiganj, Sylhet',
                'type'      => 'generation',
            ],
            [
                'code'      => '159',
                'name'      => 'RAO, Shikalbaha Power Station',
                'name_bn'   => 'আহিদ, শিকলবাহা বিদ্যুৎ কেন্দ্র, বিউবো, চট্টগ্রাম',
                'address'   => 'SIKALBAHA, CHITTAGONG',
                'type'      => 'generation',
            ],
            [
                'code'      => '160',
                'name'      => 'RAO, Kaptai Power Station',
                'name_bn'   => 'আহিদ, কাপ্তাই বিদ্যুৎ কেন্দ্র, বিউবো, কাপ্তাই',
                'address'   => 'Kaptai, Rangamati',
                'type'      => 'generation',
            ],
            [
                'code'      => '161',
                'name'      => 'RAO, Bheramara Power Station',
                'name_bn'   => 'আহিদ, ভেড়ামারা বিদ্যুৎ কেন্দ্র, বিউবো, কুষ্টিয়া',
                'address'   => 'Beramara, Kustia',
                'type'      => 'generation',
            ],
            [
                'code'      => '162',
                'name'      => 'RAO, Khulna Power Station',
                'name_bn'   => 'আহিদ, খুলনা বিদ্যুৎ কেন্দ্র, বিউবো, খুলনা',
                'address'   => 'Rao, Khulna, Power Station, BPDB Khulna',
                'type'      => 'generation',
            ],
            [
                'code'      => '163',
                'name'      => 'RAO, Fenchuganj Power Station',
                'name_bn'   => 'আহিদ, ফেঞ্চুগঞ্জ বিদ্যুৎ কেন্দ্র, বিউবো, সিলেট ',
                'address'   => 'FENCHUGONJ, SYLHET',
                'type'      => 'generation',
            ],
            [
                'code'      => '164',
                'name'      => 'RAO, Baghabari Power Station',
                'name_bn'   => 'আহিদ, বাঘাবাড়ী বিদ্যুৎ কেন্দ্র, বিউবো, সিরাজগঞ্জ',
                'address'   => 'BAGHABARI, SIRAJGONJ',
                'type'      => 'generation',
            ],
            [
                'code'      => '165',
                'name'      => 'RAO, Barapukuria Power Station',
                'name_bn'   => 'আহিদ, বড়পুকুরিয়া কয়লা ভিত্তিক তাপ বিদ্যুৎ কেন্দ্র, বিউবো, দিনাজপুর',
                'address'   => 'BARAPUKURIA, DINAJPUR',
                'type'      => 'generation',
            ],
            [
                'code'      => '487',
                'name'      => 'RAO, Tongi, Gazipur.',
                'name_bn'   => 'আহিদ, বিউবো, টঙ্গী, গাজীপুর',
                'address'   => 'Tongi 80MW GTPS, BPDB, Tongi, Gazipur.',
                'type'      => 'generation',
            ],
            [
                'code'      => '568',
                'name'      => 'RAO, Chandpur',
                'name_bn'   => 'আহিদ, বিউবো, চাঁদপুর',
                'address'   => 'Chandpur',
                'type'      => 'regional',
            ],
            [
                'code'      => '636',
                'name'      => 'RAO, Bibiyana Power Station, Habiganj',
                'name_bn'   => 'আহিদ, বিবিয়ানা বিদ্যুৎ কেন্দ্র, বিউবো, হবিগঞ্জ',
                'address'   => 'Parkul, Nabiganj, Habiganj',
                'type'      => 'generation',
            ],
            [
                'code'      => '140',
                'name'      => 'COOAC, Dhaka',
                'name_bn'   => 'কেন্দ্রীয় উপরিখাত দপ্তর হিসাব সেল (কোয়াক), বিউবো, ঢাকা',
                'address'   => 'BCIC Bhaban (3rd & 4th Floor), Motijheel C/A, Dhaka-1000',
                'type'      => 'central_overhead',
            ],
        ];

        foreach ($offices as $data) {
            RegionalAccountsOffice::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true])
            );
        }

        // বিদ্যমান কস্ট সেন্টার ও ব্যাংক অ্যাকাউন্টগুলোকে স্বয়ংক্রিয়ভাবে প্রাসঙ্গিক RAO তে ম্যাপ করা
        $ghorashal = RegionalAccountsOffice::where('code', '157')->first();
        $cooac     = RegionalAccountsOffice::where('code', '140')->first();
        $pcoDhaka  = RegionalAccountsOffice::where('code', '139')->first();
        $chittagong = RegionalAccountsOffice::where('code', '141')->first();
        $comilla   = RegionalAccountsOffice::where('code', '147')->first();
        $rajshahi  = RegionalAccountsOffice::where('code', '1026')->first();

        // কস্ট সেন্টার ম্যাপ
        CostCenter::where('code', 'like', '%1001%')->orWhere('name', 'like', '%Ghorashal%')->update([
            'regional_accounts_office_id' => $ghorashal?->id,
            'trial_balance_type'          => 'revenue_generation',
        ]);

        CostCenter::where('code', 'like', '%2001%')->orWhere('name', 'like', '%Motijheel%')->orWhere('name', 'like', '%Head Office%')->update([
            'regional_accounts_office_id' => $cooac?->id,
            'trial_balance_type'          => 'overhead',
        ]);

        CostCenter::where('name', 'like', '%Chattogram%')->orWhere('name', 'like', '%Agrabad%')->update([
            'regional_accounts_office_id' => $chittagong?->id,
            'trial_balance_type'          => 'revenue_distribution',
        ]);

        CostCenter::where('name', 'like', '%Rajshahi%')->orWhere('name', 'like', '%Hetem%')->update([
            'regional_accounts_office_id' => $rajshahi?->id,
            'trial_balance_type'          => 'revenue_distribution',
        ]);

        CostCenter::where('name', 'like', '%Cumilla%')->orWhere('name', 'like', '%Jangalia%')->update([
            'regional_accounts_office_id' => $comilla?->id,
            'trial_balance_type'          => 'revenue_distribution',
        ]);

        // অবশিষ্ট যেগুলোর এখনো RAO অ্যাসাইন হয়নি সেগুলোকে ডিফল্ট COOAC এ যুক্ত করা
        CostCenter::whereNull('regional_accounts_office_id')->update([
            'regional_accounts_office_id' => $cooac?->id,
            'trial_balance_type'          => 'overhead',
        ]);

        // ব্যাংক অ্যাকাউন্টগুলোকে তাদের কস্ট সেন্টারের RAO তে সিঙ্ক করা
        foreach (BankAccount::all() as $ba) {
            $ccRaoId = $ba->costCenter?->regional_accounts_office_id ?? $cooac?->id;
            $ba->update(['regional_accounts_office_id' => $ccRaoId]);
        }
    }
}
