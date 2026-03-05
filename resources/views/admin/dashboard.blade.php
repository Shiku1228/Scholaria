@extends('layouts.dashboard', [
    'title' => 'Admin Dashboard',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    <div class="space-y-6">
        @include('partials.dashboard.welcome', [
            'roleTitle' => 'ADMIN',
            'subtitle' => auth()->user()->name,
            'info' => 'Dashboard',
        ])

        @include('partials.dashboard.stats', [
            'items' => [
                ['label' => 'Total Courses', 'value' => $stats['total_courses'] ?? 0, 'icon' => 'book-open'],
                ['label' => 'Total Students', 'value' => $stats['total_students'] ?? 0, 'icon' => 'users'],
                ['label' => 'Total Teachers', 'value' => $stats['total_teachers'] ?? 0, 'icon' => 'user'],
                ['label' => 'Total Enrollments', 'value' => $stats['total_enrollments'] ?? 0, 'icon' => 'clipboard-check'],
            ],
        ])

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Quick Actions</div>
                    <div class="text-xs text-gray-500">Common admin tasks</div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a href="{{ route('admin.courses.create') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Create Course</a>
                        <a href="{{ route('admin.users.create', ['role' => 'Teacher']) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Add Teacher</a>
                        <a href="{{ route('admin.users.create', ['role' => 'Student']) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Add Student</a>
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Create Announcement</a>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Recent Activity</div>
                    <div class="text-xs text-gray-500">Latest system events</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($recentActivity))
                        <div class="text-sm text-gray-500">No recent activity yet. When users start enrolling and submitting work, activity will appear here.</div>
                    @else
                        <div class="space-y-3">
                            @foreach ($recentActivity as $item)
                                <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-100 bg-gray-50 p-4">
                                    <div class="text-sm text-gray-700">{{ (string) ($item['message'] ?? '') }}</div>
                                    <div class="text-xs text-gray-500 whitespace-nowrap">{{ (string) ($item['happened_at'] ?? '') }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">System Overview</div>
                    <div class="text-xs text-gray-500">Health metrics</div>
                </div>
                <div class="p-4 sm:p-5 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Active Courses</span>
                        <span class="font-semibold text-gray-900">{{ number_format((int) data_get($systemOverview, 'active_courses', 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Inactive Courses</span>
                        <span class="font-semibold text-gray-900">{{ number_format((int) data_get($systemOverview, 'inactive_courses', 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Teachers without courses</span>
                        <span class="font-semibold text-gray-900">{{ number_format((int) data_get($systemOverview, 'teachers_without_courses', 0)) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Students not enrolled</span>
                        <span class="font-semibold text-gray-900">{{ number_format((int) data_get($systemOverview, 'students_not_enrolled', 0)) }}</span>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold">Overview</div>
                        <div class="text-xs text-gray-500">Courses / Enrollments / Users</div>
                    </div>

                    <form id="overviewFilterForm" method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        <select name="range" id="rangeSelect" class="h-10 rounded-lg border-gray-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                            <option value="7d" {{ $filters['range'] === '7d' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30d" {{ $filters['range'] === '30d' ? 'selected' : '' }}>Last 30 days</option>
                            <option value="12m" {{ $filters['range'] === '12m' ? 'selected' : '' }}>Last 12 months</option>
                        </select>
                    </form>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="h-[320px]">
                        <canvas id="overviewChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Recent Enrollments</div>
                    <div class="text-xs text-gray-500">Most recent enrollments</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($recentEnrollments))
                        <div class="text-sm text-gray-500">No enrollments yet. When students enroll in courses, they will appear here.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="py-2 pr-3">Student Name</th>
                                    <th class="py-2 pr-3">Course</th>
                                    <th class="py-2">Date Enrolled</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                @foreach ($recentEnrollments as $row)
                                    <tr class="text-gray-700">
                                        <td class="py-3 pr-3 font-medium text-gray-900">{{ (string) ($row['student_name'] ?? '—') }}</td>
                                        <td class="py-3 pr-3">{{ (string) ($row['course_name'] ?? '—') }}</td>
                                        <td class="py-3 text-gray-500">{{ (string) ($row['enrolled_at'] ?? '—') }}</td>
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
                    <div class="text-sm font-semibold">Best Course</div>
                    <div class="text-xs text-gray-500">Top course by enrollments</div>
                </div>
                <div class="p-4 sm:p-5">
                    @php
                        $courseId = (int) data_get($bestSellingCourse ?? [], 'course_id', 0);
                        $courseName = (string) data_get($bestSellingCourse ?? [], 'course_name', '');
                        $teacherName = (string) data_get($bestSellingCourse ?? [], 'teacher_name', '');
                        $sales = (int) data_get($bestSellingCourse ?? [], 'sales', 0);
                    @endphp

                    @if ($courseName === '')
                        <div class="text-sm text-gray-500">No courses have enrollments yet. Once students enroll, the top course will appear here.</div>
                    @else
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ $courseName }}</div>
                                <div class="text-xs text-gray-500 mt-1">Teacher: {{ $teacherName !== '' ? $teacherName : 'N/A' }}</div>
                                <div class="mt-3 inline-flex items-center h-7 px-2 rounded-lg bg-violet-50 text-violet-700 text-xs font-semibold">
                                    {{ number_format($sales) }} students
                                </div>
                            </div>
                            <div>
                                <a href="{{ $courseId > 0 ? route('admin.courses.edit', $courseId) : '#' }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">View Course</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Enrollments (Last 7 Days)</div>
                    <div class="text-xs text-gray-500">Daily enrollments</div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="h-[240px]">
                        <canvas id="enrollments7dChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Courses Created (Per Month)</div>
                    <div class="text-xs text-gray-500">Last 12 months</div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="h-[240px]">
                        <canvas id="coursesPerMonthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Students per Course</div>
                    <div class="text-xs text-gray-500">Top 10 courses</div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="h-[300px]">
                        <canvas id="studentsPerCourseChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">User Growth</div>
                    <div class="text-xs text-gray-500">Students vs Teachers (Last 30 days)</div>
                </div>
                <div class="p-4 sm:p-5">
                    <div class="h-[300px]">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const overview = @json($overview);
        const analytics = @json($analytics);

        const overviewCtx = document.getElementById('overviewChart');

        const gridColor = 'rgba(148, 163, 184, 0.25)';

        new Chart(overviewCtx, {
            type: 'bar',
            data: {
                labels: overview.labels,
                datasets: [
                    {
                        label: 'Courses',
                        data: overview.series.courses,
                        backgroundColor: 'rgba(124, 58, 237, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 24,
                    },
                    {
                        label: 'Enrollments',
                        data: overview.series.enrollments,
                        backgroundColor: 'rgba(167, 139, 250, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 24,
                    },
                    {
                        label: 'Users',
                        data: overview.series.users,
                        backgroundColor: 'rgba(96, 165, 250, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 24,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { precision: 0 }
                    }
                }
            }
        });

        document.getElementById('rangeSelect')?.addEventListener('change', function () {
            document.getElementById('overviewFilterForm')?.submit();
        });

        const enrollments7dCtx = document.getElementById('enrollments7dChart');
        if (enrollments7dCtx && analytics?.enrollments_last_7_days?.labels?.length) {
            new Chart(enrollments7dCtx, {
                type: 'line',
                data: {
                    labels: analytics.enrollments_last_7_days.labels,
                    datasets: [{
                        label: 'Enrollments',
                        data: analytics.enrollments_last_7_days.data,
                        borderColor: 'rgba(124, 58, 237, 0.95)',
                        backgroundColor: 'rgba(124, 58, 237, 0.12)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                    }
                }
            });
        }

        const coursesPerMonthCtx = document.getElementById('coursesPerMonthChart');
        if (coursesPerMonthCtx && analytics?.courses_per_month?.labels?.length) {
            new Chart(coursesPerMonthCtx, {
                type: 'bar',
                data: {
                    labels: analytics.courses_per_month.labels,
                    datasets: [{
                        label: 'Courses',
                        data: analytics.courses_per_month.data,
                        backgroundColor: 'rgba(167, 139, 250, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 24,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                    }
                }
            });
        }

        const studentsPerCourseCtx = document.getElementById('studentsPerCourseChart');
        if (studentsPerCourseCtx && analytics?.students_per_course?.labels?.length) {
            new Chart(studentsPerCourseCtx, {
                type: 'bar',
                data: {
                    labels: analytics.students_per_course.labels,
                    datasets: [{
                        label: 'Students',
                        data: analytics.students_per_course.data,
                        backgroundColor: 'rgba(96, 165, 250, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 28,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { mode: 'index', intersect: false },
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                        y: { grid: { display: false } },
                    }
                }
            });
        }

        const userGrowthCtx = document.getElementById('userGrowthChart');
        if (userGrowthCtx && analytics?.user_growth?.labels?.length) {
            new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: analytics.user_growth.labels,
                    datasets: [
                        {
                            label: 'Students',
                            data: analytics.user_growth.students,
                            borderColor: 'rgba(124, 58, 237, 0.95)',
                            backgroundColor: 'rgba(124, 58, 237, 0.10)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                        },
                        {
                            label: 'Teachers',
                            data: analytics.user_growth.teachers,
                            borderColor: 'rgba(96, 165, 250, 0.95)',
                            backgroundColor: 'rgba(96, 165, 250, 0.10)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle' }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { precision: 0 } },
                    }
                }
            });
        }
    </script>
@endsection
