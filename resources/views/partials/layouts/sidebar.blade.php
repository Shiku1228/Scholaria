<aside id="sidebar" class="fixed left-0 top-0 h-screen w-20 bg-white flex flex-col justify-between items-center py-6 shadow-md border-r border-gray-100 z-30">
    <div class="flex flex-col items-center space-y-6">
        <div class="slms-brand-row flex items-center justify-center gap-3 px-4 w-full">
            <div class="w-10 h-10 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center">
                <img src="{{ asset('SCHOLORIA LOGO.png') }}" alt="SCHOLARIA" class="w-8 h-8 object-contain" />
            </div>
            <div class="slms-brand-text text-sm font-semibold text-gray-800">SCHOLARIA</div>
        </div>

        <nav class="slms-sidebar-nav flex flex-col items-center space-y-6 mt-8">
            @if (isset($sidebarPartial) && is_string($sidebarPartial) && $sidebarPartial !== '')
                @include($sidebarPartial)
            @endif
        </nav>
    </div>

    <div class="flex flex-col items-center space-y-4">
        <hr class="w-10 border-gray-300">

        <form method="POST" action="{{ route('logout') }}" class="contents">
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
