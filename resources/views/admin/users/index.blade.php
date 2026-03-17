@extends('layouts.admin')

@section('content')
    @php
        $role = (string) data_get($filters ?? [], 'role', request('role', 'all'));
        $status = (string) data_get($filters ?? [], 'status', request('status', 'active'));
        $query = (string) data_get($filters ?? [], 'q', request('q', ''));
        $total = $users->total();
        $from = $users->firstItem() ?? 0;
        $to = $users->lastItem() ?? 0;
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
                            type="text"
                            name="q"
                            value="{{ $query }}"
                            placeholder="Search users..."
                            class="h-11 w-72 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                        >
                    </div>

                    <div class="relative">
                        <i data-lucide="funnel" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                        <select id="role" name="role" class="h-11 min-w-32 rounded-xl border border-slate-300 bg-slate-50 pl-9 pr-8 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All Roles</option>
                            <option value="Admin" {{ $role === 'Admin' ? 'selected' : '' }}>Admin</option>
                            <option value="Teacher" {{ $role === 'Teacher' ? 'selected' : '' }}>Instructor</option>
                            <option value="Student" {{ $role === 'Student' ? 'selected' : '' }}>Student</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="mt-4">
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Add User
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
                        <th class="py-4 px-6">User</th>
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Role</th>
                        <th class="py-4 px-6">Status</th>
                        <th class="py-4 px-6">Joined Date</th>
                        <th class="py-4 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($users as $user)
                        @php
                            $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Student') : 'Student';
                            $labelRole = in_array($roleName, ['Admin', 'Teacher', 'Student'], true) ? $roleName : 'Student';
                            $displayRole = $labelRole === 'Teacher' ? 'Instructor' : $labelRole;
                            $roleBadgeClass = match ($labelRole) {
                                'Admin' => 'bg-[#eaf0fb] text-[#0b2d6b] border-[#c9d7f2]',
                                'Teacher' => 'bg-blue-50 text-blue-700 border-blue-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $isDeleted = $user->trashed();
                            $statusClass = $isDeleted
                                ? 'bg-red-50 text-red-700 border-red-200'
                                : 'bg-emerald-50 text-emerald-700 border-emerald-200';
                            $statusLabel = $isDeleted ? 'Suspended' : 'Active';
                            $initial = strtoupper(substr((string) $user->name, 0, 1));
                        @endphp

                        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-[#eaf0fb] border border-[#c9d7f2] text-[#0b2d6b] flex items-center justify-center text-xs font-semibold">
                                        {{ $initial !== '' ? $initial : 'U' }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 leading-tight">{{ $user->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium">USR-{{ str_pad((string) $user->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center h-7 px-3 rounded-lg border text-xs font-medium {{ $roleBadgeClass }}">
                                    {{ $displayRole }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center gap-2 h-7 px-3 rounded-full border text-xs font-medium {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $isDeleted ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-slate-500">{{ optional($user->created_at)->format('Y-m-d') }}</td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-[#c9d7f2] bg-[#eaf0fb] text-[#0b2d6b] hover:bg-[#dce7fb]" title="Edit">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>

                                    @if (!(method_exists($user, 'hasRole') && $user->hasRole('Admin')))
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
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
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

    <script>
        const usersFilterForm = document.getElementById('manageUsersFilterForm');
        const roleSelect = document.getElementById('role');

        roleSelect?.addEventListener('change', function () {
            usersFilterForm?.submit();
        });

        let searchTimer = null;
        usersFilterForm?.querySelector('input[name="q"]')?.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                usersFilterForm.submit();
            }, 350);
        });
    </script>
@endsection

