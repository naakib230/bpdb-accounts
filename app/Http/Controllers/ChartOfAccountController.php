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
        $search = $request->input('search');
        $controlHead = $request->input('control_head');

        $query = ChartOfAccount::with(['parent', 'users'])->orderBy('account_code');

        if ($type) {
            $query->where('account_type', $type);
        }

        if ($controlHead) {
            $query->where('control_head', $controlHead);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('control_head', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate(25)->withQueryString();
        $parentCandidates = ChartOfAccount::where('is_leaf', false)->orderBy('account_code')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $controlHeads = ChartOfAccount::whereNotNull('control_head')->distinct()->pluck('control_head')->sort();

        return view('chart_of_accounts.index', compact('accounts', 'parentCandidates', 'users', 'type', 'search', 'controlHead', 'controlHeads'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_code'    => ['required', 'string', 'max:50', 'unique:chart_of_accounts,account_code'],
            'account_name'    => ['required', 'string', 'max:255'],
            'account_name_bn' => ['nullable', 'string', 'max:255'],
            'account_type'    => ['required', 'in:asset,liability,equity,revenue,expense'],
            'income_balance'  => ['nullable', 'string', 'max:50'],
            'control_head'    => ['nullable', 'string', 'max:191'],
            'parent_id'       => ['nullable', 'exists:chart_of_accounts,id'],
            'normal_balance'  => ['required', 'in:debit,credit'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_leaf'         => ['nullable', 'boolean'],
            'direct_posting'  => ['nullable', 'boolean'],
            'description'     => ['nullable', 'string'],
        ]);

        $data['is_leaf'] = $request->boolean('is_leaf', true);
        $data['direct_posting'] = $request->boolean('direct_posting', $data['is_leaf']);
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['current_balance'] = $data['opening_balance'];

        $coa = ChartOfAccount::create($data);
        AuditLog::log('CREATE_COA', "নতুন হিসাব কোড '{$coa->account_code} - {$coa->account_name}' তৈরি করা হয়েছে।");

        return back()->with('success', 'হিসাব কোড সফলভাবে তৈরি করা হয়েছে।');
    }

    public function update(Request $request, ChartOfAccount $chartOfAccount): RedirectResponse
    {
        $data = $request->validate([
            'account_code'    => ['required', 'string', 'max:50', 'unique:chart_of_accounts,account_code,' . $chartOfAccount->id],
            'account_name'    => ['required', 'string', 'max:255'],
            'account_name_bn' => ['nullable', 'string', 'max:255'],
            'account_type'    => ['required', 'in:asset,liability,equity,revenue,expense'],
            'income_balance'  => ['nullable', 'string', 'max:50'],
            'control_head'    => ['nullable', 'string', 'max:191'],
            'parent_id'       => ['nullable', 'exists:chart_of_accounts,id'],
            'normal_balance'  => ['required', 'in:debit,credit'],
            'is_leaf'         => ['nullable', 'boolean'],
            'direct_posting'  => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
            'description'     => ['nullable', 'string'],
        ]);

        $data['is_leaf'] = $request->boolean('is_leaf', true);
        $data['direct_posting'] = $request->boolean('direct_posting', $data['is_leaf']);
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

            fputcsv($handle, ['Chart of Accounts Code', 'Chart of Accounts Name', 'Income/Balance', 'Account Category', 'Control Head', 'Direct Posting', 'Normal Balance', 'Current Balance']);

            foreach (ChartOfAccount::orderBy('account_code')->cursor() as $a) {
                $category = match ($a->account_type) {
                    'asset'     => 'Assets',
                    'liability' => 'Liabilities',
                    'equity'    => 'Equity',
                    'revenue'   => 'Income',
                    'expense'   => 'Expense',
                    default     => 'Assets',
                };

                $incomeBalance = $a->income_balance ?: (in_array($a->account_type, ['asset', 'liability', 'equity']) ? 'Balance Sheet' : 'Income Statement');

                fputcsv($handle, [
                    $a->account_code,
                    $a->account_name,
                    $incomeBalance,
                    $category,
                    $a->control_head ?? '',
                    $a->direct_posting ? 'True' : 'False',
                    $a->normal_balance,
                    $a->current_balance,
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
            fputcsv($handle, ['Chart of Accounts Code', 'Chart of Accounts Name', 'Income/Balance', 'Account Category', 'Control Head', 'Direct Posting']);
            fputcsv($handle, ['101.70', 'Land and Land Rights', 'Balance Sheet', 'Assets', 'Property,plant and equipment (PPE) (net)', 'True']);
            fputcsv($handle, ['130', 'Cash in Bank-Assets Insurance Fund', 'Balance Sheet', 'Assets', 'Cash & Cash Equivalents', 'False']);
            fputcsv($handle, ['501.01', 'Sales-LT-A-Residential', 'Income Statement', 'Income', 'Sales Revenue', 'True']);
            fputcsv($handle, ['611.20A', 'Basic Pay', 'Income Statement', 'Expense', 'Cost of Goods Sold', 'True']);
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
            while (($row = fgetcsv($handle, 4000, ',')) !== false) {
                if (empty($row[0])) continue;

                $code = trim($row[0]);
                $name = trim($row[1] ?? $code);
                $incomeBalance = trim($row[2] ?? '');
                $categoryRaw = trim($row[3] ?? '');
                $controlHead = trim($row[4] ?? '') ?: null;
                $directPosting = strtolower(trim($row[5] ?? 'true')) === 'true';

                $accountType = match (strtolower($categoryRaw)) {
                    'assets', 'asset'           => 'asset',
                    'liabilities', 'liability' => 'liability',
                    'equity'                    => 'equity',
                    'income', 'revenue'         => 'revenue',
                    'expense', 'expenses'       => 'expense',
                    default                     => (strtolower($incomeBalance) === 'income statement' ? 'expense' : 'asset'),
                };

                $normalBalance = match ($accountType) {
                    'asset', 'expense'               => 'debit',
                    'liability', 'equity', 'revenue' => 'credit',
                    default                          => 'debit',
                };

                // Contra accounts override
                if (in_array($code, ['115', '116', '122', '143'], true)) {
                    $normalBalance = 'credit';
                }

                ChartOfAccount::updateOrCreate(
                    ['account_code' => $code],
                    [
                        'account_name'    => $name,
                        'account_name_bn' => $name,
                        'account_type'    => $accountType,
                        'income_balance'  => $incomeBalance ?: (in_array($accountType, ['asset', 'liability', 'equity']) ? 'Balance Sheet' : 'Income Statement'),
                        'control_head'    => $controlHead,
                        'normal_balance'  => $normalBalance,
                        'is_leaf'         => $directPosting,
                        'direct_posting'  => $directPosting,
                        'is_active'       => true,
                    ]
                );
                $imported++;
            }
            fclose($handle);
        }

        AuditLog::log('IMPORT_COA', "মোট {$imported} টি চার্ট অব অ্যাকাউন্টস ইম্পোর্ট/আপডেট করা হয়েছে।");

        return back()->with('success', "মোট {$imported} টি চার্ট অব অ্যাকাউন্টস সফলভাবে ইম্পোর্ট/আপডেট করা হয়েছে।");
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
