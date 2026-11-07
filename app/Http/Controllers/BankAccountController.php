<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankAccountController extends Controller
{
    public function index(): View
    {
        $bankAccounts = BankAccount::with(['chartOfAccount', 'costCenter', 'users'])->orderBy('bank_name')->paginate(15);
        $users = User::where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::active()->orderBy('name_bn')->get();
        $bankCoas = ChartOfAccount::leaf()->where('account_type', 'asset')->where('account_code', 'like', '101%')->get();

        return view('bank_accounts.index', compact('bankAccounts', 'users', 'costCenters', 'bankCoas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_name'           => ['required', 'string', 'max:100'],
            'branch_name'         => ['required', 'string', 'max:100'],
            'account_number'      => ['required', 'string', 'max:50', 'unique:bank_accounts,account_number'],
            'account_title'       => ['required', 'string', 'max:191'],
            'account_type'        => ['required', 'string', 'max:50'],
            'chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'cost_center_id'      => ['nullable', 'exists:cost_centers,id'],
            'opening_balance'     => ['required', 'numeric', 'min:0'],
        ]);

        $data['current_balance'] = $data['opening_balance'];
        $bank = BankAccount::create($data);

        AuditLog::log('CREATE_BANK_ACCOUNT', "নতুন ব্যাংক হিসাব '{$bank->bank_name} ({$bank->account_number})' যুক্ত করা হয়েছে।");

        return back()->with('success', 'ব্যাংক হিসাব সফলভাবে যুক্ত করা হয়েছে।');
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $data = $request->validate([
            'bank_name'           => ['required', 'string', 'max:100'],
            'branch_name'         => ['required', 'string', 'max:100'],
            'account_number'      => ['required', 'string', 'max:50', 'unique:bank_accounts,account_number,' . $bankAccount->id],
            'account_title'       => ['required', 'string', 'max:191'],
            'account_type'        => ['required', 'string', 'max:50'],
            'chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'cost_center_id'      => ['nullable', 'exists:cost_centers,id'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $bankAccount->update($data);

        AuditLog::log('UPDATE_BANK_ACCOUNT', "ব্যাংক হিসাব '{$bankAccount->account_number}' আপডেট করা হয়েছে।");

        return back()->with('success', 'ব্যাংক হিসাব সফলভাবে আপডেট করা হয়েছে।');
    }

    public function assignUsers(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $request->validate(['user_ids' => ['nullable', 'array'], 'user_ids.*' => ['exists:users,id']]);
        $bankAccount->users()->sync($request->input('user_ids', []));

        AuditLog::log('ASSIGN_BANK_ACCOUNT_USERS', "ব্যাংক হিসাব '{$bankAccount->account_number}' এ ব্যবহারকারী দায়িত্ব বণ্টন করা হয়েছে।");

        return back()->with('success', 'ব্যবহারকারী সফলভাবে অ্যাসাইন করা হয়েছে।');
    }

    public function export(): StreamedResponse
    {
        $fileName = 'BPDB_Bank_Accounts_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ব্যাংকের নাম', 'শাখা', 'হিসাব নম্বর', 'হিসাবের শিরোনাম', 'হিসাবের ধরন', 'জিএল কোড', 'কস্ট সেন্টার', 'প্রারম্ভিক জের']);

            foreach (BankAccount::with(['chartOfAccount', 'costCenter'])->cursor() as $acc) {
                fputcsv($handle, [
                    $acc->bank_name,
                    $acc->branch_name,
                    $acc->account_number,
                    $acc->account_title,
                    $acc->account_type,
                    $acc->chartOfAccount?->account_code,
                    $acc->costCenter?->name_bn,
                    number_format($acc->opening_balance, 2),
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['bank_name', 'branch_name', 'account_number', 'account_title', 'account_type', 'gl_account_code', 'opening_balance']);
            fputcsv($handle, ['Rupali Bank PLC', 'Motijheel Corporate Branch', '0300123456789', 'BPDB Regional Operation Account', 'Current', '101020', '500000.00']);
            fclose($handle);
        }, 'Bank_Account_Import_Template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls']]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $imported = 0;
        $handle = fopen($path, 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle);
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (empty($row[2])) continue; // account_number required
                $bankName    = trim($row[0] ?? '');
                $branchName  = trim($row[1] ?? '');
                $accNum      = trim($row[2]);
                $accTitle    = trim($row[3] ?? $bankName);
                $accType     = trim($row[4] ?? 'Current');
                $glCode      = trim($row[5] ?? '101020');
                $openingBal  = (float) str_replace(',', '', trim($row[6] ?? '0'));

                $coa = ChartOfAccount::where('account_code', $glCode)->first()
                    ?? ChartOfAccount::where('account_code', '101020')->first();

                if (! $coa) continue;

                BankAccount::updateOrCreate(
                    ['account_number' => $accNum],
                    [
                        'bank_name'           => $bankName,
                        'branch_name'         => $branchName,
                        'account_title'       => $accTitle,
                        'account_type'        => $accType,
                        'chart_of_account_id' => $coa->id,
                        'opening_balance'     => $openingBal,
                        'current_balance'     => $openingBal,
                        'is_active'           => true,
                    ]
                );
                $imported++;
            }
            fclose($handle);
        }

        AuditLog::log('IMPORT_BANK_ACCOUNTS', "মোট {$imported} টি ব্যাংক হিসাব ইম্পোর্ট/আপডেট করা হয়েছে।");

        return back()->with('success', "মোট {$imported} টি ব্যাংক হিসাব সফলভাবে ইম্পোর্ট/আপডেট করা হয়েছে।");
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        if ($bankAccount->vouchers()->count() > 0) {
            return back()->with('error', 'এই ব্যাংক হিসাবের অধীনে ভাউচার লেনদেন থাকায় এটি মুছে ফেলা সম্ভব নয়।');
        }

        $bankAccount->users()->detach();
        $bankAccount->delete();

        AuditLog::log('DELETE_BANK_ACCOUNT', "ব্যাংক হিসাব '{$bankAccount->account_number}' মুছে ফেলা হয়েছে।");

        return back()->with('success', 'ব্যাংক হিসাব মুছে ফেলা হয়েছে।');
    }
}
