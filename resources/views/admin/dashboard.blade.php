@extends('layouts.admin')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 h-auto md:h-[calc(100vh-6rem)]">
        <!-- Left Side - Blue Dashboard Section -->
        <div class="col-span-1 md:col-span-4 h-auto md:h-full">
            <div class="h-auto md:h-full rounded-[28px] p-6 shadow-sm border border-gray-100 flex flex-col" style="background: linear-gradient(160deg, var(--slms-panel-grad-a, #0b2d6b) 0%, var(--slms-panel-grad-b, #0a3a8a) 45%, var(--slms-panel-grad-c, #4f46e5) 100%);">
                <div class="text-white text-3xl font-semibold mt-1">WELCOME {{ strtoupper(auth()->user()->name) }} TO YOUR DASHBOARD</div>
                <div class="mt-6 flex flex-col gap-4 flex-1 justify-center">
                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="book-open" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['total_courses']) }}</div>
                        <div class="text-white/70 text-xs mt-1">Total Courses</div>
                    </div>

                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="clipboard-check" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['total_enrollments']) }}</div>
                        <div class="text-white/70 text-xs mt-1">Total Enrollments</div>
                    </div>

                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="users" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['total_students']) }}</div>
                        <div class="text-white/70 text-xs mt-1">Total Students</div>
                    </div>

                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="user" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['total_teachers']) }}</div>
                        <div class="text-white/70 text-xs mt-1">Total Teachers</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Main Content Area -->
        <div class="col-span-1 md:col-span-8 overflow-visible md:overflow-y-auto">
            <div class="space-y-6">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold">Overview</div>
                                <div class="text-xs text-gray-500">Courses / Enroll / User</div>
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

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold">Best Selling Course</div>
                                <div class="text-xs text-gray-500">Top course by enrollments</div>
                            </div>
                        </div>

                        <div class="p-4 sm:p-5">
                            @php
                                $courseName = (string) data_get($bestSellingCourse ?? [], 'course_name', '');
                                $teacherName = (string) data_get($bestSellingCourse ?? [], 'teacher_name', '');
                                $sales = (int) data_get($bestSellingCourse ?? [], 'sales', 0);
                            @endphp

                            @if ($courseName === '')
                                <div class="text-sm text-gray-500">No data</div>
                            @else
                                <div class="flex items-start gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-violet-50 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-7 w-7 text-violet-700">
                                            <path d="M11.7 2.805a.75.75 0 0 1 .6 0A58.25 58.25 0 0 1 21.75 7.5a.75.75 0 0 1 .45.69v.75a.75.75 0 0 1-.45.69A58.25 58.25 0 0 1 12.3 14.445a.75.75 0 0 1-.6 0A58.25 58.25 0 0 1 2.25 9.63a.75.75 0 0 1-.45-.69v-.75a.75.75 0 0 1 .45-.69A58.25 58.25 0 0 1 11.7 2.805Z" />
                                            <path d="M3 10.89v6.03a.75.75 0 0 0 .375.65 58.3 58.3 0 0 0 8.25 3.935.75.75 0 0 0 .75 0 58.3 58.3 0 0 0 8.25-3.935.75.75 0 0 0 .375-.65v-6.03a59.48 59.48 0 0 1-8.7 4.44.75.75 0 0 1-.6 0A59.48 59.48 0 0 1 3 10.89Z" />
                                        </svg>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $courseName }}</div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $teacherName !== '' ? $teacherName : 'Instructor: N/A' }}
                                        </div>
                                        <div class="mt-3 inline-flex items-center h-7 px-2 rounded-lg bg-violet-50 text-violet-700 text-xs font-semibold">
                                            {{ number_format($sales) }} sales
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                        <div class="p-4 sm:p-5 border-b border-gray-100">
                            <div class="text-sm font-semibold">Recent Enrollments</div>
                            <div class="text-xs text-gray-500">Latest activity</div>
                        </div>
                        <div class="p-4 sm:p-5">
                            @if (empty($recentEnrollments))
                                <div class="text-sm text-gray-500">No data</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-xs text-gray-500">
                                            <th class="py-2 pr-3">Student</th>
                                            <th class="py-2 pr-3">Course</th>
                                            <th class="py-2">Enrolled At</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                        @foreach ($recentEnrollments as $row)
                                            <tr class="text-gray-700">
                                                <td class="py-3 pr-3 font-medium text-gray-900">{{ $row['student_name'] }}</td>
                                                <td class="py-3 pr-3">{{ $row['course_name'] }}</td>
                                                <td class="py-3 text-gray-500">{{ $row['enrolled_at'] }}</td>
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
                            <div class="text-sm font-semibold">Best Selling Courses</div>
                            <div class="text-xs text-gray-500">Top 10</div>
                        </div>
                        <div class="p-4 sm:p-5">
                            @if (empty($bestSellingCourses))
                                <div class="text-sm text-gray-500">No data</div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-xs text-gray-500">
                                            <th class="py-2 pr-3">Course Name</th>
                                            <th class="py-2 pr-3">Instructor</th>
                                            <th class="py-2 text-right">Sales</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                        @foreach ($bestSellingCourses as $row)
                                            <tr class="text-gray-700">
                                                <td class="py-3 pr-3 font-medium text-gray-900">{{ $row['course_name'] }}</td>
                                                <td class="py-3 pr-3 text-gray-700">{{ $row['teacher_name'] !== '' ? $row['teacher_name'] : 'N/A' }}</td>
                                                <td class="py-3 text-right">{{ number_format($row['sales']) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const overview = @json($overview);

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
    </script>
@endsection
