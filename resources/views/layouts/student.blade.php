<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Student Dashboard' }}</title>
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

        #sidebar .slms-brand-text {
            display: none;
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

        #sidebar.is-expanded .slms-nav-item span {
            display: inline;
        }

        #sidebar:not(.is-expanded) .slms-nav-item span {
            display: none;
        }

        @media (max-width: 991.98px) {
            #sidebar.is-expanded {
                width: 16rem;
            }
        }

        #sidebarExpandToggle svg {
            transition: transform 0.2s ease;
        }

        #sidebar.is-expanded #sidebarExpandToggle svg {
            transform: rotate(180deg);
        }

        /* Enhanced Sidebar Scrolling with SCHOLARIA Brand Theme */
        .custom-scrollbar {
            scrollbar-width: thin !important;
            scrollbar-color: #1e3a5f #f1f5f9 !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 8px !important;
            display: block !important;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9 !important;
            border-radius: 8px !important;
            border: 1px solid #e2e8f0 !important;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #1e3a5f 0%, #0f2440 100%) !important;
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            transition: all 0.3s ease !important;
            min-height: 24px !important;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #152542 0%, #0a1a2e 100%) !important;
            border-color: #94a3b8 !important;
            transform: scaleY(1.05) !important;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:active {
            background: linear-gradient(180deg, #0f1d32 0%, #061020 100%) !important;
            transform: scaleY(0.95) !important;
        }

        #sidebar .slms-nav-item {
            margin: 0.125rem 0;
        }

        /* Smooth scrolling for sidebar */
        #sidebar .slms-sidebar-nav {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="min-h-screen flex">
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 min-[992px]:hidden z-[1500]"></div>

    <aside id="sidebar" class="fixed left-0 top-0 h-screen w-20 bg-white flex flex-col shadow-md border-r border-gray-100 z-30">
        <!-- Logo Section -->
        <div class="flex flex-col items-center py-4 border-b border-gray-100">
            <div class="slms-brand-row flex items-center justify-center gap-3 px-3 w-full">
                <div class="w-10 h-10 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center flex-shrink-0">
                    <img src="{{ asset('SCHOLORIA LOGO.png') }}" alt="SCHOLARIA" class="w-8 h-8 object-contain" />
                </div>
                <div class="slms-brand-text text-sm font-semibold text-gray-800">SCHOLARIA</div>
            </div>
        </div>

        <!-- Navigation Section - Scrollable -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar">
            <nav class="slms-sidebar-nav flex flex-col items-center py-3 space-y-2">
                @include('partials.sidebars.student')
            </nav>
        </div>

        <!-- Bottom Section -->
        <div class="flex flex-col items-center py-3 space-y-2 border-t border-gray-100">
            @if(auth()->user()->google2fa_enabled)
                <button onclick="open2FAModal()" class="w-12 h-12 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors" title="2FA Settings">
                    <i data-lucide="shield-check" style="width:20px;height:20px;color:#10b981;"></i>
                    <span class="sr-only">2FA Settings</span>
                </button>
            @else
                <button onclick="open2FAModal()" class="w-12 h-12 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors" title="Setup 2FA">
                    <i data-lucide="shield" style="width:20px;height:20px;"></i>
                    <span class="sr-only">Setup 2FA</span>
                </button>
            @endif

            <a href="{{ route('web.logout') }}" class="w-12 h-12 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors">
                <i data-lucide="log-out" style="width:20px;height:20px;"></i>
                <span class="sr-only">Logout</span>
            </a>

            <button id="sidebarExpandToggle" type="button" aria-expanded="false" class="hidden min-[992px]:flex w-12 h-12 items-center justify-center rounded-full bg-white shadow-md border border-gray-200">
                <i data-lucide="menu" style="width:18px;height:18px;"></i>
                <span class="sr-only">Toggle sidebar</span>
            </button>
        </div>
    </aside>

    <div class="slms-main flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" type="button" class="min-[992px]:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#0b2d6b]">
                    <span class="sr-only">Open sidebar</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                        <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </button>

            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button id="studentNotificationToggle" type="button" class="h-10 w-10 rounded-full bg-white text-gray-600 border border-gray-200 flex items-center justify-center hover:bg-gray-50 relative" aria-label="Notifications">
                            <i data-lucide="bell" style="width:18px;height:18px;"></i>
                            @if (($headerUnreadNotificationCount ?? 0) > 0)
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ min(99, (int) $headerUnreadNotificationCount) }}</span>
                            @endif
                        </button>
                        <div id="studentNotificationPanel" class="hidden absolute right-0 mt-2 w-96 max-w-[92vw] rounded-xl border border-gray-200 bg-white shadow-xl z-50">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-900">Notifications</div>
                                <form method="POST" action="{{ route('student.notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-[#0b2d6b] hover:text-[#0a275c]">Mark all read</button>
                                </form>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                @forelse (($headerNotifications ?? collect()) as $notification)
                                    @php $data = (array) $notification->data; @endphp
                                    <a href="{{ route('student.notifications.open', ['notification' => $notification->id]) }}" class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50">
                                        <div class="text-sm font-semibold text-gray-900">{{ (string) ($data['title'] ?? 'Notification') }}</div>
                                        <div class="text-xs text-gray-600 mt-1">{{ (string) ($data['message'] ?? '') }}</div>
                                        <div class="text-[11px] text-gray-400 mt-1">{{ $notification->created_at?->diffForHumans() ?? '' }}</div>
                                    </a>
                                @empty
                                    <div class="px-4 py-6 text-sm text-gray-500 text-center">No notifications yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-[#eaf0fb] text-[#0b2d6b] flex items-center justify-center border border-[#c9d7f2]">
                        <i data-lucide="user" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="hidden sm:block leading-tight">
                        <div class="text-xs text-gray-500">Profile</div>
                        <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                    </div>
                </div>

            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }

        const toggle = document.getElementById('studentNotificationToggle');
        const panel = document.getElementById('studentNotificationPanel');
        if (toggle && panel) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                panel.classList.toggle('hidden');
            });
            document.addEventListener('click', function (e) {
                if (!panel.contains(e.target) && !toggle.contains(e.target)) {
                    panel.classList.add('hidden');
                }
            });
        }
    });
</script>

<script src="{{ asset('js/sidebar.js') }}"></script>
</body>
</html>


