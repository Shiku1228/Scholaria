@extends('layouts.dashboard', [
    'title' => 'Create Role',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <h2 class="text-xl font-semibold text-slate-900">Create New Role</h2>
        </div>
        
        <form action="{{ route('admin.roles.store') }}" method="POST" class="p-6">
            @csrf

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-slate-700 mb-2">Role Name</label>
                <input type="text" 
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] @error('name') border-red-500 @enderror" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}" 
                       placeholder="Enter role name"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-slate-700 mb-4">Permissions</label>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($permissions as $category => $categoryPermissions)
                        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                            <div class="bg-slate-100 px-4 py-3 border-b border-slate-200">
                                <h6 class="text-sm font-semibold text-slate-800">{{ $category }}</h6>
                            </div>
                            <div class="p-4 space-y-3">
                                @foreach($categoryPermissions as $permission)
                                    <div class="flex items-start">
                                        <div class="flex h-5 items-center">
                                            <input type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->id }}"
                                                   id="permission_{{ $permission->id }}"
                                                   class="h-4 w-4 rounded border-slate-300 text-[#0b2d6b] focus:ring-[#0b2d6b]">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="permission_{{ $permission->id }}" class="font-medium text-slate-700" title="{{ $permission->name }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('permissions')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Create Role
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
