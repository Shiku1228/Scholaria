@extends('layouts.dashboard', [
    'title' => 'Users',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    @php
        $role = (string) data_get($filters ?? [], 'role', request('role', 'all'));
        $status = (string) data_get($filters ?? [], 'status', request('status', 'active'));
        $query = (string) data_get($filters ?? [], 'q', request('q', ''));
        $total = $users->total();
        $from = $users->firstItem() ?? 0;
        $to = $users->lastItem() ?? 0;
        $showRoleId = in_array($role, ['all', 'Student', 'Teacher'], true);
        $deletedUsers = $deletedUsers ?? collect();
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                        <i data-lucide="users" class="h-5 w-5 text-[#0b2d6b]"></i>
                        <span>User Management</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">Manage platform users, roles, and access levels.</div>
                </div>

                <form id="manageUsersFilterForm" method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="relative">
                        <i data-lucide="search" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input
                            id="manageUsersSearch"
                            type="text"
                            name="q"
                            value="{{ $query }}"
                            placeholder="Search users..."
                            class="h-11 w-72 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                        >
                    </div>

                    <div class="relative">
                        <i data-lucide="funnel" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <select id="role" name="role" class="h-11 min-w-40 rounded-xl border border-slate-300 bg-slate-50 pl-9 pr-8 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All Roles</option>
                            @foreach ($availableRoles ?? [] as $roleName)
                                <option value="{{ $roleName }}" {{ $role === $roleName ? 'selected' : '' }}>
                                    {{ $roleName === 'Teacher' ? 'Instructor' : $roleName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="mt-4">
                @can('users.create')
                    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        Add User
                    </a>
                @endcan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
                        <th class="py-4 px-6">User</th>
                        <th class="py-4 px-6">Role ID</th>
                        <th class="py-4 px-6">Role</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6">Joined Date</th>
                        <th class="py-4 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        @php
                            $userRoles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();
                            $hasAdminRoleSpatie = false;
                            $hasTeacherRoleSpatie = false;

                            foreach ($userRoles as $rName) {
                                if ($rName === 'Teacher') {
                                    $hasTeacherRoleSpatie = true;
                                } elseif ($rName !== 'Student') {
                                    $hasAdminRoleSpatie = true;
                                }
                            }

                            $hasAdminProfile = $user->relationLoaded('admin') ? ($user->admin !== null) : (method_exists($user, 'admin') ? $user->admin()->exists() : false);
                            $hasTeacherProfile = $user->relationLoaded('teacher') ? ($user->teacher !== null) : (method_exists($user, 'teacher') ? $user->teacher()->exists() : false);

                            $hasAdminRole = $hasAdminRoleSpatie || $hasAdminProfile;
                            $hasTeacherRole = $hasTeacherRoleSpatie || $hasTeacherProfile;

                            // Role precedence for badge: Admin > Teacher > Student
                            $labelRole = $hasAdminRole ? 'Admin' : ($hasTeacherRole ? 'Teacher' : 'Student');

                            if ($labelRole === 'Admin') {
                                $displayRole = $userRoles->diff(['Teacher', 'Student'])->first() ?? 'Admin';
                            } else {
                                $displayRole = $labelRole === 'Teacher' ? 'Instructor' : $labelRole;
                            }

                            $isDeleted = $user->trashed();
                            $statusLabel = $isDeleted ? 'Suspended' : 'Active';

                            // Get profile data
                            $profile = $user->profile;
                            $fullName = $profile?->full_name ?? $user->name;
                            $roleId = match($labelRole) {
                                'Student' => $user->student?->student_number ?? '--',
                                'Teacher' => $user->teacher?->employee_id ?? '--',
                                'Admin' => 'ADM-' . str_pad((string) $user->id, 3, '0', STR_PAD_LEFT),
                                default => '--',
                            };

                            $initial = strtoupper(substr((string) $fullName, 0, 1));
                        @endphp

                        <tr
                            class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors"
                            data-user-search="{{ strtolower(trim($fullName . ' ' . $user->email . ' ' . $roleId . ' ' . $displayRole)) }}"
                        >
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-[#eaf0fb] border border-[#c9d7f2] text-[#0b2d6b] flex items-center justify-center text-xs font-semibold">
                                        {{ $initial !== '' ? $initial : 'U' }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 leading-tight">{{ $fullName }}</div>
                                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium">{{ $roleId }}</td>
                            <td class="py-4 px-6">
                                <span @class([
                                    'inline-flex items-center h-7 px-3 rounded-lg border text-xs font-medium',
                                    'bg-[#eaf0fb] text-[#0b2d6b] border-[#c9d7f2]' => $labelRole === 'Admin',
                                    'bg-blue-50 text-blue-700 border-blue-200' => $labelRole === 'Teacher',
                                    'bg-slate-100 text-slate-700 border-slate-200' => $labelRole === 'Student',
                                ])>
                                    {{ $displayRole }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span @class([
                                    'inline-flex items-center gap-2 h-7 px-3 rounded-full border text-xs font-medium',
                                    'bg-red-50 text-red-700 border-red-200' => $isDeleted,
                                    'bg-emerald-50 text-emerald-700 border-emerald-200' => !$isDeleted,
                                ])>
                                    <span @class([
                                        'h-1.5 w-1.5 rounded-full',
                                        'bg-red-500' => $isDeleted,
                                        'bg-emerald-500' => !$isDeleted,
                                    ])></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-500">{{ optional($user->created_at)->format('Y-m-d') }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-3">
                                    @can('users.view')
                                        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-[#c9d7f2] bg-[#eaf0fb] text-[#0b2d6b] hover:bg-[#dce7fb]" title="View">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </a>
                                    @endcan

                                    @can('users.edit')
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-[#c9d7f2] bg-[#eaf0fb] text-[#0b2d6b] hover:bg-[#dce7fb]" title="Edit">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                    @endcan

                                    @if (!(method_exists($user, 'hasRole') && $user->hasRole('Admin')))
                                        @can('users.delete')
                                            @if ($isDeleted)
                                                <form method="POST" action="{{ route('admin.users.restore', $user) }}" onsubmit="return confirm('Restore this user?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100" title="Restore">
                                                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Suspend this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100" title="Suspend">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="manageUsersEmptyState">
                            <td colspan="6" class="py-10 px-6 text-center text-sm text-slate-500">No users found for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="text-sm text-slate-500">Showing <span class="font-semibold text-slate-700">{{ $from }}</span> to <span class="font-semibold text-slate-700">{{ $to }}</span> of <span class="font-semibold text-slate-700">{{ number_format($total) }}</span> results</div>
            <div>
                {{ $users->onEachSide(1)->links() }}
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2 text-xl font-semibold text-slate-900">
                        <i data-lucide="archive-restore" class="h-5 w-5 text-amber-600"></i>
                        <span>Deleted Users</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">Restore suspended users from here when needed.</div>
                </div>
                <div class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                    {{ $deletedUsers->count() }} deleted {{ $deletedUsers->count() === 1 ? 'user' : 'users' }}
                </div>
            </div>
        </div>

        @if ($deletedUsers->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-slate-500">
                No deleted users available to restore.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
                            <th class="py-4 px-6">User</th>
                            <th class="py-4 px-6">Role ID</th>
                            <th class="py-4 px-6">Role</th>
                            <th class="py-4 px-6">Deleted Date</th>
                            <th class="py-4 px-6 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($deletedUsers as $deletedUser)
                            @php
                                $deletedUserRoles = method_exists($deletedUser, 'getRoleNames') ? $deletedUser->getRoleNames() : collect();
                                $deletedHasAdminRoleSpatie = false;
                                $deletedHasTeacherRoleSpatie = false;

                                foreach ($deletedUserRoles as $deletedRoleName) {
                                    if ($deletedRoleName === 'Teacher') {
                                        $deletedHasTeacherRoleSpatie = true;
                                    } elseif ($deletedRoleName !== 'Student') {
                                        $deletedHasAdminRoleSpatie = true;
                                    }
                                }

                                $deletedHasAdminProfile = $deletedUser->relationLoaded('admin') ? ($deletedUser->admin !== null) : false;
                                $deletedHasTeacherProfile = $deletedUser->relationLoaded('teacher') ? ($deletedUser->teacher !== null) : false;

                                $deletedLabelRole = $deletedHasAdminRoleSpatie || $deletedHasAdminProfile
                                    ? 'Admin'
                                    : (($deletedHasTeacherRoleSpatie || $deletedHasTeacherProfile) ? 'Teacher' : 'Student');

                                $deletedDisplayRole = $deletedLabelRole === 'Admin'
                                    ? ($deletedUserRoles->diff(['Teacher', 'Student'])->first() ?? 'Admin')
                                    : ($deletedLabelRole === 'Teacher' ? 'Instructor' : 'Student');

                                $deletedProfile = $deletedUser->profile;
                                $deletedFullName = $deletedProfile?->full_name ?? $deletedUser->name;
                                $deletedRoleId = match($deletedLabelRole) {
                                    'Student' => $deletedUser->student?->student_number ?? '--',
                                    'Teacher' => $deletedUser->teacher?->employee_id ?? '--',
                                    'Admin' => 'ADM-' . str_pad((string) $deletedUser->id, 3, '0', STR_PAD_LEFT),
                                    default => '--',
                                };
                                $deletedInitial = strtoupper(substr((string) $deletedFullName, 0, 1));
                            @endphp

                            <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-amber-50 border border-amber-200 text-amber-700 flex items-center justify-center text-xs font-semibold">
                                            {{ $deletedInitial !== '' ? $deletedInitial : 'U' }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-slate-900 leading-tight">{{ $deletedFullName }}</div>
                                            <div class="text-xs text-slate-500">{{ $deletedUser->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-xs text-slate-500 font-medium">{{ $deletedRoleId }}</td>
                                <td class="py-4 px-6">
                                    <span @class([
                                        'inline-flex items-center h-7 px-3 rounded-lg border text-xs font-medium',
                                        'bg-[#eaf0fb] text-[#0b2d6b] border-[#c9d7f2]' => $deletedLabelRole === 'Admin',
                                        'bg-blue-50 text-blue-700 border-blue-200' => $deletedLabelRole === 'Teacher',
                                        'bg-slate-100 text-slate-700 border-slate-200' => $deletedLabelRole === 'Student',
                                    ])>
                                        {{ $deletedDisplayRole }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-slate-500">{{ optional($deletedUser->deleted_at)->format('Y-m-d H:i') }}</td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center gap-2">
                                        @can('users.delete')
                                            @if (!(method_exists($deletedUser, 'hasRole') && $deletedUser->hasRole('Admin')))
                                                <form method="POST" action="{{ route('admin.users.restore', $deletedUser) }}" onsubmit="return confirm('Restore this user?');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                                                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                                                        <span>Restore</span>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.users.force-destroy', $deletedUser) }}" onsubmit="return confirm('Permanently delete this user? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
                                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                        <span>Delete Permanently</span>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script>
        const usersFilterForm = document.getElementById('manageUsersFilterForm');
        const roleSelect = document.getElementById('role');
        const searchInput = document.getElementById('manageUsersSearch');
        const userRows = Array.from(document.querySelectorAll('tbody tr[data-user-search]'));
        const emptyStateRow = document.getElementById('manageUsersEmptyState');
        const resultsSummary = document.querySelector('.px-6.py-4.border-t .text-sm.text-slate-500');

        roleSelect?.addEventListener('change', function () {
            usersFilterForm?.submit();
        });

        function updateVisibleRows() {
            if (!searchInput) {
                return;
            }

            const query = searchInput.value.trim().toLowerCase();
            let visibleCount = 0;

            userRows.forEach(function (row) {
                const haystack = row.dataset.userSearch || '';
                const words = haystack
                    .split(/[^a-z0-9@.\-]+/i)
                    .map(function (word) { return word.trim(); })
                    .filter(Boolean);

                const queryParts = query
                    .split(/\s+/)
                    .map(function (part) { return part.trim(); })
                    .filter(Boolean);

                const isMatch = queryParts.length === 0 || queryParts.every(function (part) {
                    return words.some(function (word) {
                        return word.startsWith(part);
                    });
                });
                row.classList.toggle('hidden', !isMatch);

                if (isMatch) {
                    visibleCount += 1;
                }
            });

            if (emptyStateRow) {
                emptyStateRow.classList.toggle('hidden', visibleCount !== 0);
            }

            if (resultsSummary) {
                if (query === '') {
                    resultsSummary.textContent = 'Showing {{ $from }} to {{ $to }} of {{ number_format($total) }} results';
                } else {
                    resultsSummary.textContent = 'Showing ' + visibleCount + ' filtered result' + (visibleCount === 1 ? '' : 's');
                }
            }
        }

        searchInput?.addEventListener('input', updateVisibleRows);
        searchInput?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        updateVisibleRows();
    </script>
@endsection
