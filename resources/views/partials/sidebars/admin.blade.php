@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Enrollment', 'route' => 'admin.enrollments.*', 'href' => route('admin.enrollments.index')],
        ['label' => 'Course', 'route' => 'admin.courses.*', 'href' => route('admin.courses.index')],
        ['label' => 'Message', 'href' => '#'],
        ['label' => 'Support Forum', 'href' => '#'],
        ['label' => 'Settings', 'href' => '#'],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
        'Enrollment' => 'clipboard-check',
        'Course' => 'book-open',
        'Message' => 'message-circle',
        'Support Forum' => 'help-circle',
        'Settings' => 'settings',
        'Manage Users' => 'users',
    ];
@endphp

@foreach ($nav as $item)
    @php
        $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
    @endphp
    <a href="{{ $item['href'] }}"
       title="{{ $item['label'] }}"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons[$item['label']] ?? 'circle' }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">{{ $item['label'] }}</span>
        <span class="sr-only">{{ $item['label'] }}</span>
    </a>
@endforeach

@php
    $isManageUsersActive = request()->routeIs('admin.users.*');
@endphp

@role('Admin')
    <a href="{{ route('admin.users.index', ['role' => 'all', 'status' => 'active']) }}"
       title="Manage Users"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isManageUsersActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Manage Users'] ?? 'users' }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Manage Users</span>
        <span class="sr-only">Manage Users</span>
    </a>
@endrole
