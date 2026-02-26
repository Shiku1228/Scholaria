@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Enrollment', 'href' => '#'],
        ['label' => 'Course', 'href' => '#'],
        ['label' => 'Message', 'href' => '#'],
        ['label' => 'Notification', 'href' => '#'],
        ['label' => 'Support Forum', 'href' => '#'],
        ['label' => 'Settings', 'href' => '#'],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
        'Enrollment' => 'clipboard-check',
        'Course' => 'book-open',
        'Message' => 'message-circle',
        'Notification' => 'bell',
        'Support Forum' => 'help-circle',
        'Settings' => 'settings',
        'Manage Users' => 'users',
        'Logout' => 'log-out',
    ];
@endphp

<div class="space-y-1">
    @foreach ($nav as $item)
        @php
            $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
        @endphp
        <a href="{{ $item['href'] }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $isActive ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center {{ $isActive ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                <i data-lucide="{{ $icons[$item['label']] ?? 'circle' }}" style="width:18px;height:18px;display:block;line-height:0;"></i>
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach

    @php
        $isManageUsersActive = request()->routeIs('admin.users.*');
    @endphp

    <div class="pt-2">
        <a href="{{ route('admin.users.index', ['role' => 'all', 'status' => 'active']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $isManageUsersActive ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center {{ $isManageUsersActive ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                <i data-lucide="{{ $icons['Manage Users'] ?? 'users' }}" style="width:18px;height:18px;display:block;line-height:0;"></i>
            </span>
            <span class="flex-1">Manage Users</span>
        </a>

        <div class="ml-14 mt-2 space-y-1">
            <a href="{{ route('admin.users.index', ['role' => 'all', 'status' => 'active']) }}" class="block px-3 py-2 rounded-lg text-sm {{ request()->fullUrlIs(route('admin.users.index', ['role' => 'all', 'status' => 'active'])) ? 'text-violet-700 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                All (Active)
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'teacher', 'status' => 'active']) }}" class="block px-3 py-2 rounded-lg text-sm {{ request('role') === 'teacher' && request('status', 'active') === 'active' ? 'text-violet-700 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                Teachers
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'student', 'status' => 'active']) }}" class="block px-3 py-2 rounded-lg text-sm {{ request('role') === 'student' && request('status', 'active') === 'active' ? 'text-violet-700 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                Students
            </a>
            <a href="{{ route('admin.users.index', ['role' => 'all', 'status' => 'deleted']) }}" class="block px-3 py-2 rounded-lg text-sm {{ request('status') === 'deleted' ? 'text-violet-700 font-semibold' : 'text-gray-600 hover:text-gray-900' }}">
                Deleted
            </a>
        </div>
    </div>

    <div class="pt-3 mt-3 border-t border-gray-200">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                <span class="h-8 w-8 rounded-lg bg-gray-200 text-gray-700 flex items-center justify-center">
                    <i data-lucide="{{ $icons['Logout'] ?? 'log-out' }}" style="width:18px;height:18px;display:block;line-height:0;"></i>
                </span>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>
