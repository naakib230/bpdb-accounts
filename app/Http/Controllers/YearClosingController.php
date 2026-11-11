<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Services\YearClosingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class YearClosingController extends Controller
{
    public function __construct(
        protected YearClosingService $yearClosingService
    ) {}

    public function index(): View
    {
        $currentFy = FiscalYear::current();
        $summary = $this->yearClosingService->calculateSummary($currentFy);

        $unpostedCount = $summary['unpostedCount'];
        $revenueTotal  = $summary['revenueTotal'];
        $expenseTotal  = $summary['expenseTotal'];
        $netSurplus    = $summary['netSurplus'];

        $allFiscalYears = FiscalYear::orderByDesc('start_date')->get();

        return view('year_closing.index', compact(
            'currentFy',
            'unpostedCount',
            'revenueTotal',
            'expenseTotal',
            'netSurplus',
            'allFiscalYears'
        ));
    }

    public function processClose(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->isSystemAdmin() && ! $user->isDirectorAccounts()) {
            abort(403, 'অর্থবছর সমাপনী করার ক্ষমতা কেবলমাত্র হিসাব পরিচালক এবং অ্যাডমিনের রয়েছে।');
        }

        $currentFy = FiscalYear::current();
        if (! $currentFy) {
            return back()->with('error', 'কোনো উন্মুক্ত সক্রিয় অর্থবছর পাওয়া যায়নি।');
        }

        try {
            $result = $this->yearClosingService->closeFiscalYear($currentFy, $user->id);
            $nextFy = $result['nextFy'];

            return redirect()->route('year_closing.index')
                ->with('success', "অর্থবছর {$currentFy->name} সফলভাবে সমাপ্ত হয়েছে এবং পরবর্তী অর্থবছর {$nextFy->name} সক্রিয় করা হয়েছে।");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
