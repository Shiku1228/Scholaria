<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page min-h-screen flex items-center justify-center px-4 py-10 text-gray-900">
<div class="w-full max-w-5xl">
    <div class="bg-white rounded-[28px] shadow-xl border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <div class="auth-welcome relative flex flex-col justify-between p-10 text-white order-2 lg:order-1">
                <div class="auth-welcome-curve" aria-hidden="true"></div>
                <div class="relative z-10">
                    <div class="text-4xl font-extrabold tracking-wide">NEW PASSWORD</div>
                    <div class="mt-3 text-sm text-white/85 max-w-sm">
                        Set a new secure password for your SCHOLORIA account.
                    </div>
                </div>

                <div class="relative z-10 text-white/75 text-sm">
                    Ensure your password is strong and unique.
                </div>

                <div class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 rounded-full bg-white/10 auth-circle z-10"></div>
                <div class="pointer-events-none absolute left-24 bottom-10 h-28 w-28 rounded-full bg-white/15 auth-circle z-10"></div>
                <div class="pointer-events-none absolute left-52 -bottom-6 h-44 w-44 rounded-full bg-white/10 auth-circle z-10"></div>
            </div>

            <div class="p-8 sm:p-10 order-1 lg:order-2">
                <div class="text-2xl font-semibold">Reset Password</div>
                <div class="mt-1 text-sm text-gray-500">Enter your new password below.</div>

                <form class="mt-8 space-y-5" method="POST" action="{{ route('password.update') }}" novalidate>
                    @csrf

                    <input type="hidden" name="token" value="{{ $request->token }}">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <div class="mt-2">
                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                value="{{ $request->email ?? old('email') }}"
                                required
                                readonly
                                class="block w-full h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 text-sm text-gray-900 shadow-sm"
                            />
                        </div>
                        @error('email')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <div class="mt-2 relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
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

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <div class="mt-2 relative">
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                required
                                class="block w-full h-11 rounded-xl border border-gray-200 bg-white px-4 pr-20 text-sm text-gray-900 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            />
                            <button
                                type="button"
                                id="togglePasswordConfirmation"
                                class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-gray-600 hover:text-gray-900"
                                aria-label="Toggle password confirmation visibility"
                            >
                                SHOW
                            </button>
                        </div>
                        @error('password_confirmation')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Back to sign in
                        </a>
                    </div>

                    <button
                        type="submit"
                        class="w-full h-11 rounded-xl bg-violet-600 text-white text-sm font-semibold shadow-sm hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2"
                    >
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                togglePassword.textContent = isPassword ? 'HIDE' : 'SHOW';
            });
        }

        const togglePasswordConfirmation = document.getElementById('togglePasswordConfirmation');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        if (togglePasswordConfirmation && passwordConfirmationInput) {
            togglePasswordConfirmation.addEventListener('click', function () {
                const isPassword = passwordConfirmationInput.type === 'password';
                passwordConfirmationInput.type = isPassword ? 'text' : 'password';
                togglePasswordConfirmation.textContent = isPassword ? 'HIDE' : 'SHOW';
            });
        }
    })();
</script>
</body>
</html>
