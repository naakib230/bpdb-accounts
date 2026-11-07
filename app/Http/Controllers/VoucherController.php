<?php

namespace App\Http\Controllers;

use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Voucher::with(['costCenter', 'bankAccount', 'preparedBy', 'approvedBy'])
            ->latest('date')
            ->latest('id');

        if ($request->filled('type')) {
            $query->where('voucher_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cost_center_id')) {
            $query->where('cost_center_id', $request->cost_center_id);
        } elseif (! $user->isSystemAdmin() && ! $user->isDirectorAccounts()) {
            $query->whereIn('cost_center_id', $user->costCenters->pluck('id'));
        }

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('voucher_number', 'ilike', "%{$search}%")
                  ->orWhere('narration', 'ilike', "%{$search}%")
                  ->orWhere('payee_payer_name', 'ilike', "%{$search}%");
            });
        }

        $vouchers = $query->paginate(20)->withQueryString();

        $costCenters = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? CostCenter::active()->get()
            : $user->costCenters;

        return view('vouchers.index', compact('vouchers', 'costCenters'));
    }

    public function createCash(): View
    {
        $user = Auth::user();
        $currentFy = FiscalYear::current();
        
        $costCenters = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? CostCenter::active()->get()
            : $user->costCenters;

        $bankAccounts = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? BankAccount::active()->with('chartOfAccount')->get()
            : $user->bankAccounts()->with('chartOfAccount')->get();

        $cashAccount = ChartOfAccount::where('account_code', '101010')->first();
        $expenseAndAssetAccounts = ChartOfAccount::leaf()->whereIn('account_type', ['expense', 'asset', 'liability', 'revenue'])->get();

        $periods = $currentFy ? $currentFy->periods()->where('status', 'open')->get() : collect();

        return view('vouchers.create_cash', compact(
            'costCenters',
            'bankAccounts',
            'cashAccount',
            'expenseAndAssetAccounts',
            'currentFy',
            'periods'
        ));
    }

    public function storeCash(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date'                  => ['required', 'date'],
            'cash_type'             => ['required', 'in:cash_payment,cash_receipt,bank_payment,bank_receipt'],
            'cost_center_id'        => ['required', 'exists:cost_centers,id'],
            'bank_account_id'       => ['nullable', 'required_if:cash_type,bank_payment,bank_receipt', 'exists:bank_accounts,id'],
            'cheque_number'         => ['nullable', 'string', 'max:50'],
            'cheque_date'           => ['nullable', 'date'],
            'payee_payer_name'      => ['nullable', 'string', 'max:191'],
            'contra_account_id'     => ['required', 'exists:chart_of_accounts,id'],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'narration'             => ['required', 'string'],
            'submit_action'         => ['required', 'in:save_draft,submit_for_approval'],
        ]);

        $currentFy = FiscalYear::current();
        if (! $currentFy) {
            throw ValidationException::withMessages(['date' => 'কোনো সক্রিয় অর্থবছর পাওয়া যায়নি।']);
        }

        // Determine cash/bank account head
        $cashHead = null;
        if (str_starts_with($data['cash_type'], 'cash_')) {
            $cashHead = ChartOfAccount::where('account_code', '101010')->first();
        } else {
            $bankAcc = BankAccount::with('chartOfAccount')->find($data['bank_account_id']);
            $cashHead = $bankAcc?->chartOfAccount;
        }

        if (! $cashHead) {
            throw ValidationException::withMessages(['bank_account_id' => 'সংশ্লিষ্ট ব্যাংক বা ক্যাশ অ্যাকাউন্ট হেড পাওয়া যায়নি।']);
        }

        DB::transaction(function () use ($data, $currentFy, $cashHead, $request) {
            $prefix = 'CV-' . date('Ym', strtotime($data['date'])) . '-';
            $lastCount = Voucher::where('voucher_number', 'like', $prefix . '%')->count() + 1;
            $voucherNumber = $prefix . str_pad((string)$lastCount, 4, '0', STR_PAD_LEFT);

            $status = $data['submit_action'] === 'submit_for_approval' ? 'submitted' : 'draft';
            $amount = (float) $data['amount'];

            $voucher = Voucher::create([
                'voucher_number'       => $voucherNumber,
                'voucher_type'         => 'cash_voucher',
                'cash_type'            => $data['cash_type'],
                'date'                 => $data['date'],
                'fiscal_year_id'       => $currentFy->id,
                'cost_center_id'       => $data['cost_center_id'],
                'bank_account_id'      => $data['bank_account_id'] ?? null,
                'cheque_number'        => $data['cheque_number'] ?? null,
                'cheque_date'          => $data['cheque_date'] ?? null,
                'payee_payer_name'     => $data['payee_payer_name'] ?? null,
                'total_debit'          => $amount,
                'total_credit'         => $amount,
                'status'               => $status,
                'narration'            => $data['narration'],
                'prepared_by_id'       => Auth::id(),
            ]);

            // Add Entries based on Payment vs Receipt
            if (in_array($data['cash_type'], ['cash_payment', 'bank_payment'], true)) {
                // Debit: Contra Account (Expense / Payable / Asset)
                $voucher->entries()->create([
                    'chart_of_account_id' => $data['contra_account_id'],
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => $amount,
                    'credit_amount'       => 0.00,
                    'line_narration'      => $data['narration'],
                ]);
                // Credit: Cash or Bank
                $voucher->entries()->create([
                    'chart_of_account_id' => $cashHead->id,
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => 0.00,
                    'credit_amount'       => $amount,
                    'line_narration'      => 'ক্যাশ / ব্যাংক প্রদান',
                ]);
            } else {
                // Receipt
                // Debit: Cash or Bank
                $voucher->entries()->create([
                    'chart_of_account_id' => $cashHead->id,
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => $amount,
                    'credit_amount'       => 0.00,
                    'line_narration'      => 'ক্যাশ / ব্যাংক প্রাপ্তি',
                ]);
                // Credit: Contra Account (Revenue / Receivable)
                $voucher->entries()->create([
                    'chart_of_account_id' => $data['contra_account_id'],
                    'cost_center_id'      => $data['cost_center_id'],
                    'debit_amount'        => 0.00,
                    'credit_amount'       => $amount,
                    'line_narration'      => $data['narration'],
                ]);
            }

            AuditLog::log('CREATE_CASH_VOUCHER', "ক্যাশ ভাউচার {$voucherNumber} তৈরি করা হয়েছে। স্ট্যাটাস: {$status}");
        });

        return redirect()->route('vouchers.index')
            ->with('success', 'ক্যাশ ভাউচার সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    public function createJournal(): View
    {
        $user = Auth::user();
        $currentFy = FiscalYear::current();

        $costCenters = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? CostCenter::active()->get()
            : $user->costCenters;

        $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();
        $periods = $currentFy ? $currentFy->periods()->where('status', 'open')->get() : collect();

        return view('vouchers.create_journal', compact('costCenters', 'accounts', 'currentFy', 'periods'));
    }

    public function storeJournal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date'           => ['required', 'date'],
            'cost_center_id' => ['required', 'exists:cost_centers,id'],
            'narration'      => ['required', 'string'],
            'submit_action'  => ['required', 'in:save_draft,submit_for_approval'],
            'entries'        => ['required', 'array', 'min:2'],
            'entries.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'entries.*.debit_amount'        => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit_amount'       => ['nullable', 'numeric', 'min:0'],
            'entries.*.line_narration'      => ['nullable', 'string', 'max:255'],
        ]);

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $cleanEntries = [];

        foreach ($data['entries'] as $entry) {
            $debit = (float) ($entry['debit_amount'] ?? 0);
            $credit = (float) ($entry['credit_amount'] ?? 0);

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(['entries' => 'একটি সারিতে একই সাথে ডেবিট এবং ক্রেডিট উভয় অ্যামাউন্ট থাকতে পারে না।']);
            }

            if ($debit == 0 && $credit == 0) {
                continue; // skip empty rows
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $cleanEntries[] = [
                'chart_of_account_id' => $entry['chart_of_account_id'],
                'cost_center_id'      => $data['cost_center_id'],
                'debit_amount'        => $debit,
                'credit_amount'       => $credit,
                'line_narration'      => $entry['line_narration'] ?? null,
            ];
        }

        if (count($cleanEntries) < 2) {
            throw ValidationException::withMessages(['entries' => 'জার্নাল ভাউচারে কমপক্ষে দুইটি সচল এন্ট্রি (ডেবিট ও ক্রেডিট) থাকতে হবে।']);
        }

        // Check Debit == Credit (with 0.01 precision)
        if (abs($totalDebit - $totalCredit) > 0.01 || $totalDebit <= 0) {
            throw ValidationException::withMessages([
                'entries' => "ডেবিট (৳ " . number_format($totalDebit, 2) . ") এবং ক্রেডিট (৳ " . number_format($totalCredit, 2) . ") এর যোগফল অবশ্যই সমান হতে হবে।",
            ]);
        }

        $currentFy = FiscalYear::current();
        if (! $currentFy) {
            throw ValidationException::withMessages(['date' => 'কোনো সক্রিয় অর্থবছর পাওয়া যায়নি।']);
        }

        DB::transaction(function () use ($data, $currentFy, $totalDebit, $totalCredit, $cleanEntries) {
            $prefix = 'JV-' . date('Ym', strtotime($data['date'])) . '-';
            $lastCount = Voucher::where('voucher_number', 'like', $prefix . '%')->count() + 1;
            $voucherNumber = $prefix . str_pad((string)$lastCount, 4, '0', STR_PAD_LEFT);

            $status = $data['submit_action'] === 'submit_for_approval' ? 'submitted' : 'draft';

            $voucher = Voucher::create([
                'voucher_number'       => $voucherNumber,
                'voucher_type'         => 'journal_voucher',
                'date'                 => $data['date'],
                'fiscal_year_id'       => $currentFy->id,
                'cost_center_id'       => $data['cost_center_id'],
                'total_debit'          => $totalDebit,
                'total_credit'         => $totalCredit,
                'status'               => $status,
                'narration'            => $data['narration'],
                'prepared_by_id'       => Auth::id(),
            ]);

            foreach ($cleanEntries as $e) {
                $voucher->entries()->create($e);
            }

            AuditLog::log('CREATE_JOURNAL_VOUCHER', "জার্নাল ভাউচার {$voucherNumber} তৈরি করা হয়েছে। স্ট্যাটাস: {$status}");
        });

        return redirect()->route('vouchers.index')
            ->with('success', 'জার্নাল ভাউচার সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    public function show(Voucher $voucher): View
    {
        $voucher->load(['entries.chartOfAccount', 'costCenter', 'bankAccount', 'preparedBy', 'approvedBy', 'fiscalYear']);
        return view('vouchers.show', compact('voucher'));
    }

    public function print(Voucher $voucher): View
    {
        $voucher->load(['entries.chartOfAccount', 'costCenter', 'bankAccount', 'preparedBy', 'approvedBy', 'fiscalYear']);
        return view('vouchers.print', compact('voucher'));
    }

    public function approve(Voucher $voucher): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSystemAdmin() && ! $user->isDirectorAccounts() && ! $user->hasRole('accounts_officer')) {
            abort(403, 'ভাউচার অনুমোদনের ক্ষমতা আপনার নেই।');
        }

        if ($voucher->isPosted()) {
            return back()->with('info', 'এই ভাউচারটি ইতোমধ্যে অনুমোদিত ও পোস্টকৃত।');
        }

        $voucher->update([
            'status'         => 'posted',
            'approved_by_id' => $user->id,
            'approved_at'    => now(),
        ]);

        AuditLog::log('APPROVE_VOUCHER', "ভাউচার {$voucher->voucher_number} অনুমোদন ও লেজারে পোস্টিং সম্পন্ন হয়েছে।");

        return back()->with('success', "ভাউচার {$voucher->voucher_number} সফলভাবে অনুমোদিত ও পোস্ট করা হয়েছে।");
    }

    public function reject(Request $request, Voucher $voucher): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSystemAdmin() && ! $user->isDirectorAccounts() && ! $user->hasRole('accounts_officer')) {
            abort(403, 'ভাউচার প্রত্যাখ্যানের ক্ষমতা আপনার নেই।');
        }

        $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        $voucher->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::log('REJECT_VOUCHER', "ভাউচার {$voucher->voucher_number} প্রত্যাখ্যান করা হয়েছে। কারণ: {$request->rejection_reason}");

        return back()->with('warning', "ভাউচার {$voucher->voucher_number} প্রত্যাখ্যান করা হয়েছে।");
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        if ($voucher->isPosted()) {
            return back()->with('error', 'অনুমোদিত ও পোস্টকৃত ভাউচার মুছে ফেলা সম্ভব নয়।');
        }

        $num = $voucher->voucher_number;
        $voucher->entries()->delete();
        $voucher->delete();

        AuditLog::log('DELETE_VOUCHER', "ভাউচার {$num} মুছে ফেলা হয়েছে।");

        return redirect()->route('vouchers.index')->with('success', "ভাউচার {$num} সফলভাবে মুছে ফেলা হয়েছে।");
    }
}
