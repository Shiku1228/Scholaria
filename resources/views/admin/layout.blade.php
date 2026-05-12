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
                $isAdminUsers = request()->routeIs('admin.users.*');
                $isAdminRecords = request()->routeIs('admin.records.*');
            @endphp

            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="h-8 w-8 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-[#0b2d6b]' : 'bg-gray-200' }} flex items-center justify-center">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </span>
                    <span>Dashboard</span>
                </a>

                <!-- User Management -->
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $isAdminUsers ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="h-8 w-8 rounded-lg {{ $isAdminUsers ? 'bg-[#0b2d6b]' : 'bg-gray-200' }} flex items-center justify-center">
                        <svg class="w-4 h-4 {{ $isAdminUsers ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </span>
                    <span>User Management</span>
                </a>

                <!-- Role Management -->
                <a href="{{ route('admin.roles.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.roles.*') ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="h-8 w-8 rounded-lg {{ request()->routeIs('admin.roles.*') ? 'bg-[#0b2d6b]' : 'bg-gray-200' }} flex items-center justify-center">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.roles.*') ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </span>
                    <span>Role Management</span>
                </a>

                <!-- Records -->
                <a href="{{ route('admin.records.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $isAdminRecords ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="h-8 w-8 rounded-lg {{ $isAdminRecords ? 'bg-[#0b2d6b]' : 'bg-gray-200' }} flex items-center justify-center">
                        <svg class="w-4 h-4 {{ $isAdminRecords ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                    <span>Records</span>
                </a>

                <!-- Enrollment -->
                <a href="{{ route('admin.enrollments.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.enrollments.*') ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="h-8 w-8 rounded-lg {{ request()->routeIs('admin.enrollments.*') ? 'bg-[#0b2d6b]' : 'bg-gray-200' }} flex items-center justify-center">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.enrollments.*') ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                    <span>Enrollment</span>
                </a>

                <!-- Course -->
                <a href="{{ route('admin.courses.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('admin.courses.*') ? 'bg-[#eaf0fb] text-[#0b2d6b] ring-1 ring-inset ring-[#c9d7f2]' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="h-8 w-8 rounded-lg {{ request()->routeIs('admin.courses.*') ? 'bg-[#0b2d6b]' : 'bg-gray-200' }} flex items-center justify-center">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.courses.*') ? 'text-white' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </span>
                    <span>Course</span>
                </a>

                <!-- Message -->
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span class="h-8 w-8 rounded-lg bg-gray-200 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </span>
                    <span>Message</span>
                </a>

                <!-- Notification -->
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span class="h-8 w-8 rounded-lg bg-gray-200 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </span>
                    <span>Notification</span>
                </a>

                <!-- Support Forum -->
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span class="h-8 w-8 rounded-lg bg-gray-200 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                        </svg>
                    </span>
                    <span>Support Forum</span>
                </a>

                <!-- Settings -->
                <a href="#"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span class="h-8 w-8 rounded-lg bg-gray-200 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    <span>Settings</span>
                </a>

                <div class="pt-3 mt-3 border-t border-gray-200">
                    <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <span class="h-8 w-8 rounded-lg bg-gray-200 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </span>
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

