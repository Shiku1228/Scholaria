<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin Dashboard' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 16rem;
                transform: translateX(-110%);
                transition: transform 0.3s ease;
                z-index: 2000;
            }
            #sidebar.is-open {
                transform: translateX(0);
            }

            #sidebar.is-open .slms-nav-label {
                display: inline;
            }

            #sidebar.is-open .slms-nav-item {
                width: 100%;
                justify-content: flex-start;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                gap: 0.75rem;
            }

            #sidebarOverlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.4);
                z-index: 1500;
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
                transition: opacity 0.2s ease;
            }
            #sidebarOverlay.is-open {
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            body.sidebar-open {
                overflow: hidden;
            }

            body.sidebar-open #sidebarToggle {
                opacity: 0;
                pointer-events: none;
            }

            #sidebarToggle {
                position: relative;
                z-index: 1200;
                cursor: pointer;
                transition: opacity 0.2s ease;
            }
            
            .slms-main {
                margin-left: 0 !important;
            }
        }

        #sidebar .slms-nav-label {
            display: none;
        }

        @media (max-width: 991.98px) {
            #sidebar.is-open .slms-brand-text {
                display: block !important;
            }

            #sidebar.is-open .slms-brand-row {
                justify-content: flex-start;
            }

            #sidebar.is-open .slms-nav-label {
                display: inline;
            }

            #sidebar.is-open .slms-nav-item {
                width: 100%;
                justify-content: flex-start;
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                gap: 0.75rem;
            }
        }

        .slms-main {
            margin-left: 5rem;
            transition: margin-left 0.3s ease;
        }

        body.slms-sidebar-expanded .slms-main {
            margin-left: 16rem;
        }

        /* Ensure main content shifts on desktop only */
        @media (min-width: 992px) {
            .slms-main {
                margin-left: 5rem;
                transition: margin-left 0.3s ease;
            }

            body.slms-sidebar-expanded .slms-main {
                margin-left: 16rem;
            }
        }

        /* Expanded sidebar styles */
        #sidebar {
            transition: width 0.3s ease;
        }

        #sidebar.is-expanded {
            width: 16rem;
        }

        #sidebar.is-expanded .slms-brand-text {
            display: block;
        }

        #sidebar:not(.is-expanded) .slms-brand-text {
            display: none;
        }

        #sidebar.is-expanded .slms-brand-row {
            justify-content: flex-start;
        }

        #sidebar .slms-sidebar-nav {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-right: 3rem;
        }

        #sidebar.is-expanded .slms-sidebar-nav {
            align-items: stretch;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #sidebar.is-expanded .slms-nav-label {
            display: inline;
        }

        #sidebar.is-expanded .slms-nav-item {
            width: 100%;
            justify-content: flex-start;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            gap: 0.75rem;
        }

        @media (max-width: 991.98px) {
            #sidebar.is-expanded {
                width: 16rem;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="min-h-screen flex">
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 min-[992px]:hidden z-[1500]"></div>

    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-20 bg-white flex flex-col justify-between items-center py-6 shadow-md border-r border-gray-100 z-30">
        <!-- Top Section -->
        <div class="flex flex-col items-center space-y-6">
            <div class="slms-brand-row flex items-center justify-center gap-3 px-4 w-full">
                <div class="w-10 h-10 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center">
                    <img src="{{ asset('SCHOLORIA LOGO.png') }}" alt="SCHOLARIA" class="w-8 h-8 object-contain" />
                </div>
                <div class="slms-brand-text text-sm font-semibold text-gray-800">SCHOLARIA</div>
            </div>

            <!-- Navigation Icons -->
            <nav class="flex flex-col items-center space-y-6 mt-8">
                @include('partials.sidebars.admin')
            </nav>
        </div>

        <!-- Bottom Section -->
        <div class="flex flex-col items-center space-y-4">
            <hr class="w-10 border-gray-300">
            
            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="contents">
                @csrf
                <button type="submit" class="w-12 h-12 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors">
                    <i data-lucide="log-out" style="width:20px;height:20px;"></i>
                    <span class="sr-only">Logout</span>
                </button>
            </form>

            <!-- Toggle Button -->
            <button id="sidebarExpandToggle" type="button" aria-expanded="false" class="hidden min-[992px]:flex w-12 h-12 items-center justify-center rounded-full bg-white shadow-md border border-gray-200">
                <i data-lucide="menu" style="width:18px;height:18px;"></i>
                <span class="sr-only">Toggle sidebar</span>
            </button>
        </div>
    </aside>

    <div class="slms-main flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" type="button" class="min-[992px]:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <span class="sr-only">Open sidebar</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                        <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </button>

            </div>

            <div class="flex items-center gap-3">
                <button type="button" class="h-10 w-10 rounded-full bg-white text-gray-600 border border-gray-200 flex items-center justify-center hover:bg-gray-50" aria-label="Notifications">
                    <i data-lucide="bell" style="width:18px;height:18px;"></i>
                </button>
                <div class="h-10 w-10 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center border border-violet-200">
                    <i data-lucide="user" style="width:18px;height:18px;"></i>
                </div>
                <div class="hidden sm:block leading-tight">
                    <div class="text-xs text-gray-500">Profile</div>
                    <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>

<script>
    window.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>

<script src="{{ asset('js/sidebar.js') }}"></script>
</body>
</html>
