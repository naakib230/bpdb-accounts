<header class="app-header">
    <a href="{{ route('dashboard') }}" class="header-brand" aria-label="বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড - হোম">
        <img src="{{ asset('images/bpdb-logo.png') }}" alt="বিউবো লোগো" class="header-logo">
        <span class="header-brand-text">
            <span class="header-brand-title">বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড (বিউবো)</span>
            <small class="header-brand-subtitle">অ্যাকাউন্টস ও ফিন্যান্সিয়াল পোর্টাল</small>
        </span>
    </a>

    @auth
        @php
            $currentUser = auth()->user();
            $rolesList = $currentUser->roles;
            $hasMultipleRoles = $rolesList->count() > 1;
            $activeRole = $currentUser->activeRole();
        @endphp

        <div class="header-actions">
            <!-- User Profile Chip -->
            <a href="{{ route('profile') }}" class="header-profile" title="প্রোফাইল দেখুন">
                <span class="header-avatar">
                    @if ($currentUser->photo_url)
                        <img src="{{ $currentUser->photo_url }}" alt="{{ $currentUser->name_bn ?: $currentUser->name }}" class="w-100 h-100 rounded-circle object-fit-cover">
                    @else
                        <i class="fa-solid fa-user"></i>
                    @endif
                </span>
                <span class="d-none d-md-flex flex-column text-start">
                    <span class="header-person-name">{{ $currentUser->name_bn ?: $currentUser->name }}</span>
                    <span class="header-person-desc">{{ $currentUser->designation ?: 'হিসাব কর্মকর্তা' }}</span>
                </span>
            </a>

            <!-- Role Switcher -->
            <div class="d-flex align-items-center gap-1 bg-white bg-opacity-10 px-2 py-1 rounded-pill">
                <span class="small text-white-50 d-none d-lg-inline ps-1">সক্রিয় ভূমিকা:</span>
                <form method="POST" action="{{ route('switch_role') }}" class="m-0">
                    @csrf
                    <select name="role" class="form-select form-select-sm border-0 bg-transparent text-white fw-bold py-0" 
                            style="cursor: pointer; min-width: 140px;" 
                            onchange="this.form.submit()" 
                            @disabled(!$hasMultipleRoles)>
                        @foreach ($rolesList as $role)
                            <option value="{{ $role->name }}" class="text-dark" @selected($role->name === ($activeRole?->name ?? ''))>
                                {{ $role->display_name_bn ?: $role->display_name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm rounded-pill px-3" title="লগআউট">
                    <i class="fa-solid fa-right-from-bracket me-1"></i>
                    <span class="d-none d-sm-inline">লগআউট</span>
                </button>
            </form>
        </div>
    @endauth
</header>
