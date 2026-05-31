@extends('layouts.dashboard', [
    'title' => 'Programs',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    <div class="px-6 py-6">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Programs</h1>
                <p class="text-sm text-slate-500 mt-1">Manage your program catalog.</p>
            </div>

            @can('programs.create')
                <a href="{{ route('admin.programs.create') }}"
                   class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Add Program
                </a>
            @endcan
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" action="{{ route('admin.programs.index') }}" class="flex flex-col md:flex-row gap-3 md:items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700" for="q">Search</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                           class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                           placeholder="Program name">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700" for="college_id">College</label>
                    <select id="college_id" name="college_id"
                            class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="0">All colleges</option>
                        @foreach($colleges as $college)
                            <option value="{{ $college->id }}" {{ (int)($filters['college_id'] ?? 0) === (int)$college->id ? 'selected' : '' }}>
                                {{ $college->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit"
                            class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <th class="py-4 px-6 text-left">#</th>
                        <th class="py-4 px-6 text-left">Program</th>
                        <th class="py-4 px-6 text-left">College</th>
                        <th class="py-4 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-slate-700">
                @forelse($programs as $program)
                    <tr class="border-t border-slate-200">
                        <td class="py-4 px-6">{{ $programs->firstItem() + $loop->index }}</td>
                        <td class="py-4 px-6">
                            <span class="font-medium">{{ $program->name }}</span>
                        </td>
                        <td class="py-4 px-6">
                            {{ $program->college?->name ?? '-' }}
                        </td>
                        <td class="py-4 px-6 text-right">
                            <div class="flex justify-end gap-2">
                                @can('programs.edit')
                                    <a href="{{ route('admin.programs.edit', $program) }}"
                                       class="inline-flex items-center justify-center h-9 px-3 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                        Edit
                                    </a>
                                @endcan

                                @can('programs.delete')
                                    <form action="{{ route('admin.programs.destroy', $program) }}" method="POST" onsubmit="return confirm('Delete this program?');">
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
                        <td colspan="4" class="py-10 px-6 text-center text-slate-500">
                            No programs found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $programs->links() }}
        </div>
    </div>
@endsection
