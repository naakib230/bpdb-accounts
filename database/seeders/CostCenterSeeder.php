<?php

namespace Database\Seeders;

use App\Models\CostCenter;
use App\Models\RegionalAccountsOffice;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    public function run(): void
    {
        $raoMap = RegionalAccountsOffice::pluck('id', 'code')->toArray();

        $costCenters = [
            [
                'code'                        => 'CC-101',
                'name'                        => 'Directorate of Accounts, BPDB Head Office',
                'name_bn'                     => 'হিসাব পরিদপ্তর, বিউবো, ওয়াপদা ভবন, ঢাকা',
                'regional_accounts_office_id' => $raoMap['140'] ?? null,
                'trial_balance_type'          => 'overhead',
                'location'                    => 'Motijheel, Dhaka',
                'division_or_zone'            => 'Dhaka Central',
                'contact_person'              => 'Md. Rafiqul Islam, Director',
                'phone'                       => '02-9550001',
            ],
            [
                'code'                        => 'CC-102',
                'name'                        => 'Regional Accounting Office (RAO), Chattogram',
                'name_bn'                     => 'আঞ্চলিক হিসাব দপ্তর (আহিদ), বিউবো, চট্টগ্রাম',
                'regional_accounts_office_id' => $raoMap['141'] ?? null,
                'trial_balance_type'          => 'revenue_distribution',
                'location'                    => 'Agrabad, Chattogram',
                'division_or_zone'            => 'Chattogram Zone',
                'contact_person'              => 'Tanvir Ahmed, Deputy Director',
                'phone'                       => '031-710002',
            ],
            [
                'code'                        => 'CC-103',
                'name'                        => 'Regional Accounting Office (RAO), Rajshahi',
                'name_bn'                     => 'আঞ্চলিক হিসাব দপ্তর (আহিদ), বিউবো, রাজশাহী',
                'regional_accounts_office_id' => $raoMap['1026'] ?? null,
                'trial_balance_type'          => 'revenue_distribution',
                'location'                    => 'Hetem Khan, Rajshahi',
                'division_or_zone'            => 'Rajshahi Zone',
                'contact_person'              => 'Farhana Yeasmin, Deputy Director',
                'phone'                       => '0721-770003',
            ],
            [
                'code'                        => 'CC-104',
                'name'                        => 'Regional Accounting Office (RAO), Cumilla',
                'name_bn'                     => 'আঞ্চলিক হিসাব দপ্তর (আহিদ), বিউবো, কুমিল্লা',
                'regional_accounts_office_id' => $raoMap['147'] ?? null,
                'trial_balance_type'          => 'revenue_distribution',
                'location'                    => 'Jangalia, Cumilla',
                'division_or_zone'            => 'Cumilla Zone',
                'contact_person'              => 'Mohammad Selim, Deputy Director',
                'phone'                       => '081-650004',
            ],
            [
                'code'                        => 'CC-105',
                'name'                        => 'Regional Accounting Office (RAO), Sylhet',
                'name_bn'                     => 'আঞ্চলিক হিসাব দপ্তর (আহিদ), বিউবো, সিলেট',
                'regional_accounts_office_id' => $raoMap['149'] ?? null,
                'trial_balance_type'          => 'revenue_distribution',
                'location'                    => 'Mendibagh, Sylhet',
                'division_or_zone'            => 'Sylhet Zone',
                'contact_person'              => 'Abdur Rashid, Deputy Director',
                'phone'                       => '0821-720005',
            ],
            [
                'code'                        => 'CC-106',
                'name'                        => 'Ghorashal Thermal Power Station',
                'name_bn'                     => 'ঘোড়াশাল তাপ বিদ্যুৎ কেন্দ্র, পলাশ, নরসিংদী',
                'regional_accounts_office_id' => $raoMap['157'] ?? null,
                'trial_balance_type'          => 'revenue_generation',
                'location'                    => 'Palash, Narsingdi',
                'division_or_zone'            => 'Generation Zone',
                'contact_person'              => 'Chief Engineer & Manager',
                'phone'                       => '02-9466006',
            ],
            [
                'code'                        => 'CC-107',
                'name'                        => 'Karnaphuli Hydro Power Station',
                'name_bn'                     => 'কর্ণফুলী জলবিদ্যুৎ কেন্দ্র, কাপ্তাই, রাঙামাটি',
                'regional_accounts_office_id' => $raoMap['160'] ?? null,
                'trial_balance_type'          => 'revenue_generation',
                'location'                    => 'Kaptai, Rangamati',
                'division_or_zone'            => 'Generation Zone',
                'contact_person'              => 'Manager (Accounts)',
                'phone'                       => '03529-56007',
            ],
            [
                'code'                        => 'CC-108',
                'name'                        => 'Siddhirganj 335 MW Combined Cycle Power Plant',
                'name_bn'                     => 'সিদ্ধিরগঞ্জ ৩৩৫ মেঃওঃ সিসিপিপি, নারায়ণগঞ্জ',
                'regional_accounts_office_id' => $raoMap['156'] ?? null,
                'trial_balance_type'          => 'revenue_generation',
                'location'                    => 'Siddhirganj, Narayanganj',
                'division_or_zone'            => 'Generation Zone',
                'contact_person'              => 'Superintending Engineer',
                'phone'                       => '02-7690008',
            ],
            [
                'code'                        => 'CC-109',
                'name'                        => 'Electric Distribution Division, Dhanmondi',
                'name_bn'                     => 'বিদ্যুৎ বিতরণ বিভাগ, ধানমন্ডি, বিউবো, ঢাকা',
                'regional_accounts_office_id' => $raoMap['139'] ?? null,
                'trial_balance_type'          => 'revenue_distribution',
                'location'                    => 'Dhanmondi, Dhaka',
                'division_or_zone'            => 'Dhaka Distribution',
                'contact_person'              => 'Executive Engineer',
                'phone'                       => '02-8610009',
            ],
            [
                'code'                        => 'CC-110',
                'name'                        => 'Electric Distribution Division, Stadium',
                'name_bn'                     => 'বিদ্যুৎ বিতরণ বিভাগ, স্টেডিয়াম, চট্টগ্রাম',
                'regional_accounts_office_id' => $raoMap['141'] ?? null,
                'trial_balance_type'          => 'revenue_distribution',
                'location'                    => 'Kotwali, Chattogram',
                'division_or_zone'            => 'Chattogram Distribution',
                'contact_person'              => 'Executive Engineer',
                'phone'                       => '031-620010',
            ],
            [
                'code'                        => 'CC-111',
                'name'                        => 'Ghorashal 6th Unit Re-powering Project',
                'name_bn'                     => 'ঘোড়াশাল ৬ষ্ঠ ইউনিট রি-পাওয়ারিং প্রকল্প',
                'regional_accounts_office_id' => $raoMap['157'] ?? null,
                'trial_balance_type'          => 'development',
                'location'                    => 'Palash, Narsingdi',
                'division_or_zone'            => 'Development Project',
                'contact_person'              => 'Project Director',
                'phone'                       => '02-9466011',
            ],
            [
                'code'                        => 'CC-112',
                'name'                        => 'Deposit Works Division, Cumilla',
                'name_bn'                     => 'আমানতি কাজ বিভাগ, কুমিল্লা',
                'regional_accounts_office_id' => $raoMap['147'] ?? null,
                'trial_balance_type'          => 'deposit_works',
                'location'                    => 'Jangalia, Cumilla',
                'division_or_zone'            => 'Deposit Works',
                'contact_person'              => 'Executive Engineer',
                'phone'                       => '081-650012',
            ],
        ];

        foreach ($costCenters as $cc) {
            CostCenter::updateOrCreate(['code' => $cc['code']], $cc);
        }
    }
}
