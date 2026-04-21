@extends('layouts.teacher')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Students (Roster)</div>
            <div class="text-sm text-gray-500">Students enrolled in your courses</div>
        </div>
    </div>

    <div class="mt-5 bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form id="rosterFilters" method="GET" class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 md:gap-4 md:items-end">
                <div>
                    <label for="course_id" class="block text-xs font-medium text-gray-600">Course</label>
                    <select id="course_id" name="course_id" class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-[color:var(--slms-primary)]">
                        <option value="">All courses</option>
                        @foreach(($courses ?? collect()) as $course)
                            <option value="{{ $course->id }}" @selected((string)($filters['course_id'] ?? '') === (string)$course->id)>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="student" class="block text-xs font-medium text-gray-600">Student</label>
                    <input id="student" name="student" value="{{ $filters['student'] ?? '' }}" placeholder="Search name..." class="mt-1 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[color:var(--slms-primary)]" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('teacher.students.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-[color:var(--slms-primary)] focus:ring-offset-2">
                    Reset
                </a>
            </div>
        </form>

        <script>
            (function () {
                var form = document.getElementById('rosterFilters');
                var course = document.getElementById('course_id');
                if (!form || !course) return;
                course.addEventListener('change', function () {
                    form.submit();
                });
            })();
        </script>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-600 border-b border-gray-100 bg-gray-50">
                <th class="py-3 px-4 font-semibold">Student</th>
                <th class="py-3 px-4 font-semibold">Course</th>
                <th class="py-3 px-4 font-semibold">Semester</th>
                <th class="py-3 px-4 font-semibold">Enrollment Date</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $row)
                <tr class="text-gray-700 hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium text-gray-900 whitespace-nowrap">{{ $row->student_name ?? '—' }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $row->course_name ?? '—' }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $row->semester ?? '—' }}</td>
                    <td class="py-3 px-4 whitespace-nowrap">{{ $row->enrolled_at ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-10 px-4 text-center text-sm text-gray-500">No students found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
