@extends('layouts.dashboard', [
    'title' => 'Role Management',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                        <i data-lucide="shield-check" class="h-5 w-5 text-[#0b2d6b]"></i>
                        <span>Role Management</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">Manage system roles and permissions.</div>
                </div>
            </div>
            <div class="mt-4">
                @can('roles.manage')
                    <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        Add New Role
                    </a>
                @endcan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
                        <th class="py-4 px-6">Role Name</th>
                        <th class="py-4 px-6">Permissions</th>
                        <th class="py-4 px-6">Users Count</th>
                        <th class="py-4 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($roles as $role)
                        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                            <td class="py-4 px-6">
                                <span @class([
                                    'inline-flex items-center h-7 px-3 rounded-lg border text-xs font-medium',
                                    'bg-red-50 text-red-700 border-red-200' => $role->name === 'Super Admin',
                                    'bg-[#eaf0fb] text-[#0b2d6b] border-[#c9d7f2]' => $role->name !== 'Super Admin',
                                ])>
                                    {{ $role->name }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    @foreach($role->permissions as $permission)
                                        <span class="inline-flex items-center h-6 px-2 rounded-md bg-slate-200 text-slate-700 text-[10px] font-medium" title="{{ $permission->name }}">
                                            {{ Str::limit($permission->name, 15) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center h-7 px-3 rounded-lg border bg-blue-50 border-blue-200 text-blue-700 text-xs font-medium">
                                    {{ $role->users()->count() }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center justify-center gap-3">
                                    @can('roles.manage')
                                        <a href="{{ route('admin.roles.edit', $role->id) }}" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-[#c9d7f2] bg-[#eaf0fb] text-[#0b2d6b] hover:bg-[#dce7fb]" title="Edit">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        
                                        @if($role->name !== 'Super Admin' && $role->users()->count() === 0)
                                            <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100" title="Delete">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-10 px-6 text-center text-sm text-slate-500">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
