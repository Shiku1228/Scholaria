@extends('layouts.dashboard', [
    'title' => 'Student Dashboard',
    'sidebarPartial' => 'partials.sidebars.student',
])

@section('content')
    <div class="space-y-6">
        @include('partials.dashboard.welcome', [
            'roleTitle' => 'STUDENT',
            'subtitle' => auth()->user()->name,
            'info' => 'Dashboard',
        ])

        @include('partials.dashboard.stats', [
            'items' => [
                ['label' => 'Enrolled Courses', 'value' => $stats['enrolled_courses'] ?? 0, 'icon' => 'book-open'],
                ['label' => 'In Progress', 'value' => $stats['in_progress'] ?? 0, 'icon' => 'activity'],
                ['label' => 'Completed', 'value' => $stats['completed'] ?? 0, 'icon' => 'check-check'],
            ],
        ])

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">My Courses</div>
                        <div class="text-xs text-gray-500">Continue where you left off</div>
                    </div>
                    <a href="{{ route('student.courses.index') }}" class="text-sm font-semibold text-violet-700 hover:text-violet-800">View all</a>
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
                                @endphp
                                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900 truncate">{{ (string) data_get($course, 'course_name', '') }}</div>
                                            <div class="text-xs text-gray-500 mt-1">Teacher: {{ (string) data_get($course, 'teacher_name', 'N/A') }}</div>
                                        </div>
                                        <div class="h-10 w-10 rounded-2xl bg-violet-50 flex items-center justify-center">
                                            <i data-lucide="book-open" style="width:18px;height:18px;color:rgb(109 40 217);"></i>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>Progress</span>
                                            <span class="font-semibold text-gray-700">{{ $progress }}%</span>
                                        </div>
                                        <div class="mt-2 h-2 rounded-full bg-white overflow-hidden border border-gray-100">
                                            <div class="h-full bg-violet-600" style="width: {{ $progress }}%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <a href="{{ $courseId > 0 ? route('student.assignments.index', ['course_id' => $courseId]) : route('student.assignments.index') }}" class="inline-flex items-center justify-center h-10 w-full rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Continue Course</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Quick Actions</div>
                    <div class="text-xs text-gray-500">Jump to common tasks</div>
                </div>
                <div class="p-4 sm:p-5 space-y-3">
                    <a href="{{ route('student.courses.index') }}" class="inline-flex items-center justify-center h-11 w-full rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Browse Courses</a>
                    <a href="{{ route('student.assignments.index') }}" class="inline-flex items-center justify-center h-11 w-full rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">View Assignments</a>
                    <a href="{{ route('student.grades.index') }}" class="inline-flex items-center justify-center h-11 w-full rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">View Grades</a>
                    <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center h-11 w-full rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Message Teacher</a>
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
                                            <a href="{{ $assignmentId > 0 ? route('student.assignments.submit', $assignmentId) : '#' }}" class="hover:text-violet-700">{{ (string) data_get($row, 'assignment_title', '') }}</a>
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
                                    <div class="h-full bg-violet-600" style="width: {{ $p }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
