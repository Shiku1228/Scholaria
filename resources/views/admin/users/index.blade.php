@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Manage Users</div>
            <div class="text-sm text-gray-500">Manage teacher and student accounts</div>
        </div>

        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">
            Create User
        </a>
    </div>

    <form id="manageUsersFilterForm" method="GET" action="{{ route('admin.users.index') }}" class="mt-6 flex flex-col sm:flex-row sm:items-center gap-3">
        @php
            $role = (string) data_get($filters ?? [], 'role', request('role', 'all'));
            $status = (string) data_get($filters ?? [], 'status', request('status', 'active'));
        @endphp

        <input type="hidden" name="status" value="{{ $status }}">

        <div>
            <label class="block text-xs font-medium text-gray-600" for="role">Role</label>
            <select id="role" name="role" class="mt-1 h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="all" {{ $role === 'all' ? 'selected' : '' }}>All</option>
                <option value="Admin" {{ $role === 'Admin' ? 'selected' : '' }}>Admin</option>
                <option value="Teacher" {{ $role === 'Teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="Student" {{ $role === 'Student' ? 'selected' : '' }}>Student</option>
            </select>
        </div>
    </form>

    <div class="mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Name</th>
                <th class="py-3 px-4">Email</th>
                <th class="py-3 px-4">Role</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @foreach ($users as $user)
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $user->name }}</td>
                    <td class="py-3 px-4">{{ $user->email }}</td>
                    <td class="py-3 px-4">
                        @php
                            $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Student') : 'Student';
                            $labelRole = in_array($roleName, ['Admin', 'Teacher', 'Student'], true) ? $roleName : 'Student';
                            $badgeClass = match ($labelRole) {
                                'Admin' => 'bg-violet-50 text-violet-700',
                                'Teacher' => 'bg-blue-50 text-blue-700',
                                default => 'bg-gray-50 text-gray-700',
                            };
                        @endphp
                        <span class="inline-flex items-center h-6 px-2 rounded-lg text-xs font-semibold {{ $badgeClass }}">
                            {{ $labelRole }}
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        @if ($user->trashed())
                            <span class="inline-flex items-center h-6 px-2 rounded-lg text-xs font-semibold bg-red-50 text-red-700">deleted</span>
                        @else
                            <span class="inline-flex items-center h-6 px-2 rounded-lg text-xs font-semibold bg-green-50 text-green-700">active</span>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Edit
                            </a>

                            @if (!(method_exists($user, 'hasRole') && $user->hasRole('Admin')))
                                @if ($user->trashed())
                                    <form method="POST" action="{{ route('admin.users.restore', $user) }}" onsubmit="return confirm('Restore this user?');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-violet-600 text-white text-xs font-semibold hover:bg-violet-700" style="background-color:#7c3aed;color:#ffffff;border-radius:0.5rem;padding:0 0.75rem;height:2.25rem;display:inline-flex;align-items:center;justify-content:center;">
                                            Restore
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700" style="background-color:#dc2626;color:#ffffff;border-radius:0.5rem;padding:0 0.75rem;height:2.25rem;display:inline-flex;align-items:center;justify-content:center;">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <script>
        document.getElementById('role')?.addEventListener('change', function () {
            document.getElementById('manageUsersFilterForm')?.submit();
        });
    </script>
@endsection
