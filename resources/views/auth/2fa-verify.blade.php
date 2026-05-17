<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-Factor Authentication</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page min-h-screen flex items-center justify-center px-4 py-10 text-gray-900">
<div class="w-full max-w-5xl">
    <div class="bg-white rounded-[28px] shadow-xl border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <div class="auth-welcome relative flex flex-col justify-between p-10 text-white order-2 lg:order-1 bg-blue-600">
                <div class="auth-welcome-curve" aria-hidden="true"></div>
                <div class="relative z-10">
                    <div class="text-4xl font-extrabold tracking-wide">2FA VERIFICATION</div>
                    <div class="mt-3 text-sm text-white/85 max-w-sm">
                        Enter the verification code from your authenticator app to complete login.
                    </div>
                </div>

                <div class="relative z-10 text-white/75 text-sm">
                    This adds an extra layer of security to your account.
                </div>

                <div class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 rounded-full bg-white/10 auth-circle z-10"></div>
                <div class="pointer-events-none absolute left-24 bottom-10 h-28 w-28 rounded-full bg-white/15 auth-circle z-10"></div>
                <div class="pointer-events-none absolute left-52 -bottom-6 h-44 w-44 rounded-full bg-white/10 auth-circle z-10"></div>
            </div>

            <div class="p-8 sm:p-10 order-1 lg:order-2 bg-white border-l" style="display: block !important; visibility: visible !important;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-200 flex items-center justify-center">
                        <img src="{{ asset('SCHOLORIA LOGO.png') }}" alt="SCHOLARIA" class="w-7 h-7 object-contain" />
                    </div>
                    <div class="text-2xl font-semibold">Verify 2FA</div>
                </div>
                <div class="mt-1 text-sm text-gray-500">Enter the code from your authenticator app.</div>

                @if (session('error'))
                    <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                <form class="mt-8 space-y-5" method="POST" action="{{ route('2fa.verify') }}">
                    @csrf

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Verification Code</label>
                        <div class="mt-2">
                            <input
                                id="code"
                                name="code"
                                type="text"
                                autocomplete="one-time-code"
                                required
                                autofocus
                                class="block w-full rounded-lg border-gray-300 bg-white px-4 py-3 text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="123456"
                            >
                        </div>
                        @error('code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Verify
                        </button>
                    </div>

                    <div class="text-center">
                        <form method="POST" action="{{ route('web.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                                Cancel and logout
                            </button>
                        </form>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
