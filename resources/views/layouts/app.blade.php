<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/bpdb-logo.png') }}">
    <title>@yield('title', 'BPDB Accounts Portal | বাংলাদেশ বিদ্যুৎ উন্নয়ন বোর্ড')</title>

    <!-- Bootstrap 5 & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- BPDB Portal Stylesheet -->
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    @stack('styles')
</head>
<body>
    @auth
        @include('partials.header')
    @endauth

    <div class="app-wrapper">
        @auth
            @include('partials.sidebar')
        @endauth

        <main class="app-content">
            @auth
                <button class="btn btn-outline-success d-lg-none mb-3 no-print" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainSidebar" aria-controls="mainSidebar">
                    <i class="fa-solid fa-bars me-2"></i>মেনু (Menu)
                </button>
            @endauth

            {{-- Flash Alerts --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show auto-dismiss-alert shadow-sm border-0 position-relative mb-3" role="alert" data-auto-dismiss="10000">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-check fs-5 me-2 text-success flex-shrink-0"></i>
                        <div class="flex-grow-1 fw-medium pe-2">{{ session('success') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="alert-progress-track">
                        <div class="alert-progress-bar bg-success"></div>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show auto-dismiss-alert shadow-sm border-0 position-relative mb-3" role="alert" data-auto-dismiss="10000">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation fs-5 me-2 text-danger flex-shrink-0"></i>
                        <div class="flex-grow-1 fw-medium pe-2">{{ session('error') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="alert-progress-track">
                        <div class="alert-progress-bar bg-danger"></div>
                    </div>
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show auto-dismiss-alert shadow-sm border-0 position-relative mb-3" role="alert" data-auto-dismiss="10000">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-triangle-exclamation fs-5 me-2 text-warning flex-shrink-0"></i>
                        <div class="flex-grow-1 fw-medium pe-2">{{ session('warning') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="alert-progress-track">
                        <div class="alert-progress-bar bg-warning"></div>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show auto-dismiss-alert shadow-sm border-0 position-relative mb-3" role="alert" data-auto-dismiss="10000">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-info fs-5 me-2 text-info flex-shrink-0"></i>
                        <div class="flex-grow-1 fw-medium pe-2">{{ session('info') }}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="alert-progress-track">
                        <div class="alert-progress-bar bg-info"></div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show auto-dismiss-alert shadow-sm border-0 position-relative mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="fa-solid fa-circle-exclamation fs-5 me-2 mt-1 text-danger flex-shrink-0"></i>
                        <div class="flex-grow-1 fw-medium pe-2">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Auto-dismiss script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.auto-dismiss-alert[data-auto-dismiss]').forEach(function (alert) {
                var duration = parseInt(alert.getAttribute('data-auto-dismiss')) || 10000;
                var bar = alert.querySelector('.alert-progress-bar');
                var timer = setTimeout(function () {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    if (bsAlert) bsAlert.close();
                }, duration);

                alert.addEventListener('mouseenter', function () {
                    clearTimeout(timer);
                    if (bar) bar.style.animationPlayState = 'paused';
                });

                alert.addEventListener('mouseleave', function () {
                    if (bar) bar.style.animationPlayState = 'running';
                    timer = setTimeout(function () {
                        var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                        if (bsAlert) bsAlert.close();
                    }, 4000);
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
