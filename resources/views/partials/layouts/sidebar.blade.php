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
            @if (isset($sidebarPartial) && is_string($sidebarPartial) && $sidebarPartial !== '')
                @include($sidebarPartial)
            @endif
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

        <form method="POST" action="{{ route('web.logout') }}">
            @csrf
            <button type="submit" class="w-12 h-12 flex items-center justify-center rounded-xl hover:bg-gray-100 transition-colors">
                <i data-lucide="log-out" style="width:20px;height:20px;"></i>
                <span class="sr-only">Logout</span>
            </button>
        </form>

        <button id="sidebarExpandToggle" type="button" aria-expanded="false" class="hidden min-[992px]:flex w-12 h-12 items-center justify-center rounded-full bg-white shadow-md border border-gray-200">
            <i data-lucide="menu" style="width:18px;height:18px;"></i>
            <span class="sr-only">Toggle sidebar</span>
        </button>
    </div>
</aside>
