<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\FiscalYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AccountingPeriodController extends Controller
{
    public function index(): View
    {
        $fiscalYears = FiscalYear::with('periods')->orderByDesc('start_date')->get();
        return view('accounting_periods.index', compact('fiscalYears'));
    }

    public function storeYear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:50', 'unique:fiscal_years,name'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_current' => ['nullable', 'boolean'],
        ]);

        $isCurrent = $request->boolean('is_current');
        if ($isCurrent) {
            FiscalYear::where('is_current', true)->update(['is_current' => false]);
        }

        $fy = FiscalYear::create([
            'name'       => $data['name'],
            'start_date' => $data['start_date'],
            'end_date'   => $data['end_date'],
            'is_current' => $isCurrent,
        ]);

        // Auto-generate 12 monthly periods
        $start = \Carbon\Carbon::parse($data['start_date']);
        for ($i = 1; $i <= 12; $i++) {
            $periodStart = $start->copy()->addMonthsNoOverflow($i - 1)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();
            AccountingPeriod::create([
                'fiscal_year_id' => $fy->id,
                'period_name'    => $periodStart->format('F Y'),
                'period_number'  => $i,
                'start_date'     => $periodStart->toDateString(),
                'end_date'       => $periodEnd->toDateString(),
                'status'         => 'open',
            ]);
        }

        AuditLog::log('CREATE_FISCAL_YEAR', "নতুন অর্থবছর '{$fy->name}' ও ১২টি পিরিয়ড তৈরি করা হয়েছে।");

        return back()->with('success', 'নতুন অর্থবছর ও পিরিয়ডসমূহ সফলভাবে তৈরি হয়েছে।');
    }

    public function setCurrent(FiscalYear $fiscalYear): RedirectResponse
    {
        FiscalYear::where('is_current', true)->update(['is_current' => false]);
        $fiscalYear->update(['is_current' => true]);

        AuditLog::log('SET_CURRENT_FY', "অর্থবছর '{$fiscalYear->name}' কে বর্তমান সক্রিয় অর্থবছর হিসেবে নির্ধারণ করা হয়েছে।");

        return back()->with('success', "অর্থবছর '{$fiscalYear->name}' এখন বর্তমান সক্রিয় অর্থবছর।");
    }

    public function updatePeriodStatus(Request $request, AccountingPeriod $period): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:open,closed,locked']]);
        $period->update(['status' => $request->status]);

        AuditLog::log('UPDATE_PERIOD_STATUS', "হিসাব পিরিয়ড '{$period->period_name}' এর স্ট্যাটাস '{$request->status}' করা হয়েছে।");

        return back()->with('success', "পিরিয়ড '{$period->period_name}' সফলভাবে আপডেট হয়েছে।");
    }
}
