<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $currentFy = FiscalYear::where('is_current', true)->first();
        if (! $currentFy) return;

        $costCenters = CostCenter::take(4)->get();
        $expenseHeads = ChartOfAccount::leaf()->where('account_type', 'expense')->take(20)->get();

        foreach ($costCenters as $cc) {
            foreach ($expenseHeads as $head) {
                $baseAmount = match ($head->account_code) {
                    '501010' => 50000000.00, // Fuel
                    '501020' => 80000000.00, // Power Purchase
                    '501030' => 12000000.00, // Plant repair
                    '502010' => 6000000.00,  // Officers salary
                    '502020' => 9500000.00,  // Staff salary
                    '502030' => 4500000.00,  // House rent
                    '502040' => 1500000.00,  // Medical
                    '502050' => 800000.00,   // TA/DA
                    '502060' => 1200000.00,  // Utilities
                    '502070' => 600000.00,   // Stationery
                    '502080' => 900000.00,   // Vehicle fuel
                    default  => 300000.00,
                };

                Budget::updateOrCreate(
                    [
                        'fiscal_year_id'      => $currentFy->id,
                        'cost_center_id'      => $cc->id,
                        'chart_of_account_id' => $head->id,
                    ],
                    [
                        'original_budget' => $baseAmount,
                        'revised_budget'  => $baseAmount * 1.05,
                        'notes'           => 'অনুমোদিত বাজেট বরাদ্দ ২০২৫-২০২৬ অর্থবছর',
                    ]
                );
            }
        }
    }
}
