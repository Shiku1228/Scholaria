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
                pointer-events: auto;
            }
            #sidebar.is-open {
                transform: translateX(0);
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
        }

        @media (min-width: 992px) {
            #sidebar {
                position: static;
                height: auto;
                transform: none;
                transition: none;
                z-index: auto;
            }
            #sidebarOverlay {
                display: none;
            }

            #sidebarToggle,
            #sidebarToggleInner {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="min-h-screen flex">
    <div id="sidebarOverlay"
         class="sidebar-overlay fixed inset-0 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 min-[992px]:hidden z-[1500]">
    </div>
    
    <!-- Sidebar -->
    <aside id="sidebar"
           class="sidebar w-64 flex flex-col bg-white border-r border-gray-200
                  fixed inset-y-0 left-0 z-[2000]
                  -translate-x-full transition-transform duration-300 ease-in-out
                  min-[992px]:translate-x-0 min-[992px]:static min-[992px]:inset-auto min-[992px]:z-auto">
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
            <div class="flex items-center gap-2">
                <div class="h-9 w-9 rounded-xl bg-[#0b2d6b]"></div>
                <div class="font-semibold">SCHOLORIA</div>
            </div>
            <button id="sidebarToggleInner" type="button" class="min-[992px]:hidden p-2 rounded-md hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                    <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
            @php
                $nav = [
                    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'href' => route('admin.dashboard')],
                    ['label' => 'Enrollment', 'href' => '#'],
                    ['label' => 'Course', 'href' => '#'],
                    ['label' => 'Manage Teacher', 'href' => '#'],
                    ['label' => 'Manage Student', 'href' => '#'],
                    ['label' => 'Message', 'href' => '#'],
                    ['label' => 'Notification', 'href' => '#'],
                    ['label' => 'Support Forum', 'href' => '#'],
                    ['label' => 'Settings', 'href' => '#'],
                ];
            @endphp

            <div class="space-y-1">
                @foreach ($nav as $item)
                    @php
                        $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
                    @endphp
                    <a href="{{ $item['href'] }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $isActive ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                        <span class="h-8 w-8 rounded-lg {{ $isActive ? 'bg-[#0b2d6b]' : 'bg-gray-200' }}"></span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <div class="pt-3 mt-3 border-t border-gray-200">
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span class="h-8 w-8 rounded-lg bg-gray-200"></span>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col min-w-0 w-full lg:ml-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" type="button" class="min-[992px]:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[#0b2d6b]">
                    <span class="sr-only">Open sidebar</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                        <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div class="text-sm text-gray-500">Admin</div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" class="relative inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-50">
                    <span class="sr-only">Notifications</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                        <path d="M5.25 9.75a6.75 6.75 0 0 1 13.5 0v5.126c0 .317.12.621.336.852l1.407 1.507a.75.75 0 0 1-.547 1.265H4.054a.75.75 0 0 1-.547-1.265l1.407-1.507a1.25 1.25 0 0 0 .336-.852V9.75Z" />
                        <path d="M9.75 19.5a2.25 2.25 0 0 0 4.5 0h-4.5Z" />
                    </svg>
                    <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-[#0b2d6b]"></span>
                </button>

                <button type="button" class="inline-flex items-center gap-2 h-10 px-3 rounded-lg hover:bg-gray-50">
                    <span class="h-8 w-8 rounded-full bg-gray-200"></span>
                    <span class="hidden sm:block text-sm font-medium">Admin</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 text-gray-600">
                        <path fill-rule="evenodd" d="M12.53 16.28a.75.75 0 0 1-1.06 0l-5.25-5.25a.75.75 0 1 1 1.06-1.06L12 14.69l4.72-4.72a.75.75 0 0 1 1.06 1.06l-5.25 5.25Z" clip-rule="evenodd" />
                    </svg>
                </button>
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
    });
</script>

<script src="{{ asset('js/sidebar.js') }}"></script>
</body>
</html>

