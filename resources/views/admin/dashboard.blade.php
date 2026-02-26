@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-500">Total Courses</div>
                        <div class="text-2xl font-semibold mt-1">{{ number_format($stats['total_courses']) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-violet-700">
                            <path d="M11.7 2.805a.75.75 0 0 1 .6 0A58.25 58.25 0 0 1 21.75 7.5a.75.75 0 0 1 .45.69v.75a.75.75 0 0 1-.45.69A58.25 58.25 0 0 1 12.3 14.445a.75.75 0 0 1-.6 0A58.25 58.25 0 0 1 2.25 9.63a.75.75 0 0 1-.45-.69v-.75a.75.75 0 0 1 .45-.69A58.25 58.25 0 0 1 11.7 2.805Z" />
                            <path d="M3 10.89v6.03a.75.75 0 0 0 .375.65 58.3 58.3 0 0 0 8.25 3.935.75.75 0 0 0 .75 0 58.3 58.3 0 0 0 8.25-3.935.75.75 0 0 0 .375-.65v-6.03a59.48 59.48 0 0 1-8.7 4.44.75.75 0 0 1-.6 0A59.48 59.48 0 0 1 3 10.89Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-500">Total Enrollments</div>
                        <div class="text-2xl font-semibold mt-1">{{ number_format($stats['total_enrollments']) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-violet-700">
                            <path fill-rule="evenodd" d="M8.25 6.75A.75.75 0 0 1 9 6h6a.75.75 0 0 1 .75.75v10.5A.75.75 0 0 1 15 18H9a.75.75 0 0 1-.75-.75V6.75ZM9.75 7.5v9h4.5v-9h-4.5Z" clip-rule="evenodd" />
                            <path d="M6.75 8.25A.75.75 0 0 0 6 9v8.25A2.25 2.25 0 0 0 8.25 19.5h.75a.75.75 0 0 0 0-1.5h-.75a.75.75 0 0 1-.75-.75V9a.75.75 0 0 0-.75-.75Z" />
                            <path d="M17.25 8.25A.75.75 0 0 1 18 9v8.25a2.25 2.25 0 0 1-2.25 2.25H15a.75.75 0 0 1 0-1.5h.75a.75.75 0 0 0 .75-.75V9a.75.75 0 0 1 .75-.75Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-500">Total Students</div>
                        <div class="text-2xl font-semibold mt-1">{{ number_format($stats['total_students']) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-violet-700">
                            <path d="M15 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path fill-rule="evenodd" d="M4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs text-gray-500">Total Teachers</div>
                        <div class="text-2xl font-semibold mt-1">{{ number_format($stats['total_teachers']) }}</div>
                    </div>
                    <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-violet-700">
                            <path d="M12 12a3.75 3.75 0 1 0 0-7.5A3.75 3.75 0 0 0 12 12Z" />
                            <path fill-rule="evenodd" d="M1.5 20.118a10.5 10.5 0 0 1 21 0A18.958 18.958 0 0 1 12 21.75c-3.375 0-6.526-.884-9.375-2.432Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

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
