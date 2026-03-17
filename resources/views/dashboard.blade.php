<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen">
<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6">
        <div class="text-2xl font-semibold">Dashboard</div>
        <div class="mt-2 text-sm text-gray-500">You are signed in.</div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                Go to Admin Dashboard
            </a>

            <a href="{{ route('logout') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Logout
            </a>
        </div>
    </div>
</div>
</body>
</html>

