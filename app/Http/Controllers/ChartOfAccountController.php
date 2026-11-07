<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChartOfAccountController extends Controller
{
    public function index(Request $request): View
    {
        $type = $request->input('type');
        $query = ChartOfAccount::with(['parent', 'users'])->orderBy('account_code');

        if ($type) {
            $query->where('account_type', $type);
        }

        $accounts = $query->paginate(20)->withQueryString();
        $parentCandidates = ChartOfAccount::where('is_leaf', false)->orderBy('account_code')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('chart_of_accounts.index', compact('accounts', 'parentCandidates', 'users', 'type'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_code'    => ['required', 'string', 'max:50', 'unique:chart_of_accounts,account_code'],
            'account_name'    => ['required', 'string', 'max:191'],
            'account_name_bn' => ['required', 'string', 'max:191'],
            'account_type'    => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id'       => ['nullable', 'exists:chart_of_accounts,id'],
            'normal_balance'  => ['required', 'in:debit,credit'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_leaf'         => ['nullable', 'boolean'],
            'description'     => ['nullable', 'string'],
        ]);

        $data['is_leaf'] = $request->boolean('is_leaf', true);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['current_balance'] = $data['opening_balance'];

        $coa = ChartOfAccount::create($data);
        AuditLog::log('CREATE_COA', "নতুন হিসাব কোড '{$coa->account_code} - {$coa->account_name_bn}' তৈরি করা হয়েছে।");

        return back()->with('success', 'হিসাব কোড সফলভাবে তৈরি করা হয়েছে।');
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $data = $request->validate([
            'account_code'    => ['required', 'string', 'max:50', 'unique:chart_of_accounts,account_code,' . $chartOfAccount->id],
            'account_name'    => ['required', 'string', 'max:191'],
            'account_name_bn' => ['required', 'string', 'max:191'],
            'account_type'    => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id'       => ['nullable', 'exists:chart_of_accounts,id'],
            'normal_balance'  => ['required', 'in:debit,credit'],
            'is_leaf'         => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
            'description'     => ['nullable', 'string'],
        ]);

        $data['is_leaf'] = $request->boolean('is_leaf', true);
        $data['is_active'] = $request->boolean('is_active', true);

        $chartOfAccount->update($data);
        AuditLog::log('UPDATE_COA', "হিসাব কোড '{$chartOfAccount->account_code}' আপডেট করা হয়েছে।");

        return back()->with('success', 'হিসাব কোড সফলভাবে আপডেট করা হয়েছে।');
    }

    public function assignUsers(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $request->validate(['user_ids' => ['nullable', 'array'], 'user_ids.*' => ['exists:users,id']]);
        $chartOfAccount->users()->sync($request->input('user_ids', []));

        AuditLog::log('ASSIGN_COA_USERS', "হিসাব কোড '{$chartOfAccount->account_code}' এ ব্যবহারকারী দায়িত্ব বণ্টন করা হয়েছে।");

        return back()->with('success', 'ব্যবহারকারী সফলভাবে অ্যাসাইন করা হয়েছে।');
    }

    public function export(): StreamedResponse
    {
        $fileName = 'BPDB_Chart_of_Accounts_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['হিসাব কোড', 'হিসাবের নাম (ইংরেজি)', 'হিসাবের নাম (বাংলা)', 'ধরন (Type)', 'মূল হিসাব কোড (Parent)', 'স্বাভাবিক জের (Normal Balance)', 'প্রারম্ভিক জের', 'লিফ অ্যাকাউন্ট']);

            foreach (ChartOfAccount::with('parent')->orderBy('account_code')->cursor() as $a) {
                fputcsv($handle, [
                    $a->account_code,
                    $a->account_name,
                    $a->account_name_bn,
                    $a->account_type,
                    $a->parent?->account_code,
                    $a->normal_balance,
                    number_format($a->opening_balance, 2),
                    $a->is_leaf ? 'Yes' : 'No',
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
            fputcsv($handle, ['account_code', 'account_name', 'account_name_bn', 'account_type', 'parent_code', 'normal_balance', 'opening_balance', 'is_leaf']);
            fputcsv($handle, ['502130', 'Audit & Inspection Fees', 'নিরীক্ষা ও পরিদর্শন ফি', 'expense', '500000', 'debit', '0.00', '1']);
            fclose($handle);
        }, 'Chart_of_Accounts_Import_Template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
                if (empty($row[0])) continue;
                $code       = trim($row[0]);
                $name       = trim($row[1] ?? $code);
                $nameBn     = trim($row[2] ?? $name);
                $type       = strtolower(trim($row[3] ?? 'expense'));
                $parentCode = trim($row[4] ?? '');
                $normalBal  = strtolower(trim($row[5] ?? 'debit'));
                $openingBal = (float) str_replace(',', '', trim($row[6] ?? '0'));
                $isLeaf     = trim($row[7] ?? '1') === '1' || strtolower(trim($row[7] ?? '')) === 'yes';

                $parentId = null;
                if ($parentCode) {
                    $parent = ChartOfAccount::where('account_code', $parentCode)->first();
                    $parentId = $parent?->id;
                }

                ChartOfAccount::updateOrCreate(
                    ['account_code' => $code],
                    [
                        'account_name'    => $name,
                        'account_name_bn' => $nameBn,
                        'account_type'    => in_array($type, ['asset', 'liability', 'equity', 'revenue', 'expense']) ? $type : 'expense',
                        'parent_id'       => $parentId,
                        'normal_balance'  => in_array($normalBal, ['debit', 'credit']) ? $normalBal : 'debit',
                        'opening_balance' => $openingBal,
                        'current_balance' => $openingBal,
                        'is_leaf'         => $isLeaf,
                        'is_active'       => true,
                    ]
                );
                $imported++;
            }
            fclose($handle);
        }

        AuditLog::log('IMPORT_COA', "মোট {$imported} টি হিসাব কোড ইম্পোর্ট/আপডেট করা হয়েছে।");

        return back()->with('success', "মোট {$imported} টি হিসাব কোড সফলভাবে ইম্পোর্ট/আপডেট করা হয়েছে।");
    }

    public function destroy(ChartOfAccount $chartOfAccount): RedirectResponse
    {
        if ($chartOfAccount->voucherEntries()->count() > 0) {
            return back()->with('error', 'এই হিসাব কোডে ভাউচার লেনদেন থাকায় এটি মুছে ফেলা সম্ভব নয়।');
        }

        if ($chartOfAccount->children()->count() > 0) {
            return back()->with('error', 'এই মূল হিসাবের অধীনে অধীনস্থ উপ-হিসাব থাকায় এটি মুছে ফেলা সম্ভব নয়।');
        }

        $chartOfAccount->users()->detach();
        $chartOfAccount->delete();

        AuditLog::log('DELETE_COA', "হিসাব কোড '{$chartOfAccount->account_code}' মুছে ফেলা হয়েছে।");

        return back()->with('success', 'হিসাব কোড মুছে ফেলা হয়েছে।');
    }
}
