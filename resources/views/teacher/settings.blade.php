@extends('layouts.teacher')

@section('content')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-xl font-semibold">Settings</div>
                <div class="mt-2 text-sm text-gray-500">Manage your account preferences</div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Notifications --}}
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <div class="text-sm font-semibold text-gray-900">Notifications</div>
                <div class="mt-1 text-sm text-gray-600">Quick actions for your teacher notifications.</div>

                <div class="mt-4">
                    <form method="POST" action="{{ route('teacher.notifications.read-all') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-[#0b2d6b] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0a275c] transition-colors">
                            Mark all as read
                        </button>
                    </form>
                </div>

                <div class="mt-3 text-xs text-gray-500">
                    Tip: You can open individual notifications from the bell dropdown.
                </div>
            </div>

            {{-- 2FA --}}
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                <div class="text-sm font-semibold text-gray-900">Two-Factor Authentication (2FA)</div>
                <div class="mt-1 text-sm text-gray-600">Add an extra layer of security to your account.</div>

                @php
                    $google2faEnabled = (bool) (auth()->user()->google2fa_enabled ?? false);
                @endphp

                <div class="mt-4 flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-gray-900">
                        Status:
                        <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $google2faEnabled ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $google2faEnabled ? 'Enabled' : 'Not enabled' }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row gap-2">
                    @if($google2faEnabled)
                        <a href="{{ route('2fa.verify.show') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50 transition-colors">
                            Manage / Verify
                        </a>
                    @else
                        <a href="{{ route('2fa.setup') }}"
                            class="inline-flex items-center justify-center rounded-xl bg-[#0b2d6b] px-4 py-2 text-sm font-semibold text-white hover:bg-[#0a275c] transition-colors">
                            Setup 2FA
                        </a>
                    @endif
                </div>

                <div class="mt-3 text-xs text-gray-500">
                    Current session may require verification depending on your 2FA flow.
                </div>
            </div>
        </div>

    </div>
@endsection

