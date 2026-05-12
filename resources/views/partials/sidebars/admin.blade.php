@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'href' => route('admin.dashboard'), 'permission' => null],
        ['label' => 'Security', 'route' => 'admin.security-dashboard.*', 'href' => route('admin.security-dashboard.index'), 'permission' => null],
        ['label' => 'Enrollment', 'route' => 'admin.enrollments.*', 'href' => route('admin.enrollments.index'), 'permission' => null],
        ['label' => 'Course', 'route' => 'admin.courses.*', 'href' => route('admin.courses.index'), 'permission' => 'courses.view'],
        ['label' => 'Message', 'href' => '#', 'permission' => null],
        ['label' => 'Support Forum', 'href' => '#', 'permission' => null],
        ['label' => 'Settings', 'href' => '#', 'permission' => null],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
        'Security' => 'shield',
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
        $hasPermission = !$item['permission'] || auth()->user()->can($item['permission']);
    @endphp
    @if($hasPermission)
    <a href="{{ $item['href'] }}"
       title="{{ $item['label'] }}"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons[$item['label']] ?? 'circle' }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">{{ $item['label'] }}</span>
        <span class="sr-only">{{ $item['label'] }}</span>
    </a>
    @endif
@endforeach

@php
    $isManageUsersActive = request()->routeIs('admin.users.*');
    $isRecordsActive = request()->routeIs('admin.records.*');
    $isRoleManagementActive = request()->routeIs('admin.roles.*');
@endphp

{{-- Manage Users - requires users.view permission --}}
@if(auth()->user()->can('users.view'))
    <a href="{{ route('admin.users.index', ['role' => 'all', 'status' => 'active']) }}"
       title="Manage Users"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isManageUsersActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Manage Users'] ?? 'users' }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Manage Users</span>
        <span class="sr-only">Manage Users</span>
    </a>
@endif

{{-- Role Management - requires roles.manage permission --}}
@if(auth()->user()->can('roles.manage'))
    <a href="{{ route('admin.roles.index') }}"
       title="Role Management"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isRoleManagementActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="shield-check" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Role Management</span>
        <span class="sr-only">Role Management</span>
    </a>
@endif

{{-- Records/Reports - requires reports.view permission --}}
@if(auth()->user()->can('reports.view'))
    <a href="{{ route('admin.records.index') }}"
       title="Records"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isRecordsActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="file-text" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Records</span>
        <span class="sr-only">Records</span>
    </a>
@endif

{{-- Settings - requires settings.view permission --}}
@if(auth()->user()->can('settings.view'))
    <a href="#"
       title="Settings"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors text-gray-700 hover:bg-gray-100">
        <i data-lucide="settings" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Settings</span>
        <span class="sr-only">Settings</span>
    </a>
@endif
