<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoucherRequest;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\FiscalYear;
use App\Models\Voucher;
use App\Models\VoucherEntry;
use App\Services\VoucherPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherPostingService $voucherPostingService
    ) {}

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

    public function storeCash(StoreVoucherRequest $request): RedirectResponse
    {
        $this->voucherPostingService->createCashVoucher($request->validated(), Auth::id());

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

    public function storeJournal(StoreVoucherRequest $request): RedirectResponse
    {
        $this->voucherPostingService->createJournalVoucher($request->validated(), Auth::id());

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
