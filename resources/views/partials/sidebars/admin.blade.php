@php
    $icons = [
        'Dashboard' => 'layout-dashboard',
        'Security' => 'shield',
        'Role Management' => 'shield-check',
        'Enrollment' => 'clipboard-check',
        'Course' => 'book-open',
        'Announcements' => 'megaphone',
        'Message' => 'message-circle',
        'Support Forum' => 'help-circle',
        'Settings' => 'settings',
        'Manage Users' => 'users',
        'Records' => 'file-text',
        'Colleges' => 'building',
        'Programs' => 'layers',
    ];
@endphp

{{-- Dashboard - visible to all admin roles --}}
@php
    $isDashboardActive = request()->routeIs('admin.dashboard');
@endphp
<a href="{{ route('admin.dashboard') }}"
   title="Dashboard"
   class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isDashboardActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
    <i data-lucide="{{ $icons['Dashboard'] }}" style="width:20px;height:20px;"></i>
    <span class="slms-nav-label ml-3">Dashboard</span>
    <span class="sr-only">Dashboard</span>
</a>

{{-- Security - Super Admin only (roles.manage permission) --}}
@can('roles.manage')
    @php
        $isSecurityActive = request()->routeIs('admin.security-dashboard.*');
    @endphp
    <a href="{{ route('admin.security-dashboard.index') }}"
       title="Security"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isSecurityActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Security'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Security</span>
        <span class="sr-only">Security</span>
    </a>
@endcan

{{-- Role Management - Super Admin only (roles.manage permission) --}}
@can('roles.manage')
    @php
        $isRoleManagementActive = request()->routeIs('admin.roles.*');
    @endphp
    <a href="{{ route('admin.roles.index') }}"
       title="Role Management"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isRoleManagementActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Role Management'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Role Management</span>
        <span class="sr-only">Role Management</span>
    </a>
@endcan
{{-- Enrollment - needs users.view permission (all admins except Content Admin) --}}
@can('users.view')
    @php
        $isEnrollmentActive = request()->routeIs('admin.enrollments.*');
    @endphp
    <a href="{{ route('admin.enrollments.index') }}"
       title="Enrollment"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isEnrollmentActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Enrollment'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Enrollment</span>
        <span class="sr-only">Enrollment</span>
    </a>
@endcan

{{-- Course - needs courses.view permission (Super Admin, Content Admin) --}}
@can('courses.view')
    @php
        $isCourseActive = request()->routeIs('admin.courses.*');
    @endphp
    <a href="{{ route('admin.courses.index') }}"
       title="Course"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isCourseActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Course'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Course</span>
        <span class="sr-only">Course</span>
    </a>
@endcan

{{-- Settings - needs settings.view permission (Super Admin, Settings Admin) --}}
@can('settings.view')
    @php
        $isSettingsActive = request()->routeIs('admin.settings.*');
    @endphp
    <a href="#"
       title="Settings"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isSettingsActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Settings'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Settings</span>
        <span class="sr-only">Settings</span>
    </a>
@endcan

{{-- Manage Users - needs users.view permission (all admins) --}}
@can('users.view')
    @php
        $isManageUsersActive = request()->routeIs('admin.users.*');
    @endphp
    <a href="{{ route('admin.users.index', ['role' => 'all', 'status' => 'active']) }}"
       title="Manage Users"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isManageUsersActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Manage Users'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Manage Users</span>
        <span class="sr-only">Manage Users</span>
    </a>
@endcan

{{-- Colleges - needs colleges.view permission --}}
@can('colleges.view')
    @php
        $isCollegesActive = request()->routeIs('admin.colleges.*');
    @endphp
    <a href="{{ route('admin.colleges.index') }}"
       title="Colleges"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isCollegesActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Colleges'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Colleges</span>
        <span class="sr-only">Colleges</span>
    </a>
@endcan

{{-- Programs - needs programs.view permission --}}
@can('programs.view')
    @php
        $isProgramsActive = request()->routeIs('admin.programs.*');
    @endphp
    <a href="{{ route('admin.programs.index') }}"
       title="Programs"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isProgramsActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Programs'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Programs</span>
        <span class="sr-only">Programs</span>
    </a>
@endcan

{{-- Records - needs reports.view permission (Super Admin, Report Admin) --}}
@can('reports.view')
    @php
        $isRecordsActive = request()->routeIs('admin.records.*');
    @endphp
    <a href="{{ route('admin.records.index') }}"
       title="Records"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isRecordsActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons['Records'] }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">Records</span>
        <span class="sr-only">Records</span>
    </a>
@endcan
