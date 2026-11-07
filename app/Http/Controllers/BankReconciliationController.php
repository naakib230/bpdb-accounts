<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\VoucherEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function index(): View
    {
        $reconciliations = BankReconciliation::with(['bankAccount', 'preparedBy'])
            ->latest('statement_date')
            ->paginate(15);

        return view('reconciliation.index', compact('reconciliations'));
    }

    public function create(Request $request): View
    {
        $user = Auth::user();
        $bankAccounts = $user->isSystemAdmin() || $user->isDirectorAccounts()
            ? BankAccount::active()->with('chartOfAccount')->get()
            : $user->bankAccounts()->with('chartOfAccount')->get();

        $selectedBankId = $request->input('bank_account_id', $bankAccounts->first()?->id);
        $selectedBank = $bankAccounts->firstWhere('id', $selectedBankId);
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        $cashBookBalance = 0.0;
        if ($selectedBank && $selectedBank->chartOfAccount) {
            $coaId = $selectedBank->chart_of_account_id;
            $baseOpening = (float) $selectedBank->opening_balance;

            $transQuery = VoucherEntry::whereHas('voucher', function ($q) use ($asOfDate) {
                $q->where('status', 'posted')->where('date', '<=', $asOfDate);
            })->where('chart_of_account_id', $coaId);

            $dr = (float) $transQuery->sum('debit_amount');
            $cr = (float) $transQuery->sum('credit_amount');

            $cashBookBalance = $baseOpening + $dr - $cr;
        }

        return view('reconciliation.create', compact('bankAccounts', 'selectedBank', 'asOfDate', 'cashBookBalance'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bank_account_id'        => ['required', 'exists:bank_accounts,id'],
            'statement_date'         => ['required', 'date'],
            'bank_statement_balance' => ['required', 'numeric'],
            'cash_book_balance'      => ['required', 'numeric'],
            'notes'                  => ['nullable', 'string'],
            'items'                  => ['nullable', 'array'],
            'items.*.type'           => ['required', 'in:outstanding_cheque,deposit_in_transit,bank_charge,interest_credited,other_adjustment'],
            'items.*.item_date'      => ['required', 'date'],
            'items.*.reference_no'   => ['nullable', 'string', 'max:100'],
            'items.*.description'    => ['required', 'string', 'max:255'],
            'items.*.amount'         => ['required', 'numeric', 'min:0.01'],
            'items.*.effect'         => ['required', 'in:add,less'],
        ]);

        DB::transaction(function () use ($data) {
            $bankBal = (float) $data['bank_statement_balance'];
            $adjustedBank = $bankBal;

            $itemsData = $data['items'] ?? [];
            foreach ($itemsData as $item) {
                $amt = (float) $item['amount'];
                if ($item['effect'] === 'add') {
                    $adjustedBank += $amt;
                } else {
                    $adjustedBank -= $amt;
                }
            }

            $diff = round($adjustedBank - (float)$data['cash_book_balance'], 2);
            $status = abs($diff) < 0.01 ? 'reconciled' : 'draft';

            $rec = BankReconciliation::create([
                'bank_account_id'        => $data['bank_account_id'],
                'statement_date'         => $data['statement_date'],
                'bank_statement_balance' => $data['bank_statement_balance'],
                'cash_book_balance'      => $data['cash_book_balance'],
                'reconciled_balance'     => $adjustedBank,
                'difference'             => $diff,
                'status'                 => $status,
                'notes'                  => $data['notes'] ?? null,
                'prepared_by_id'         => Auth::id(),
                'reconciled_at'          => $status === 'reconciled' ? now() : null,
            ]);

            foreach ($itemsData as $item) {
                $rec->items()->create($item);
            }

            AuditLog::log('CREATE_BANK_RECONCILIATION', "ব্যাংক সমন্বয় বিবরণী তৈরি করা হয়েছে। স্ট্যাটাস: {$status}");
        });

        return redirect()->route('reconciliation.index')
            ->with('success', 'ব্যাংক সমন্বয় বিবরণী সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    public function show(BankReconciliation $reconciliation): View
    {
        $reconciliation->load(['bankAccount', 'items', 'preparedBy']);
        return view('reconciliation.show', compact('reconciliation'));
    }
}
