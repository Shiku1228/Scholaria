@extends('layouts.admin')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Manage Enrollments</div>
            <div class="text-sm text-gray-500">Enroll students into courses</div>
        </div>

        <a href="{{ route('admin.enrollments.create') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">
            Add Enrollment
        </a>
    </div>

    <form method="GET" action="{{ route('admin.enrollments.index') }}" class="mt-6 flex flex-col lg:flex-row lg:items-end gap-3">
        @php
            $studentId = (string) data_get($filters ?? [], 'student_id', request('student_id', ''));
            $courseId = (string) data_get($filters ?? [], 'course_id', request('course_id', ''));
            $teacherId = (string) data_get($filters ?? [], 'teacher_id', request('teacher_id', ''));
            $semester = (string) data_get($filters ?? [], 'semester', request('semester', ''));
            $status = (string) data_get($filters ?? [], 'status', request('status', ''));
        @endphp

        <div>
            <label class="block text-xs font-medium text-gray-600" for="student_id">Student</label>
            <select id="student_id" name="student_id" class="mt-1 h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="">All</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" {{ $studentId === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600" for="course_id">Course</label>
            <select id="course_id" name="course_id" class="mt-1 h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="">All</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" {{ $courseId === (string) $course->id ? 'selected' : '' }}>{{ $course->course_number }} - {{ $course->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600" for="teacher_id">Teacher</label>
            <select id="teacher_id" name="teacher_id" class="mt-1 h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="">All</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ $teacherId === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                @endforeach
            </select>
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

        <div>
            <label class="block text-xs font-medium text-gray-600" for="status">Status</label>
            <select id="status" name="status" class="mt-1 h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                <option value="" {{ $status === '' ? 'selected' : '' }}>All</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="dropped" {{ $status === 'dropped' ? 'selected' : '' }}>Dropped</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Apply
            </button>
            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Reset
            </a>
        </div>
    </form>

    <div class="mt-4 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Student</th>
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Teacher</th>
                <th class="py-3 px-4">Semester</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Enrolled</th>
                <th class="py-3 px-4 text-right">Action</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($enrollments as $enrollment)
                @php
                    $semesterLabel = match ($enrollment->course?->semester) {
                        'first' => '1st',
                        'second' => '2nd',
                        'summer' => 'Summer',
                        default => $enrollment->course?->semester,
                    };

                    $statusLabel = match ($enrollment->status) {
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'dropped' => 'Dropped',
                        default => $enrollment->status,
                    };

                    $statusClass = match ($enrollment->status) {
                        'active' => 'bg-green-50 text-green-700',
                        'completed' => 'bg-blue-50 text-blue-700',
                        'dropped' => 'bg-red-50 text-red-700',
                        default => 'bg-gray-50 text-gray-700',
                    };
                @endphp
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $enrollment->student?->name ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $enrollment->course?->course_number ?? '' }} {{ $enrollment->course?->title ? '- ' . $enrollment->course->title : '' }}</td>
                    <td class="py-3 px-4">{{ $enrollment->teacher?->name ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $semesterLabel ?? '—' }}</td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center h-6 px-2 rounded-lg text-xs font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td class="py-3 px-4">{{ optional($enrollment->enrolled_at)->format('Y-m-d') }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit</a>
                            <form method="POST" action="{{ route('admin.enrollments.destroy', $enrollment) }}" onsubmit="return confirm('Delete this enrollment?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700" style="background-color:#dc2626;color:#ffffff;border-radius:0.5rem;padding:0 0.75rem;height:2.25rem;display:inline-flex;align-items:center;justify-content:center;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-10 px-4 text-center text-sm text-gray-500">No enrollments yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $enrollments->links() }}
    </div>
@endsection
