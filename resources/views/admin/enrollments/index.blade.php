@extends('layouts.admin')

@section('content')
    @php
        $studentId = (string) data_get($filters ?? [], 'student_id', request('student_id', ''));
        $courseId = (string) data_get($filters ?? [], 'course_id', request('course_id', ''));
        $teacherId = (string) data_get($filters ?? [], 'teacher_id', request('teacher_id', ''));
        $semester = (string) data_get($filters ?? [], 'semester', request('semester', ''));
        $status = (string) data_get($filters ?? [], 'status', request('status', ''));
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <div class="text-3xl font-semibold text-slate-900">Manage Enrollments</div>
                    <div class="mt-1 text-sm text-slate-500">Enroll students into courses and manage their status.</div>
                </div>

                <a href="{{ route('admin.enrollments.create') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Add Enrollment
                </a>
            </div>

            <form method="GET" action="{{ route('admin.enrollments.index') }}" class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="student_id">Student</label>
                    <select id="student_id" name="student_id" class="mt-2 h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="">All</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" {{ $studentId === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="course_id">Course</label>
                    <select id="course_id" name="course_id" class="mt-2 h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="">All</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}" {{ $courseId === (string) $course->id ? 'selected' : '' }}>{{ $course->course_number }} - {{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="teacher_id">Teacher</label>
                    <select id="teacher_id" name="teacher_id" class="mt-2 h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="">All</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $teacherId === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="semester">Semester</label>
                    <select id="semester" name="semester" class="mt-2 h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="" {{ $semester === '' ? 'selected' : '' }}>All</option>
                        <option value="first" {{ $semester === 'first' ? 'selected' : '' }}>1st</option>
                        <option value="second" {{ $semester === 'second' ? 'selected' : '' }}>2nd</option>
                        <option value="summer" {{ $semester === 'summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600" for="status">Status</label>
                    <select id="status" name="status" class="mt-2 h-11 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="" {{ $status === '' ? 'selected' : '' }}>All</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="dropped" {{ $status === 'dropped' ? 'selected' : '' }}>Dropped</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Apply</button>
                    <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                        <th class="py-4 px-4">Student</th>
                        <th class="py-4 px-4">Course</th>
                        <th class="py-4 px-4">Teacher</th>
                        <th class="py-4 px-4">Semester</th>
                        <th class="py-4 px-4">Status</th>
                        <th class="py-4 px-4">Enrolled</th>
                        <th class="py-4 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($enrollments as $enrollment)
                        @php
                            $studentName = (string) ($enrollment->student?->name ?? '--');
                            $initial = strtoupper(substr($studentName, 0, 1));
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
                                'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'completed' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'dropped' => 'bg-red-50 text-red-700 border-red-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="text-slate-700 hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-[#eaf0fb] border border-[#c9d7f2] text-[#0b2d6b] flex items-center justify-center text-xs font-semibold">{{ $initial !== '' ? $initial : 'S' }}</div>
                                    <div class="font-semibold text-slate-900">{{ $studentName }}</div>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-slate-700">{{ $enrollment->course?->course_number ?? '' }} {{ $enrollment->course?->title ? '- ' . $enrollment->course->title : '' }}</td>
                            <td class="py-4 px-4">{{ $enrollment->teacher?->name ?? '--' }}</td>
                            <td class="py-4 px-4">{{ $semesterLabel ?? '--' }}</td>
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center h-7 px-3 rounded-full border text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="py-4 px-4 text-slate-500">{{ optional($enrollment->enrolled_at)->format('Y-m-d') }}</td>
                            <td class="py-4 px-4">
                                <div class="flex items-center justify-center gap-3">
                                    <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="text-slate-400 hover:text-[#0b2d6b]" title="Edit">
                                        <i data-lucide="pencil" class="h-4 w-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.enrollments.destroy', $enrollment) }}" onsubmit="return confirm('Delete this enrollment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-600" title="Delete">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-14 px-4 text-center text-sm text-slate-500">No enrollments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
            {{ $enrollments->links() }}
        </div>
    </div>
@endsection
