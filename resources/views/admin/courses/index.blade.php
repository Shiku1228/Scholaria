@extends('layouts.admin')

@section('content')
    @php
        $search = (string) data_get($filters ?? [], 'search', request('search', ''));
        $semester = (string) data_get($filters ?? [], 'semester', request('semester', ''));
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Manage Courses</h1>
                <p class="text-sm text-slate-500 mt-1">Create and manage courses</p>
            </div>
            <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                Add New Course
            </a>
        </div>

        <form method="GET" action="{{ route('admin.courses.index') }}" class="mt-6 flex flex-col lg:flex-row lg:items-end gap-4">
            <div class="flex-1">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="search">Search</label>
                <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Search by course number or title" class="mt-2 w-full h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
            </div>

            <div class="w-full sm:w-52">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="semester">Semester</label>
                <select id="semester" name="semester" class="mt-2 w-full h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                    <option value="" {{ $semester === '' ? 'selected' : '' }}>All</option>
                    <option value="first" {{ $semester === 'first' ? 'selected' : '' }}>1st</option>
                    <option value="second" {{ $semester === 'second' ? 'selected' : '' }}>2nd</option>
                    <option value="summer" {{ $semester === 'summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Apply
                </button>
                <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-4 bg-white rounded-2xl border border-slate-200 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <th class="py-4 px-4">Course #</th>
                    <th class="py-4 px-4">Title</th>
                    <th class="py-4 px-4">Semester</th>
                    <th class="py-4 px-4">School Year</th>
                    <th class="py-4 px-4">Teacher</th>
                    <th class="py-4 px-4 text-right">Action</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($courses as $course)
                    @php
                        $semesterLabel = match ($course->semester) {
                            'first' => '1st',
                            'second' => '2nd',
                            'summer' => 'Summer',
                            default => $course->semester,
                        };
                    @endphp
                    <tr class="text-slate-700">
                        <td class="py-4 px-4 font-semibold text-slate-900">{{ $course->course_number }}</td>
                        <td class="py-4 px-4">{{ $course->title }}</td>
                        <td class="py-4 px-4">{{ $semesterLabel }}</td>
                        <td class="py-4 px-4">{{ $course->school_year ?? '--' }}</td>
                        <td class="py-4 px-4">
                            @if ($course->teacher)
                                <div class="font-medium text-slate-900">{{ $course->teacher->name }}</div>
                                <div class="text-xs text-slate-500">{{ $course->teacher->email ?? '' }}</div>
                            @else
                                <span class="text-slate-400">Unassigned</span>
                            @endif
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.courses.edit', $course) }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-14 px-4 text-center text-sm text-slate-500">No courses yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $courses->links() }}
    </div>
@endsection

