@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Manage Courses</div>
            <div class="text-sm text-gray-500">Create and manage courses</div>
        </div>

        <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">
            Add New Course
        </a>
    </div>

    <form method="GET" action="{{ route('admin.courses.index') }}" class="mt-6 flex flex-col lg:flex-row lg:items-end gap-3">
        @php
            $search = (string) data_get($filters ?? [], 'search', request('search', ''));
            $semester = (string) data_get($filters ?? [], 'semester', request('semester', ''));
        @endphp

        <div class="flex-1">
            <label class="block text-xs font-medium text-gray-600" for="search">Search</label>
            <input id="search" name="search" type="text" value="{{ $search }}" placeholder="Search by course number or title" class="mt-1 w-full h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500" />
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600" for="semester">Semester</label>
            <select id="semester" name="semester" class="mt-1 h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="" {{ $semester === '' ? 'selected' : '' }}>All</option>
                <option value="first" {{ $semester === 'first' ? 'selected' : '' }}>1st</option>
                <option value="second" {{ $semester === 'second' ? 'selected' : '' }}>2nd</option>
                <option value="summer" {{ $semester === 'summer' ? 'selected' : '' }}>Summer</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Apply
            </button>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Reset
            </a>
        </div>
    </form>

    <div class="mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Course #</th>
                <th class="py-3 px-4">Title</th>
                <th class="py-3 px-4">Semester</th>
                <th class="py-3 px-4">School Year</th>
                <th class="py-3 px-4">Teacher</th>
                <th class="py-3 px-4 text-right">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($courses as $course)
                @php
                    $semesterLabel = match ($course->semester) {
                        'first' => '1st',
                        'second' => '2nd',
                        'summer' => 'Summer',
                        default => $course->semester,
                    };
                @endphp
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $course->course_number }}</td>
                    <td class="py-3 px-4">{{ $course->title }}</td>
                    <td class="py-3 px-4">{{ $semesterLabel }}</td>
                    <td class="py-3 px-4">{{ $course->school_year ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $course->teacher?->name ?? '—' }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.courses.edit', $course) }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700" style="background-color:#dc2626;color:#ffffff;border-radius:0.5rem;padding:0 0.75rem;height:2.25rem;display:inline-flex;align-items:center;justify-content:center;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-10 px-4 text-center text-sm text-gray-500">No courses yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $courses->links() }}
    </div>
@endsection
