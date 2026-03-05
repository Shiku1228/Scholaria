@extends('layouts.dashboard', [
    'title' => 'Teacher Dashboard',
    'sidebarPartial' => 'partials.sidebars.teacher',
])

@section('content')
    <div class="space-y-6">
        @include('partials.dashboard.welcome', [
            'roleTitle' => 'TEACHER',
            'subtitle' => auth()->user()->name,
            'info' => 'Dashboard',
        ])

        @include('partials.dashboard.stats', [
            'items' => [
                ['label' => 'My Courses', 'value' => $stats['my_courses'] ?? 0, 'icon' => 'book-open'],
                ['label' => 'Students in My Courses', 'value' => $stats['students_in_my_courses'] ?? 0, 'icon' => 'users'],
                ['label' => 'New Enrollments (7 days)', 'value' => $stats['new_enrollments'] ?? 0, 'icon' => 'clipboard-check'],
            ],
        ])

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">My Course List</div>
                    <div class="text-xs text-gray-500">Latest courses</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($courseList))
                        <div class="text-sm text-gray-500">No data</div>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($courseList as $c)
                                <li class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">{{ $c['course_name'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Recent Activity</div>
                    <div class="text-xs text-gray-500">Submissions, enrollments, announcements</div>
                </div>
                <div class="p-4 sm:p-5 space-y-5">
                    <div>
                        <div class="text-xs font-semibold text-gray-600">Recent Submissions</div>
                        <div class="mt-2">
                            @if (empty($recentSubmissions))
                                <div class="text-sm text-gray-500">No data</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-xs text-gray-500">
                                            <th class="py-2 pr-3">Student</th>
                                            <th class="py-2 pr-3">Assignment</th>
                                            <th class="py-2 pr-3">Course</th>
                                            <th class="py-2">Submitted</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                        @foreach ($recentSubmissions as $row)
                                            <tr class="text-gray-700">
                                                <td class="py-3 pr-3 font-medium text-gray-900">{{ $row['student_name'] !== '' ? $row['student_name'] : '—' }}</td>
                                                <td class="py-3 pr-3">{{ $row['assignment_title'] !== '' ? $row['assignment_title'] : '—' }}</td>
                                                <td class="py-3 pr-3">{{ $row['course_name'] !== '' ? $row['course_name'] : '—' }}</td>
                                                <td class="py-3 text-gray-500">{{ $row['submitted_at'] !== '' ? $row['submitted_at'] : '—' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-gray-600">Recent Enrollments</div>
                        <div class="mt-2">
                            @if (empty($recentEnrollments))
                                <div class="text-sm text-gray-500">No data</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-xs text-gray-500">
                                            <th class="py-2 pr-3">Student</th>
                                            <th class="py-2 pr-3">Course</th>
                                            <th class="py-2">Enrolled</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                        @foreach ($recentEnrollments as $row)
                                            <tr class="text-gray-700">
                                                <td class="py-3 pr-3 font-medium text-gray-900">{{ $row['student_name'] !== '' ? $row['student_name'] : '—' }}</td>
                                                <td class="py-3 pr-3">{{ $row['course_name'] !== '' ? $row['course_name'] : '—' }}</td>
                                                <td class="py-3 text-gray-500">{{ $row['enrolled_at'] !== '' ? $row['enrolled_at'] : '—' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold text-gray-600">Recent Announcements</div>
                        <div class="mt-2">
                            @if (empty($recentAnnouncements))
                                <div class="text-sm text-gray-500">No data</div>
                            @else
                                <div class="space-y-2">
                                    @foreach ($recentAnnouncements as $row)
                                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="font-semibold text-gray-900">{{ $row['title'] !== '' ? $row['title'] : '—' }}</div>
                                                <div class="text-xs text-gray-500">{{ $row['created_at'] !== '' ? $row['created_at'] : '' }}</div>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $row['course_name'] !== '' ? $row['course_name'] : '—' }}</div>
                                            <div class="text-sm text-gray-700 mt-2">{{ $row['message'] !== '' ? $row['message'] : '' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Quick Actions</div>
                <div class="text-xs text-gray-500">Jump to common pages</div>
            </div>
            <div class="p-4 sm:p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">View My Courses</a>
                    <a href="{{ route('teacher.students.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">View Students</a>
                    <a href="{{ route('teacher.announcements') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Create Announcement</a>
                    <a href="{{ route('teacher.assignments.overview') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Create Assignment</a>
                    <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Upload Course Material</a>
                </div>
            </div>
        </div>
    </div>
@endsection
