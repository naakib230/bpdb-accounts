<?php

namespace Database\Seeders;

use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FiscalYearSeeder extends Seeder
{
    public function run(): void
    {
        // 2024-2025 (Previous / Closed)
        $fy2425 = FiscalYear::updateOrCreate(
            ['name' => '2024-2025'],
            [
                'start_date' => '2024-07-01',
                'end_date'   => '2025-06-30',
                'is_current' => false,
                'is_closed'  => true,
                'closed_at'  => '2025-07-15 10:00:00',
            ]
        );

        // 2025-2026 (Current Active)
        $fy2526 = FiscalYear::updateOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date' => '2025-07-01',
                'end_date'   => '2026-06-30',
                'is_current' => true,
                'is_closed'  => false,
            ]
        );

        // Generate 12 monthly accounting periods for current FY
        $months = [
            1 => ['July 2025', '2025-07-01', '2025-07-31'],
            2 => ['August 2025', '2025-08-01', '2025-08-31'],
            3 => ['September 2025', '2025-09-01', '2025-09-30'],
            4 => ['October 2025', '2025-10-01', '2025-10-31'],
            5 => ['November 2025', '2025-11-01', '2025-11-30'],
            6 => ['December 2025', '2025-12-01', '2025-12-31'],
            7 => ['January 2026', '2026-01-01', '2026-01-31'],
            8 => ['February 2026', '2026-02-01', '2026-02-28'],
            9 => ['March 2026', '2026-03-01', '2026-03-31'],
            10 => ['April 2026', '2026-04-01', '2026-04-30'],
            11 => ['May 2026', '2026-05-01', '2026-05-31'],
            12 => ['June 2026', '2026-06-01', '2026-06-30'],
        ];

        foreach ($months as $num => [$name, $start, $end]) {
            AccountingPeriod::updateOrCreate(
                ['fiscal_year_id' => $fy2526->id, 'period_number' => $num],
                [
                    'period_name' => $name,
                    'start_date'  => $start,
                    'end_date'    => $end,
                    'status'      => 'open',
                ]
            );
        }

        // 2026-2027 (Upcoming)
        FiscalYear::updateOrCreate(
            ['name' => '2026-2027'],
            [
                'start_date' => '2026-07-01',
                'end_date'   => '2027-06-30',
                'is_current' => false,
                'is_closed'  => false,
            ]
        );
    }
}
