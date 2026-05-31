@extends('layouts.dashboard', [
    'title' => 'Colleges',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    <div class="px-6 py-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Colleges</h1>
                <p class="text-sm text-slate-500 mt-1">Manage your college catalog.</p>
            </div>

            @can('colleges.create')
                <a href="{{ route('admin.colleges.create') }}"
                   class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Add College
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" action="{{ route('admin.colleges.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700" for="q">Search</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                           class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                           placeholder="College name">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <th class="py-4 px-6 text-left">#</th>
                        <th class="py-4 px-6 text-left">Name</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                @forelse($colleges as $college)
                    <tr class="border-t border-slate-200">
                        <td class="py-4 px-6">{{ $colleges->firstItem() + $loop->index }}</td>
                        <td class="py-4 px-6">
                            <span class="font-medium">{{ $college->name }}</span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex justify-end gap-2">
                                @can('colleges.edit')
                                    <a href="{{ route('admin.colleges.edit', $college) }}"
                                       class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                        Edit
                                    </a>
                                @endcan

                                @can('colleges.delete')
                                    <form action="{{ route('admin.colleges.destroy', $college) }}" method="POST" onsubmit="return confirm('Delete this college? This may affect related programs.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center h-9 px-3 rounded-xl bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-10 px-6 text-center text-slate-500">
                            No colleges found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $colleges->links() }}
        </div>
    </div>
@endsection
