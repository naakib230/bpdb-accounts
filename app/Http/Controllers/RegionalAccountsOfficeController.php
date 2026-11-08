<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\CostCenter;
use App\Models\RegionalAccountsOffice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class RegionalAccountsOfficeController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $type   = $request->input('type');

        $query = RegionalAccountsOffice::withCount(['costCenters', 'bankAccounts', 'users']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('name_bn', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        $offices = $query->orderByRaw("CASE WHEN code = '140' THEN 1 ELSE 2 END")
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        $allCostCenters  = CostCenter::active()->orderBy('name_bn')->get();
        $allBankAccounts = BankAccount::active()->orderBy('bank_name')->get();

        return view('regional_accounts_offices.index', compact('offices', 'search', 'type', 'allCostCenters', 'allBankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', 'unique:regional_accounts_offices,code'],
            'name'      => ['required', 'string', 'max:255'],
            'name_bn'   => ['required', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'type'      => ['required', 'string', 'in:regional,central_overhead,generation'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $office = RegionalAccountsOffice::create($data);

        AuditLog::log('RAO_CREATED', "নতুন আঞ্চলিক হিসাব দপ্তর '{$office->name_bn}' (কোড: {$office->code}) তৈরি করা হয়েছে।", $office);

        return redirect()->route('raos.index')->with('success', "আঞ্চলিক হিসাব দপ্তর '{$office->name_bn}' সফলভাবে যুক্ত হয়েছে।");
    }

    public function update(Request $request, RegionalAccountsOffice $rao): RedirectResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50', 'unique:regional_accounts_offices,code,' . $rao->id],
            'name'      => ['required', 'string', 'max:255'],
            'name_bn'   => ['required', 'string', 'max:255'],
            'address'   => ['nullable', 'string'],
            'type'      => ['required', 'string', 'in:regional,central_overhead,generation'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $rao->update($data);

        AuditLog::log('RAO_UPDATED', "আঞ্চলিক হিসাব দপ্তর '{$rao->name_bn}' (কোড: {$rao->code}) তথ্য হালনাগাদ করা হয়েছে।", $rao);

        return redirect()->route('raos.index')->with('success', "আঞ্চলিক হিসাব দপ্তর '{$rao->name_bn}' এর তথ্য সফলভাবে সংরক্ষণ করা হয়েছে।");
    }

    public function assign(Request $request, RegionalAccountsOffice $rao): RedirectResponse
    {
        $request->validate([
            'cost_center_ids'   => ['nullable', 'array'],
            'cost_center_ids.*' => ['integer', 'exists:cost_centers,id'],
            'bank_account_ids'  => ['nullable', 'array'],
            'bank_account_ids.*'=> ['integer', 'exists:bank_accounts,id'],
        ]);

        $selectedCcIds = $request->input('cost_center_ids', []);
        $selectedBaIds = $request->input('bank_account_ids', []);

        // Cost Centers
        CostCenter::where('regional_accounts_office_id', $rao->id)
            ->whereNotIn('id', $selectedCcIds)
            ->update(['regional_accounts_office_id' => null]);

        if (! empty($selectedCcIds)) {
            CostCenter::whereIn('id', $selectedCcIds)->update(['regional_accounts_office_id' => $rao->id]);
        }

        // Bank Accounts
        BankAccount::where('regional_accounts_office_id', $rao->id)
            ->whereNotIn('id', $selectedBaIds)
            ->update(['regional_accounts_office_id' => null]);

        if (! empty($selectedBaIds)) {
            BankAccount::whereIn('id', $selectedBaIds)->update(['regional_accounts_office_id' => $rao->id]);
        }

        AuditLog::log('RAO_ASSIGNMENT_UPDATED', "'{$rao->name_bn}' দপ্তরে কস্ট সেন্টার ও ব্যাংক হিসাবের ম্যাপিং হালনাগাদ করা হয়েছে।", $rao);

        return redirect()->route('raos.index')->with('success', "'{$rao->name_bn}' এর অধীনে কস্ট সেন্টার ও ব্যাংক হিসাবের অ্যাসাইনমেন্ট সফলভাবে সংরক্ষিত হয়েছে।");
    }
}
