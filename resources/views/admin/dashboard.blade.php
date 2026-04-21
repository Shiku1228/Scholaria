@extends('layouts.dashboard', [
    'title' => 'Admin Dashboard',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    @php
        $nowLabel = now()->format('l, F j, Y');
        $activeCourses = (int) data_get($systemOverview, 'active_courses', 0);
        $inactiveCourses = (int) data_get($systemOverview, 'inactive_courses', 0);
        $teachersWithoutCourses = (int) data_get($systemOverview, 'teachers_without_courses', 0);
        $studentsNotEnrolled = (int) data_get($systemOverview, 'students_not_enrolled', 0);
        $priorityCount = ($inactiveCourses > 0 ? 1 : 0) + ($teachersWithoutCourses > 0 ? 1 : 0) + ($studentsNotEnrolled > 0 ? 1 : 0);
        $healthSeverityScore = ($inactiveCourses * 2) + ($teachersWithoutCourses * 2) + $studentsNotEnrolled;
        $healthStatusLabel = 'Operational';
        $healthStatusClasses = 'border-emerald-400/40 bg-emerald-500/20 text-emerald-300';
        if ($healthSeverityScore >= 10) {
            $healthStatusLabel = 'Critical';
            $healthStatusClasses = 'border-rose-400/40 bg-rose-500/20 text-rose-200';
        } elseif ($healthSeverityScore > 0) {
            $healthStatusLabel = 'Needs Attention';
            $healthStatusClasses = 'border-amber-400/40 bg-amber-500/20 text-amber-200';
        }
        $overviewHasData = array_sum((array) data_get($overview, 'series.courses', [])) > 0
            || array_sum((array) data_get($overview, 'series.enrollments', [])) > 0
            || array_sum((array) data_get($overview, 'series.users', [])) > 0;
        $enrollments7dHasData = array_sum((array) data_get($analytics, 'enrollments_last_7_days.data', [])) > 0;
        $coursesPerMonthHasData = array_sum((array) data_get($analytics, 'courses_per_month.data', [])) > 0;
        $studentsPerCourseHasData = count((array) data_get($analytics, 'students_per_course.labels', [])) > 0
            && array_sum((array) data_get($analytics, 'students_per_course.data', [])) > 0;
        $userGrowthHasData = array_sum((array) data_get($analytics, 'user_growth.students', [])) > 0
            || array_sum((array) data_get($analytics, 'user_growth.teachers', [])) > 0;
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
                <div>
                    <h1 class="text-5xl font-bold tracking-tight text-slate-900">Admin Dashboard</h1>
                    <p class="mt-2 text-2xl text-slate-600">Monitor operations, users, and academic system activity</p>
                    <p class="mt-3 text-sm text-slate-500">{{ $nowLabel }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-blue-500 to-blue-600">
                    <div class="text-base text-blue-100">Total Courses</div>
                    <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($stats['total_courses'] ?? 0)) }}</div>
                    <div class="mt-2 text-sm text-blue-100">All course records</div>
                </div>

                <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-indigo-500 to-violet-600">
                    <div class="text-base text-indigo-100">Total Students</div>
                    <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($stats['total_students'] ?? 0)) }}</div>
                    <div class="mt-2 text-sm text-indigo-100">Registered students</div>
                </div>

                <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-emerald-500 to-teal-600">
                    <div class="text-base text-emerald-100">Total Teachers</div>
                    <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($stats['total_teachers'] ?? 0)) }}</div>
                    <div class="mt-2 text-sm text-emerald-100">Active instructors</div>
                </div>

                <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-amber-500 to-orange-500">
                    <div class="text-base text-amber-100">Total Enrollments</div>
                    <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($stats['total_enrollments'] ?? 0)) }}</div>
                    <div class="mt-2 text-sm text-amber-100">Enrollment transactions</div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="{{ route('admin.courses.create') }}" class="inline-flex w-full items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Create Course</a>
                <a href="{{ route('admin.users.create', ['role' => 'Teacher']) }}" class="inline-flex w-full items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Add Teacher</a>
                <a href="{{ route('admin.users.create', ['role' => 'Student']) }}" class="inline-flex w-full items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Add Student</a>
                <a href="{{ route('admin.enrollments.create') }}" class="inline-flex w-full items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Create Enrollment</a>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 12h4l3-9 4 18 3-9h4"/>
                            </svg>
                        </span>
                        <div>
                            <div class="text-xl font-bold text-slate-900">Recent Activity</div>
                            <div class="text-sm text-slate-500">Latest platform events and actions</div>
                        </div>
                    </div>
                    @if (!empty($recentActivity))
                        <a href="{{ route('admin.dashboard') }}#recent-activity" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">View All</a>
                    @endif
                </div>
                <div id="recent-activity" class="p-4 sm:p-5">
                    @if (empty($recentActivity))
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">
                            No recent activity yet. Once users enroll, submit, and publish content, events will appear here.
                        </div>
                    @else
                        <div class="space-y-3 max-h-[360px] overflow-auto pr-1">
                            @php
                                $activityIconClasses = [
                                    'course' => 'bg-indigo-50 text-indigo-600',
                                    'enroll' => 'bg-emerald-50 text-emerald-600',
                                    'assignment' => 'bg-blue-50 text-blue-600',
                                    'submission' => 'bg-blue-50 text-blue-600',
                                    'system' => 'bg-violet-50 text-violet-600',
                                    'settings' => 'bg-violet-50 text-violet-600',
                                    'achievement' => 'bg-amber-50 text-amber-600',
                                ];
                            @endphp
                            @foreach ($recentActivity as $item)
                                @php
                                    $message = (string) ($item['message'] ?? '');
                                    $messageLower = strtolower($message);
                                    $iconType = 'course';
                                    if (str_contains($messageLower, 'enroll')) {
                                        $iconType = 'enroll';
                                    } elseif (str_contains($messageLower, 'assignment') || str_contains($messageLower, 'submit')) {
                                        $iconType = 'assignment';
                                    } elseif (str_contains($messageLower, 'setting') || str_contains($messageLower, 'system')) {
                                        $iconType = 'system';
                                    } elseif (str_contains($messageLower, 'achiev') || str_contains($messageLower, 'certif')) {
                                        $iconType = 'achievement';
                                    }
                                    $iconClass = $activityIconClasses[$iconType] ?? $activityIconClasses['course'];
                                @endphp
                                <div class="flex items-start gap-3 rounded-xl p-2.5 hover:bg-slate-50 transition-colors">
                                    <span class="mt-0.5 inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $iconClass }}">
                                        @if ($iconType === 'enroll')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                <circle cx="8.5" cy="7" r="4"/>
                                                <path d="M20 8v6M23 11h-6"/>
                                            </svg>
                                        @elseif ($iconType === 'assignment')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <path d="M14 2v6h6"/>
                                                <path d="M16 13H8M16 17H8M10 9H8"/>
                                            </svg>
                                        @elseif ($iconType === 'system')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        @elseif ($iconType === 'achievement')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="8" r="6"/>
                                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20"/>
                                                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                            </svg>
                                        @endif
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-base text-slate-800 leading-snug">{{ $message }}</p>
                                        <p class="mt-1 text-sm text-slate-500 inline-flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <polyline points="12 6 12 12 16 14"/>
                                            </svg>
                                            {{ !empty($item['happened_at']) ? \Illuminate\Support\Carbon::parse($item['happened_at'])->diffForHumans() : 'just now' }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="xl:col-span-1 h-full rounded-2xl border border-slate-800 bg-gradient-to-br from-[#0a1638] to-[#131b46] text-white p-5 shadow-sm flex flex-col">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-white/10 text-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="3" width="20" height="8" rx="2"/>
                                <rect x="2" y="13" width="20" height="8" rx="2"/>
                            </svg>
                        </span>
                        <div class="text-xl font-bold tracking-tight">System Health</div>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $healthStatusClasses }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                        {{ $healthStatusLabel }}
                    </span>
                </div>

                <div class="mt-auto pt-8 grid grid-cols-2 gap-x-5 gap-y-5">
                    <div>
                        <div class="text-sm text-slate-300">Active Courses</div>
                        <div class="mt-1 text-2xl font-extrabold">{{ number_format($activeCourses) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-slate-300">Inactive Courses</div>
                        <div class="mt-1 text-2xl font-extrabold">{{ number_format($inactiveCourses) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-slate-300">Teachers Without Courses</div>
                        <div class="mt-1 text-2xl font-extrabold">{{ number_format($teachersWithoutCourses) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-slate-300">Students Not Enrolled</div>
                        <div class="mt-1 text-2xl font-extrabold {{ $studentsNotEnrolled > 0 ? 'text-amber-300' : 'text-white' }}">{{ number_format($studentsNotEnrolled) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-900">Platform Overview</div>
                        <div class="text-xs text-slate-500">Courses, enrollments, and users over time</div>
                    </div>

                    <form id="overviewFilterForm" method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                        <select name="range" id="rangeSelect" class="h-10 rounded-lg border-slate-300 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="7d" {{ $filters['range'] === '7d' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30d" {{ $filters['range'] === '30d' ? 'selected' : '' }}>Last 30 days</option>
                            <option value="12m" {{ $filters['range'] === '12m' ? 'selected' : '' }}>Last 12 months</option>
                        </select>
                    </form>
                </div>
                <div class="p-4 sm:p-5">
                    @if ($overviewHasData)
                        <div class="h-[320px]">
                            <canvas id="overviewChart"></canvas>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No overview trend data yet. Activity will appear as users, courses, and enrollments grow.
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-900">Recent Enrollments</div>
                    <div class="text-xs text-slate-500">Most recent enrollment records</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($recentEnrollments))
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">No enrollments yet. New records will appear here.</div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-slate-500 uppercase tracking-wide">
                                        <th class="py-2 pr-3">Student</th>
                                        <th class="py-2 pr-3">Course</th>
                                        <th class="py-2">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($recentEnrollments as $row)
                                        <tr class="text-slate-700">
                                            <td class="py-3 pr-3 font-medium text-slate-900">{{ (string) ($row['student_name'] ?? '--') }}</td>
                                            <td class="py-3 pr-3">{{ (string) ($row['course_name'] ?? '--') }}</td>
                                            <td class="py-3 text-slate-500">{{ !empty($row['enrolled_at']) ? \Illuminate\Support\Carbon::parse($row['enrolled_at'])->format('Y-m-d') : '--' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-900">Top Performing Courses</div>
                    <div class="text-xs text-slate-500">Highest enrollments</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($bestSellingCourses))
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">No course enrollment data yet.</div>
                    @else
                        <div class="space-y-3">
                            @foreach (array_slice($bestSellingCourses, 0, 5) as $index => $course)
                                <div class="flex items-center justify-between rounded-xl border border-slate-200 p-3">
                                    <div>
                                        <div class="text-xs text-slate-500">#{{ $index + 1 }}</div>
                                        <div class="text-sm font-semibold text-slate-900">{{ (string) data_get($course, 'course_name', 'Course') }}</div>
                                        <div class="text-xs text-slate-500">{{ (string) data_get($course, 'teacher_name', 'N/A') }}</div>
                                    </div>
                                    <span class="inline-flex items-center h-7 px-2 rounded-lg bg-[#eaf0fb] text-[#0b2d6b] text-xs font-semibold">{{ number_format((int) data_get($course, 'sales', 0)) }} students</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-900">Enrollments (Last 7 Days)</div>
                    <div class="text-xs text-slate-500">Daily enrollment volume</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if ($enrollments7dHasData)
                        <div class="h-[240px]">
                            <canvas id="enrollments7dChart"></canvas>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No enrollments recorded in the last 7 days.
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-900">Courses Created (Per Month)</div>
                    <div class="text-xs text-slate-500">Last 12 months</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if ($coursesPerMonthHasData)
                        <div class="h-[240px]">
                            <canvas id="coursesPerMonthChart"></canvas>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No course creation activity captured for this period.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-900">Students per Course</div>
                    <div class="text-xs text-slate-500">Top 10 courses by enrollment</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if ($studentsPerCourseHasData)
                        <div class="h-[300px]">
                            <canvas id="studentsPerCourseChart"></canvas>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No enrollment distribution data yet.
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200">
                <div class="p-4 sm:p-5 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-900">User Growth</div>
                    <div class="text-xs text-slate-500">Students vs teachers (Last 30 days)</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if ($userGrowthHasData)
                        <div class="h-[300px]">
                            <canvas id="userGrowthChart"></canvas>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">
                            No user growth events in the selected timeframe.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const overview = @json($overview);
        const analytics = @json($analytics);

        const overviewCtx = document.getElementById('overviewChart');
        const gridColor = 'rgba(148, 163, 184, 0.22)';
        const colorPrimary = 'rgba(11, 45, 107, 0.9)';
        const colorSecondary = 'rgba(37, 99, 235, 0.8)';
        const colorTertiary = 'rgba(56, 189, 248, 0.8)';

        if (overviewCtx) {
            new Chart(overviewCtx, {
                type: 'bar',
                data: {
                    labels: overview.labels,
                    datasets: [
                        {
                            label: 'Courses',
                            data: overview.series.courses,
                            backgroundColor: colorPrimary,
                            borderRadius: 8,
                            maxBarThickness: 24,
                        },
                        {
                            label: 'Enrollments',
                            data: overview.series.enrollments,
                            backgroundColor: colorSecondary,
                            borderRadius: 8,
                            maxBarThickness: 24,
                        },
                        {
                            label: 'Users',
                            data: overview.series.users,
                            backgroundColor: colorTertiary,
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
        }

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
                        borderColor: colorPrimary,
                        backgroundColor: 'rgba(11, 45, 107, 0.1)',
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
                        backgroundColor: colorSecondary,
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
                        backgroundColor: colorTertiary,
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
                            borderColor: colorPrimary,
                            backgroundColor: 'rgba(11, 45, 107, 0.09)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 0,
                        },
                        {
                            label: 'Teachers',
                            data: analytics.user_growth.teachers,
                            borderColor: colorSecondary,
                            backgroundColor: 'rgba(37, 99, 235, 0.09)',
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
