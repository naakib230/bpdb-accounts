<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $fiscalYears = FiscalYear::orderByDesc('start_date')->get();
        $currentFy = FiscalYear::current();
        $selectedFyId = $request->input('fiscal_year_id', $currentFy?->id);

        $costCenters = CostCenter::active()->orderBy('name_bn')->get();
        $selectedCcId = $request->input('cost_center_id', $costCenters->first()?->id);

        $expenseHeads = ChartOfAccount::leaf()->where('account_type', 'expense')->orderBy('account_code')->get();

        $budgets = [];
        if ($selectedFyId && $selectedCcId) {
            $existing = Budget::where('fiscal_year_id', $selectedFyId)
                ->where('cost_center_id', $selectedCcId)
                ->get()
                ->keyBy('chart_of_account_id');

            foreach ($expenseHeads as $head) {
                $b = $existing->get($head->id);
                $budgets[] = [
                    'chart_of_account_id' => $head->id,
                    'account_code'        => $head->account_code,
                    'account_name_bn'     => $head->account_name_bn,
                    'original_budget'     => $b ? (float)$b->original_budget : 0.0,
                    'revised_budget'      => $b ? (float)$b->revised_budget : 0.0,
                    'utilized_amount'     => $b ? $b->utilized_amount : 0.0,
                    'notes'               => $b ? $b->notes : '',
                ];
            }
        }

        return view('budget.index', compact(
            'fiscalYears',
            'currentFy',
            'selectedFyId',
            'costCenters',
            'selectedCcId',
            'budgets'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_year_id' => ['required', 'exists:fiscal_years,id'],
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'allocations'    => ['required', 'array'],
            'allocations.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'allocations.*.original_budget'     => ['nullable', 'numeric', 'min:0'],
            'allocations.*.revised_budget'      => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes'               => ['nullable', 'string', 'max:255'],
        ]);

        $saved = 0;
        foreach ($data['allocations'] as $item) {
            $original = (float) ($item['original_budget'] ?? 0);
            $revised  = (float) ($item['revised_budget'] ?? 0);

            if ($original > 0 || $revised > 0) {
                Budget::updateOrCreate(
                    [
                        'fiscal_year_id'      => $data['fiscal_year_id'],
                        'cost_center_id'      => $data['cost_center_id'],
                        'chart_of_account_id' => $item['chart_of_account_id'],
                    ],
                    [
                        'original_budget' => $original,
                        'revised_budget'  => $revised,
                        'notes'           => $item['notes'] ?? null,
                        'created_by'      => Auth::id(),
                    ]
                );
                $saved++;
            }
        }

        AuditLog::log('SAVE_BUDGET', "কস্ট সেন্টারের বাজেট বরাদ্দ সংরক্ষণ করা হয়েছে (মোট খাত: {$saved})।");

        return back()->with('success', "বাজেট বরাদ্দ সফলভাবে সংরক্ষণ করা হয়েছে (মোট খাত: {$saved})।");
    }
}
