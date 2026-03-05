@extends('layouts.teacher')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">My Courses</div>
            <div class="text-sm text-gray-500">Courses assigned to you</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Semester</th>
                <th class="py-3 px-4">School Year</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($courses as $course)
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">
                        <a href="{{ isset($course->id) ? route('teacher.courses.show', $course->id) : '#' }}" class="hover:underline">
                            {{ $course->course_number ?? $course->title ?? $course->name ?? $course->course_name ?? ('Course #' . ($course->id ?? '')) }}
                        </a>
                    </td>
                    <td class="py-3 px-4">{{ $course->semester ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $course->school_year ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-10 px-4 text-center text-sm text-gray-500">No courses found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
