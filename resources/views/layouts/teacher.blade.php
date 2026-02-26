<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Teacher Dashboard' }}</title>
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
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 min-[992px]:hidden z-[1500]"></div>
    
    <aside id="sidebar" class="w-64 flex flex-col bg-white border-r border-gray-200">
        <div class="h-16 flex items-center justify-between px-6 border-b border-gray-200">
            <div class="flex items-center gap-2">
                <img src="{{ asset('SCHOLORIA LOGO.png') }}" alt="SCHOLORIA" class="h-9 w-9 object-contain" />
                <div class="font-semibold">SCHOLORIA</div>
            </div>
            <button id="sidebarToggleInner" type="button" class="min-[992px]:hidden p-2 rounded-md hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                    <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4 overflow-y-auto">
            @include('partials.sidebars.teacher')
        </nav>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" type="button" class="min-[992px]:hidden inline-flex items-center justify-center h-10 w-10 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <span class="sr-only">Open sidebar</span>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-gray-700">
                        <path fill-rule="evenodd" d="M3 6.75A.75.75 0 0 1 3.75 6h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 6.75Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 12Zm0 5.25c0-.414.336-.75.75-.75h16.5a.75.75 0 0 1 0 1.5H3.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div class="text-sm text-gray-500">Teacher</div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 h-10 px-3 rounded-lg hover:bg-gray-50 text-sm font-medium text-gray-700">
                    Logout
                </button>
            </form>
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
