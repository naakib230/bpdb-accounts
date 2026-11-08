<aside class="app-sidebar offcanvas-lg offcanvas-start no-print" tabindex="-1" id="mainSidebar" aria-labelledby="mainSidebarLabel">
    <div class="offcanvas-header d-lg-none p-3 border-bottom">
        <h5 class="offcanvas-title fw-bold text-success" id="mainSidebarLabel">
            <i class="fa-solid fa-bars-staggered me-2"></i>মেনু (Menu)
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#mainSidebar" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-block p-0 overflow-y-auto">
        <ul class="sidebar-menu">
            <!-- 1. Dashboard -->
            <li class="sidebar-heading">প্রধান ডিরেক্টরি</li>
            <li class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="fa-solid fa-gauge-high" style="color: #2563eb;"></i>ড্যাশবোর্ড (Dashboard)
                </a>
            </li>

            <!-- 2. Vouchers -->
            <li class="sidebar-heading">ভাউচার পোস্টিং ও এন্ট্রি</li>
            <li class="sidebar-item {{ request()->routeIs('vouchers.index') ? 'active' : '' }}">
                <a href="{{ route('vouchers.index') }}">
                    <i class="fa-solid fa-file-invoice-dollar" style="color: #d97706;"></i>সকল ভাউচার তালিকা
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('vouchers.create_cash') ? 'active' : '' }}">
                <a href="{{ route('vouchers.create_cash') }}">
                    <i class="fa-solid fa-money-bill-transfer" style="color: #16a34a;"></i>ক্যাশ ভাউচার (Cash Voucher)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('vouchers.create_journal') ? 'active' : '' }}">
                <a href="{{ route('vouchers.create_journal') }}">
                    <i class="fa-solid fa-book-journal-whills" style="color: #0284c7;"></i>জার্নাল ভাউচার (Journal Voucher)
                </a>
            </li>

            <!-- 3. Ledgers & Trial Balance -->
            <li class="sidebar-heading">খতিয়ান ও রেওয়ামিল</li>
            <li class="sidebar-item {{ request()->routeIs('ledger.*') ? 'active' : '' }}">
                <a href="{{ route('ledger.index') }}">
                    <i class="fa-solid fa-book-open" style="color: #7c3aed;"></i>সাধারণ খতিয়ান (Ledger)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('trial_balance.*') ? 'active' : '' }}">
                <a href="{{ route('trial_balance.index') }}">
                    <i class="fa-solid fa-scale-balanced" style="color: #0d9488;"></i>রেওয়ামিল (Trial Balance)
                </a>
            </li>

            <!-- 4. Reports & Financial Statements -->
            <li class="sidebar-heading">আর্থিক বিবরণী ও প্রতিবেদন</li>
            <li class="sidebar-item {{ request()->routeIs('reports.balance_sheet') ? 'active' : '' }}">
                <a href="{{ route('reports.balance_sheet') }}">
                    <i class="fa-solid fa-landmark" style="color: #1d4ed8;"></i>আর্থিক অবস্থার বিবরণী (Balance Sheet)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('reports.income_statement') ? 'active' : '' }}">
                <a href="{{ route('reports.income_statement') }}">
                    <i class="fa-solid fa-chart-line" style="color: #e11d48;"></i>আয়-ব্যয় বিবরণী (Income Statement)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('reports.bank_book') ? 'active' : '' }}">
                <a href="{{ route('reports.bank_book') }}">
                    <i class="fa-solid fa-building-columns" style="color: #0891b2;"></i>ব্যাংক ও ক্যাশ খাতা (Cash Book)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('reports.cost_center_expenditure') ? 'active' : '' }}">
                <a href="{{ route('reports.cost_center_expenditure') }}">
                    <i class="fa-solid fa-chart-pie" style="color: #ea580c;"></i>কস্ট সেন্টার ভিত্তিক খরচ
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('reports.budget_variance') ? 'active' : '' }}">
                <a href="{{ route('reports.budget_variance') }}">
                    <i class="fa-solid fa-bullseye" style="color: #dc2626;"></i>বাজেট বনাম বাস্তব খরচ রিপোর্ট
                </a>
            </li>

            <!-- 5. Configuration & Management -->
            <li class="sidebar-heading">কনফিগারেশন ও অ্যাকাউন্টিং</li>
            <li class="sidebar-item {{ request()->routeIs('raos.*') ? 'active' : '' }}">
                <a href="{{ route('raos.index') }}">
                    <i class="fa-solid fa-building-shield" style="color: #059669;"></i>আঞ্চলিক হিসাব দপ্তর (RAO)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('cost_centers.*') ? 'active' : '' }}">
                <a href="{{ route('cost_centers.index') }}">
                    <i class="fa-solid fa-sitemap" style="color: #9333ea;"></i>কস্ট সেন্টার (Cost Centers)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('bank_accounts.*') ? 'active' : '' }}">
                <a href="{{ route('bank_accounts.index') }}">
                    <i class="fa-solid fa-money-check-dollar" style="color: #0284c7;"></i>ব্যাংক হিসাব (Bank Accounts)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('chart_of_accounts.*') ? 'active' : '' }}">
                <a href="{{ route('chart_of_accounts.index') }}">
                    <i class="fa-solid fa-folder-tree" style="color: #b45309;"></i>চার্ট অব অ্যাকাউন্টস (COA)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('accounting_periods.*') ? 'active' : '' }}">
                <a href="{{ route('accounting_periods.index') }}">
                    <i class="fa-solid fa-calendar-days" style="color: #4f46e5;"></i>অর্থবছর ও পিরিয়ড সেটআপ
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('budget.*') ? 'active' : '' }}">
                <a href="{{ route('budget.index') }}">
                    <i class="fa-solid fa-coins" style="color: #ca8a04;"></i>কস্ট সেন্টার বাজেট বরাদ্দ
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('reconciliation.*') ? 'active' : '' }}">
                <a href="{{ route('reconciliation.index') }}">
                    <i class="fa-solid fa-handshake-simple" style="color: #0d9488;"></i>ব্যাংক সমন্বয় (Bank Reconciliation)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('year_closing.*') ? 'active' : '' }}">
                <a href="{{ route('year_closing.index') }}">
                    <i class="fa-solid fa-lock" style="color: #be123c;"></i>বছর সমাপনী প্রক্রিয়া (Year Closing)
                </a>
            </li>

            <!-- 6. Administration -->
            <li class="sidebar-heading">প্রশাসন ও নিরাপত্তা</li>
            <li class="sidebar-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <a href="{{ route('users.index') }}">
                    <i class="fa-solid fa-users-gear" style="color: #6366f1;"></i>ব্যবহারকারী ব্যবস্থাপনা (Users)
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                <a href="{{ route('profile') }}">
                    <i class="fa-solid fa-user-circle" style="color: #10b981;"></i>আমার প্রোফাইল
                </a>
            </li>
        </ul>
    </div>
</aside>
