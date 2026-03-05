@extends('layouts.teacher')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
            <div class="text-sm text-gray-500">{{ $course->title ?? '' }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.assignments.index', $course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Assignments</a>
            <a href="{{ route('teacher.announcements.index', $course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Announcements</a>
            <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Students in Course</div>
                <div class="text-xs text-gray-500">Latest enrollments</div>
            </div>
            <div class="p-4 sm:p-5">
                @if ($students->isEmpty())
                    <div class="text-sm text-gray-500">No students found.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                                <th class="py-3 pr-3">Student</th>
                                <th class="py-3">Enrolled At</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach ($students as $enrollment)
                                <tr class="text-gray-700">
                                    <td class="py-3 pr-3 font-medium text-gray-900">{{ $enrollment->student?->name ?? '—' }}</td>
                                    <td class="py-3 text-gray-500">{{ $enrollment->enrolled_at ?? $enrollment->created_at ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Assignments</div>
                <div class="text-xs text-gray-500">Manage coursework</div>
            </div>
            <div class="p-4 sm:p-5">
                @if ($assignments->isEmpty())
                    <div class="text-sm text-gray-500">No assignments yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                                <th class="py-3 pr-3">Title</th>
                                <th class="py-3 pr-3">Due</th>
                                <th class="py-3">Submissions</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach ($assignments as $a)
                                <tr class="text-gray-700">
                                    <td class="py-3 pr-3 font-medium text-gray-900">
                                        <a href="{{ route('teacher.assignments.show', [$course, $a]) }}" class="hover:underline">{{ $a->title ?? '—' }}</a>
                                    </td>
                                    <td class="py-3 pr-3 text-gray-500">{{ $a->due_date ?? '—' }}</td>
                                    <td class="py-3 text-gray-500">{{ $a->submissions_count ?? 0 }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 xl:col-span-2">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Announcements</div>
                <div class="text-xs text-gray-500">Latest posts</div>
            </div>
            <div class="p-4 sm:p-5">
                @if ($announcements->isEmpty())
                    <div class="text-sm text-gray-500">No announcements yet.</div>
                @else
                    <div class="space-y-3">
                        @foreach ($announcements as $n)
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <a href="{{ route('teacher.announcements.show', [$course, $n]) }}" class="font-semibold text-gray-900 hover:underline">{{ $n->title ?? '—' }}</a>
                                    <div class="text-xs text-gray-500">{{ $n->created_at ?? '' }}</div>
                                </div>
                                <div class="text-sm text-gray-700 mt-2">{{ $n->message ?? '' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
