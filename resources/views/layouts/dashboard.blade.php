<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }}</title>
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

        .slms-main {
            margin-left: 5rem;
            transition: margin-left 0.3s ease;
        }

        body.slms-sidebar-expanded .slms-main {
            margin-left: 16rem;
        }

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

        .slms-hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .slms-hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">
<div class="min-h-screen flex">
    <div id="sidebarOverlay" class="fixed inset-0 bg-black/40 opacity-0 pointer-events-none transition-opacity duration-300 min-[992px]:hidden z-[1500]"></div>

    @include('partials.layouts.sidebar', ['sidebarPartial' => $sidebarPartial ?? ''])

    <!-- 2FA Setup Modal -->
    <div id="twoFactorModal" class="fixed inset-0 bg-black/50 z-[2000] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[28px] shadow-xl border border-gray-100 w-full max-w-md overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center">
                            <i data-lucide="shield" style="width:16px;height:16px;"></i>
                        </div>
                        <div class="text-lg font-semibold">Two-Factor Authentication</div>
                    </div>
                    <button onclick="close2FAModal()" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mt-1 text-xs text-gray-500">Setup 2FA to secure your account.</div>

                <div id="2faSetupContent" class="mt-5 space-y-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-600 mb-3">Scan QR code with Google Authenticator</p>
                        <div class="inline-block bg-white p-3 rounded-xl border-2 border-gray-200 shadow-sm">
                            <img id="2faQrCode" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="QR Code" class="w-32 h-32" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Secret Key</label>
                        <div class="flex items-center gap-2">
                            <p id="2faSecretKey" class="font-mono text-xs bg-gray-50 px-3 py-2 rounded-xl border border-gray-200 flex-1 overflow-x-auto">Loading...</p>
                            <button onclick="copySecret()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-xs font-semibold transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>

                    <form id="2faEnableForm" class="space-y-4">
                        @csrf
                        <div>
                            <label for="code" class="block text-xs font-medium text-gray-700">Verification Code</label>
                            <div class="mt-1">
                                <input type="text" name="code" id="code" required pattern="[0-9]{6}" maxlength="6"
                                    class="block w-full h-10 rounded-xl border border-gray-200 bg-white px-4 text-center text-lg tracking-widest text-gray-900 shadow-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                                    placeholder="000000">
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" onclick="close2FAModal()" class="flex-1 h-10 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 h-10 rounded-xl bg-[#0b2d6b] text-white text-xs font-semibold shadow-sm hover:bg-[#0a3a8a]">
                                Enable 2FA
                            </button>
                        </div>
                    </form>
                </div>

                <div id="2faDisableContent" class="hidden">
                    <div class="space-y-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-sm text-green-800 flex items-center gap-2">
                                <i data-lucide="shield-check" style="width:14px;height:14px;color:#10b981;"></i>
                                <strong>2FA is enabled</strong>
                            </p>
                        </div>

                        <form id="2faDisableForm" class="space-y-3">
                            @csrf
                            <input type="password" name="password" required
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500"
                                placeholder="Enter password to disable">
                            <div class="flex gap-2">
                                <button type="button" onclick="close2FAModal()" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" class="flex-1 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                    Disable
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                @php
                    $isStudent = auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('Student');
                    $isTeacher = auth()->check() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('Teacher');
                    $markAllRoute = $isStudent ? 'student.notifications.read-all' : ($isTeacher ? 'teacher.notifications.read-all' : null);
                    $openRoute = $isStudent ? 'student.notifications.open' : ($isTeacher ? 'teacher.notifications.open' : null);
                @endphp
                <div class="relative">
                    <button id="sharedNotificationToggle" type="button" class="h-10 w-10 rounded-full bg-white text-gray-600 border border-gray-200 flex items-center justify-center hover:bg-gray-50 relative" aria-label="Notifications">
                        <i data-lucide="bell" style="width:18px;height:18px;"></i>
                        @if (($headerUnreadNotificationCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ min(99, (int) $headerUnreadNotificationCount) }}</span>
                        @endif
                    </button>
                    <div id="sharedNotificationPanel" class="hidden absolute right-0 mt-2 w-96 max-w-[92vw] rounded-xl border border-gray-200 bg-white shadow-xl z-50">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-900">Notifications</div>
                            @if ($markAllRoute && \Illuminate\Support\Facades\Route::has($markAllRoute))
                                <form method="POST" action="{{ route($markAllRoute) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-[#0b2d6b] hover:text-[#0a275c]">Mark all read</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse (($headerNotifications ?? collect()) as $notification)
                                @php $data = (array) $notification->data; @endphp
                                @if ($openRoute && \Illuminate\Support\Facades\Route::has($openRoute))
                                    <a href="{{ route($openRoute, ['notification' => $notification->id]) }}" class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50">
                                        <div class="text-sm font-semibold text-gray-900">{{ (string) ($data['title'] ?? 'Notification') }}</div>
                                        <div class="text-xs text-gray-600 mt-1">{{ (string) ($data['message'] ?? '') }}</div>
                                        <div class="text-[11px] text-gray-400 mt-1">{{ $notification->created_at?->diffForHumans() ?? '' }}</div>
                                    </a>
                                @else
                                    <div class="px-4 py-3 border-b border-gray-100">
                                        <div class="text-sm font-semibold text-gray-900">{{ (string) ($data['title'] ?? 'Notification') }}</div>
                                        <div class="text-xs text-gray-600 mt-1">{{ (string) ($data['message'] ?? '') }}</div>
                                    </div>
                                @endif
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

        const toggle = document.getElementById('sharedNotificationToggle');
        const panel = document.getElementById('sharedNotificationPanel');
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

    // 2FA Modal Functions
    function open2FAModal() {
        const modal = document.getElementById('twoFactorModal');
        const setupContent = document.getElementById('2faSetupContent');
        const disableContent = document.getElementById('2faDisableContent');

        modal.classList.remove('hidden');

        // Check if 2FA is enabled
        const isEnabled = @json(auth()->user()->google2fa_enabled);

        if (isEnabled) {
            setupContent.classList.add('hidden');
            disableContent.classList.remove('hidden');
        } else {
            setupContent.classList.remove('hidden');
            disableContent.classList.add('hidden');
            fetchQRCode();
        }
    }

    function close2FAModal() {
        const modal = document.getElementById('twoFactorModal');
        modal.classList.add('hidden');
    }

    function fetchQRCode() {
        fetch('/2fa/qr-code', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('2faQrCode').src = data.qrCode;
                document.getElementById('2faSecretKey').textContent = data.secret;
            } else {
                alert(data.message || 'Failed to generate QR code');
                close2FAModal();
            }
        })
        .catch(error => {
            console.error('Error fetching QR code:', error);
            alert('Failed to generate QR code');
            close2FAModal();
        });
    }

    function copySecret() {
        const secretText = document.getElementById('2faSecretKey').textContent;
        navigator.clipboard.writeText(secretText).then(() => {
            alert('Secret key copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy secret key');
        });
    }

    // Handle 2FA Enable Form
    document.getElementById('2faEnableForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/2fa/enable', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('2FA enabled successfully!');
                close2FAModal();
                location.reload();
            } else {
                alert(data.message || 'Failed to enable 2FA');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to enable 2FA');
        });
    });

    // Handle 2FA Disable Form
    document.getElementById('2faDisableForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/2fa/disable', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('2FA disabled successfully!');
                close2FAModal();
                location.reload();
            } else {
                alert(data.message || 'Failed to disable 2FA');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to disable 2FA');
        });
    });
</script>

<script src="{{ asset('js/sidebar.js') }}"></script>
</body>
</html>

