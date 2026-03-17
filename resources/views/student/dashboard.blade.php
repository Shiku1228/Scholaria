@extends('layouts.dashboard', [
    'title' => 'Student Dashboard',
    'sidebarPartial' => 'partials.sidebars.student',
])

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-6">
            <div class="text-5xl font-extrabold tracking-tight text-slate-900">WELCOME STUDENT</div>
            <div class="mt-2 text-4xl text-slate-600">{{ auth()->user()->name }}</div>
            <div class="mt-1 text-sm text-slate-500">Dashboard</div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-3xl p-6 text-white shadow-md bg-gradient-to-r from-blue-500 to-blue-600">
                <div class="text-2xl font-medium text-white/95">Enrolled Courses</div>
                <div class="mt-2 text-6xl font-extrabold">{{ (int) ($stats['enrolled_courses'] ?? 0) }}</div>
                <div class="mt-2 text-base text-white/90">Across all courses</div>
            </div>
            <div class="rounded-3xl p-6 text-white shadow-md bg-gradient-to-r from-violet-500 to-purple-600">
                <div class="text-2xl font-medium text-white/95">In Progress</div>
                <div class="mt-2 text-6xl font-extrabold">{{ (int) ($stats['in_progress'] ?? 0) }}</div>
                <div class="mt-2 text-base text-white/90">Currently assigned</div>
            </div>
            <div class="rounded-3xl p-6 text-white shadow-md bg-gradient-to-r from-emerald-500 to-teal-600">
                <div class="text-2xl font-medium text-white/95">Completed</div>
                <div class="mt-2 text-6xl font-extrabold">{{ (int) ($stats['completed'] ?? 0) }}</div>
                <div class="mt-2 text-base text-white/90">Finished courses</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <a href="{{ route('student.assignments.index') }}" class="inline-flex items-center justify-center h-12 w-full rounded-xl border border-gray-300 bg-white text-lg font-medium text-slate-700 hover:bg-gray-50">View Assignments</a>
                <a href="{{ route('student.grades.index') }}" class="inline-flex items-center justify-center h-12 w-full rounded-xl border border-gray-300 bg-white text-lg font-medium text-slate-700 hover:bg-gray-50">View Grades</a>
                <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center h-12 w-full rounded-xl border border-gray-300 bg-white text-lg font-medium text-slate-700 hover:bg-gray-50">Message Teacher</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">My Courses</div>
                        <div class="text-xs text-gray-500">Continue where you left off</div>
                    </div>
                    <a href="{{ route('student.courses.index') }}" class="text-sm font-semibold text-[#0b2d6b] hover:text-[#0a275c]">View all</a>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($myCourses))
                        <div class="text-sm text-gray-500">You are not enrolled in any course yet.</div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($myCourses as $course)
                                @php
                                    $courseId = (int) data_get($course, 'course_id', 0);
                                    $progress = (int) data_get($course, 'progress', 0);
                                    $semesterLabel = match (strtolower((string) data_get($course, 'semester', ''))) {
                                        'first' => '1st Semester',
                                        'second' => '2nd Semester',
                                        'summer' => 'Summer',
                                        default => (string) (data_get($course, 'semester', '') ?: 'Semester'),
                                    };
                                    $code = (string) data_get($course, 'course_number', '');
                                    $done = (int) data_get($course, 'assignments_submitted', 0);
                                    $total = (int) data_get($course, 'assignments_total', 0);
                                    $bgClass = match ($courseId % 4) {
                                        0 => 'from-blue-600 to-indigo-600',
                                        1 => 'from-indigo-600 to-violet-600',
                                        2 => 'from-teal-600 to-emerald-600',
                                        default => 'from-orange-500 to-amber-500',
                                    };
                                @endphp
                                <div class="rounded-2xl border border-gray-100 bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                    <div class="h-36 relative bg-cover bg-center rounded-t-2xl" style="{{ !empty((string) data_get($course, 'cover_image', '')) ? 'background-image:url(' . e(asset('storage/' . ltrim((string) data_get($course, 'cover_image', ''), '/'))) . ');' : '' }}">
                                        @if (empty((string) data_get($course, 'cover_image', '')))
                                            <div class="absolute inset-0 bg-gradient-to-br {{ $bgClass }}"></div>
                                        @else
                                            <div class="absolute inset-0 bg-gradient-to-b from-white/55 via-white/20 to-slate-900/30"></div>
                                        @endif
                                        <div class="relative z-10 p-3 flex items-start justify-between gap-2">
                                            <span class="inline-flex items-center rounded-lg bg-white/75 text-slate-800 px-2 py-1 text-xs font-semibold">{{ $semesterLabel }}</span>
                                            @if ((string) data_get($course, 'school_year', '') !== '')
                                                <span class="inline-flex items-center rounded-lg bg-white/75 text-slate-800 px-2 py-1 text-xs font-semibold">{{ (string) data_get($course, 'school_year', '') }}</span>
                                            @endif
                                        </div>

                                        <div class="absolute inset-x-0 bottom-0 p-3">
                                            <div class="text-2xl font-bold text-white leading-tight drop-shadow-sm truncate">{{ (string) data_get($course, 'course_name', '') }}</div>
                                            <div class="mt-1 text-xs text-white/95 drop-shadow-sm">Teacher: {{ (string) data_get($course, 'teacher_name', 'N/A') }}{{ $code !== '' ? ' • ' . $code : '' }}</div>
                                        </div>
                                    </div>

                                    <div class="p-4">
                                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                            <span>Progress</span>
                                            <span class="font-semibold text-gray-700">{{ $progress }}%</span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-[#0b2d6b]" style="width: {{ $progress }}%"></div>
                                        </div>
                                        <div class="mt-2 text-xs text-slate-500">{{ $done }}/{{ $total }} assignments</div>

                                        <div class="mt-3">
                                            <a href="{{ $courseId > 0 ? route('student.courses.show', $courseId) : route('student.courses.index') }}" class="inline-flex items-center justify-center h-9 w-full rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Open Course</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Upcoming Assignments</div>
                    <div class="text-xs text-gray-500">Next 5 deadlines</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($upcomingAssignments))
                        <div class="text-sm text-gray-500">No assignments due yet. When teachers create assignments, they will appear here.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="py-2 pr-3">Assignment</th>
                                    <th class="py-2 pr-3">Course</th>
                                    <th class="py-2">Due Date</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                @foreach ($upcomingAssignments as $row)
                                    @php $assignmentId = (int) data_get($row, 'assignment_id', 0); @endphp
                                    <tr class="text-gray-700">
                                        <td class="py-3 pr-3 font-medium text-gray-900">
                                            <a href="{{ $assignmentId > 0 ? route('student.assignments.submit', $assignmentId) : '#' }}" class="hover:text-[#0b2d6b]">{{ (string) data_get($row, 'assignment_title', '') }}</a>
                                        </td>
                                        <td class="py-3 pr-3">{{ (string) data_get($row, 'course_name', '') }}</td>
                                        <td class="py-3 text-gray-500">{{ (string) data_get($row, 'due_date', '') }}</td>
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
                    <div class="text-sm font-semibold">Announcements</div>
                    <div class="text-xs text-gray-500">From your enrolled courses</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($recentAnnouncements))
                        <div class="text-sm text-gray-500">No announcements yet. When teachers post updates, they will show here.</div>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentAnnouncements as $a)
                                <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                    <div class="text-xs text-gray-500">{{ (string) data_get($a, 'course_name', '') }}</div>
                                    <div class="text-sm font-semibold text-gray-900 mt-1">{{ (string) data_get($a, 'title', '') }}</div>
                                    <div class="text-xs text-gray-500 mt-1">{{ (string) data_get($a, 'created_at', '') }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Learning Progress</div>
                <div class="text-xs text-gray-500">Progress across your courses</div>
            </div>
            <div class="p-4 sm:p-5">
                @if (empty($learningProgress))
                    <div class="text-sm text-gray-500">No progress to show yet. Once you enroll and start submitting assignments, progress will appear here.</div>
                @else
                    <div class="space-y-4">
                        @foreach ($learningProgress as $row)
                            @php $p = (int) data_get($row, 'progress', 0); @endphp
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="font-medium text-gray-900">{{ (string) data_get($row, 'course_name', '') }}</div>
                                    <div class="text-gray-500">{{ $p }}%</div>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                                    <div class="h-full bg-[#0b2d6b]" style="width: {{ $p }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
