<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page min-h-screen flex items-center justify-center px-4 py-10 text-gray-900">
<div class="w-full max-w-5xl">
    <div class="bg-white rounded-[28px] shadow-xl border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <div class="auth-welcome relative flex flex-col justify-between p-10 text-white order-2 lg:order-1">
                <div class="auth-welcome-curve" aria-hidden="true"></div>
                <div class="relative z-10">
                    <div class="text-4xl font-extrabold tracking-wide">WELCOME</div>
                    <div class="mt-3 text-sm text-white/85 max-w-sm">
                        Sign in to continue managing your SCHOLORIA dashboard.
                    </div>
                </div>

                <div class="relative z-10 text-white/75 text-sm">
                    Secure access for administrators and users.
                </div>

                <div class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 rounded-full bg-white/10 auth-circle z-10"></div>
                <div class="pointer-events-none absolute left-24 bottom-10 h-28 w-28 rounded-full bg-white/15 auth-circle z-10"></div>
                <div class="pointer-events-none absolute left-52 -bottom-6 h-44 w-44 rounded-full bg-white/10 auth-circle z-10"></div>
            </div>

            <div class="p-8 sm:p-10 order-1 lg:order-2">
                <div class="text-2xl font-semibold">Sign in</div>
                <div class="mt-1 text-sm text-gray-500">Use your account credentials to continue.</div>

                <form class="mt-8 space-y-5" method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf

                    <div>
                        <label for="login" class="block text-sm font-medium text-gray-700">Email / Username</label>
                        <div class="mt-2">
                            <input
                                id="login"
                                name="login"
                                type="text"
                                autocomplete="username"
                                value="{{ old('login') }}"
                                required
                                class="block w-full h-11 rounded-xl border border-gray-200 bg-white px-4 text-sm text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            />
                        </div>
                        @error('login')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-2 relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                                class="block w-full h-11 rounded-xl border border-gray-200 bg-white px-4 pr-20 text-sm text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            />
                            <button
                                type="button"
                                id="togglePassword"
                                class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-gray-600 hover:text-gray-900"
                                aria-label="Toggle password visibility"
                            >
                                SHOW
                            </button>
                        </div>
                        @error('password')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 select-none">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                                class="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                            />
                            Remember me
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-violet-700 hover:text-violet-800">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <button
                        type="submit"
                        class="w-full h-11 rounded-xl bg-violet-600 text-white text-sm font-semibold shadow-sm hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                    >
                        Sign in
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const btn = document.getElementById('togglePassword');
        const input = document.getElementById('password');
        if (!btn || !input) return;

        btn.addEventListener('click', function () {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.textContent = isPassword ? 'HIDE' : 'SHOW';
        });
    })();
</script>
</body>
</html>
