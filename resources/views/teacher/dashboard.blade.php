@extends('layouts.dashboard', [
    'title' => 'Teacher Dashboard',
    'sidebarPartial' => 'partials.sidebars.teacher',
])

@section('content')
    @php
        $totalStudents = (int) ($stats['students_in_my_courses'] ?? 0);
        $activeCourses = (int) ($stats['my_courses'] ?? 0);
        $pendingReviews = collect($recentSubmissions ?? [])->filter(fn ($r) => empty($r['score']) && empty($r['graded_at']))->count();
        $avgEngagement = (int) ($avgEngagement ?? 0);

        $courseNames = collect($courseList ?? [])->pluck('course_name')->filter(fn ($x) => (string) $x !== '')->values();
        $schedule = collect($upcomingClasses ?? [])->values();

        $courseStats = collect($courseStats ?? [])->values();

        $submissionRows = collect($recentSubmissions ?? [])->take(6)->values();

        $trendLabels = (array) data_get($performanceTrends ?? [], 'labels', ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5']);
        $trendCompletion = (array) data_get($performanceTrends ?? [], 'completion', [0, 0, 0, 0, 0]);
        $trendEngagement = (array) data_get($performanceTrends ?? [], 'engagement', [0, 0, 0, 0, 0]);
        $trendScore = (array) data_get($performanceTrends ?? [], 'avg_score', [0, 0, 0, 0, 0]);
    @endphp

    <div class="space-y-4 sm:space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-slate-900">Instructor Dashboard</h1>
                    <p class="mt-2 text-lg sm:text-xl lg:text-2xl text-slate-600">Manage your courses, students, and grading</p>
                </div>
            </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-3xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-blue-500 to-blue-600">
                <div class="text-sm sm:text-base text-blue-100">Total Students</div>
                <div class="mt-2 text-3xl sm:text-5xl font-extrabold">{{ number_format($totalStudents) }}</div>
                <div class="mt-2 text-xs sm:text-sm text-blue-100">Across all courses</div>
            </div>

            <div class="rounded-3xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-indigo-500 to-violet-600">
                <div class="text-sm sm:text-base text-indigo-100">Active Courses</div>
                <div class="mt-2 text-3xl sm:text-5xl font-extrabold">{{ number_format($activeCourses) }}</div>
                <div class="mt-2 text-xs sm:text-sm text-indigo-100">Currently assigned</div>
            </div>

            <div class="rounded-3xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-emerald-500 to-teal-600">
                <div class="text-sm sm:text-base text-emerald-100">Avg. Engagement</div>
                <div class="mt-2 text-3xl sm:text-5xl font-extrabold">{{ $avgEngagement }}%</div>
                <div class="mt-2 text-xs sm:text-sm text-emerald-100">From activity trends</div>
            </div>

            <div class="rounded-3xl p-4 sm:p-6 text-white shadow-lg bg-gradient-to-br from-amber-500 to-orange-500">
                <div class="text-sm sm:text-base text-amber-100">Pending Reviews</div>
                <div class="mt-2 text-3xl sm:text-5xl font-extrabold">{{ number_format($pendingReviews) }}</div>
                <div class="mt-2 text-xs sm:text-sm text-amber-100">Submissions to grade</div>
            </div>
        </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">View My Courses</a>
                <a href="{{ route('teacher.students.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">View Students</a>
                <a href="{{ route('teacher.announcements') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Create Announcement</a>
                <a href="{{ route('teacher.assignments.overview') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Create Assignment</a>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <section class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Student Performance Trends</h2>
                <p class="mt-1 text-lg sm:text-xl lg:text-2xl text-slate-500">Weekly metrics across all courses</p>
                <div class="mt-4 h-[300px] sm:h-[360px]">
                    <canvas id="teacherTrendsChart"></canvas>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Upcoming Classes</h2>
                <p class="mt-1 text-lg sm:text-xl lg:text-2xl text-slate-500">Your schedule</p>

                <div class="mt-5 space-y-3">
                    @forelse ($schedule as $row)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-semibold text-slate-900 truncate flex-1">{{ (string) ($row['course_name'] ?? '--') }}</div>
                                <span class="inline-flex h-7 items-center rounded-lg px-2 text-xs font-semibold bg-[#eaf0fb] text-[#0b2d6b] flex-shrink-0">{{ (string) ($row['label'] ?? '') }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm text-slate-600">
                                <span class="truncate">{{ (string) ($row['time'] ?? '--') }}</span>
                                <span class="flex-shrink-0">{{ (int) ($row['students'] ?? 0) }} students</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">No upcoming classes yet.</div>
                    @endforelse
                </div>

                <div class="mt-5 text-center">
                    <a href="{{ route('teacher.calendar') }}" class="text-[#0b2d6b] font-semibold hover:underline">View Full Calendar</a>
                 </div>
            </section>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm p-4 sm:p-6">
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Course Statistics</h2>
            <p class="mt-1 text-lg sm:text-xl lg:text-2xl text-slate-500">Performance overview by course</p>

            <div class="mt-5 overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                <table class="min-w-full text-sm sm:text-base">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                            <th class="py-3 font-semibold">Course Name</th>
                            <th class="py-3 font-semibold">Students</th>
                            <th class="py-3 font-semibold hidden sm:table-cell">Avg Grade</th>
                            <th class="py-3 font-semibold hidden lg:table-cell">Completion Rate</th>
                            <th class="py-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($courseStats as $row)
                            <tr>
                                <td class="py-4">
                                    <div class="font-semibold text-slate-900 truncate max-w-[120px] sm:max-w-none">{{ $row['name'] }}</div>
                                    <div class="sm:hidden text-xs text-slate-500 mt-1">Grade: {{ $row['avg_grade'] }}%</div>
                                    <div class="lg:hidden text-xs text-slate-500 mt-1">Completion: {{ $row['completion'] }}%</div>
                                </td>
                                <td class="py-4 text-slate-700">{{ $row['students'] }}</td>
                                <td class="py-4 font-semibold text-slate-900 hidden sm:table-cell">{{ $row['avg_grade'] }}%</td>
                                <td class="py-4 hidden lg:table-cell">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2 w-32 rounded-full bg-slate-200 overflow-hidden">
                                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $row['completion'] }}%"></div>
                                        </div>
                                        <span class="text-sm text-slate-600">{{ $row['completion'] }}%</span>
                                    </div>
                                </td>
                                <td class="py-4 text-right">
                                    <a href="{{ route('teacher.courses.index') }}" class="text-[#0b2d6b] font-semibold hover:underline text-sm sm:text-base">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-slate-500">No course data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="p-4 sm:p-6 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900">Recent Submissions</h2>
                    <p class="mt-1 text-lg sm:text-xl lg:text-2xl text-slate-500">Needs your attention for grading.</p>
                </div>
                <a href="{{ route('teacher.assignments.overview') }}" class="text-[#0b2d6b] font-semibold hover:underline whitespace-nowrap">View All</a>
            </div>

            <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
                <table class="min-w-full text-sm sm:text-base">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-500 border-b border-slate-200">
                            <th class="py-4 px-6 font-semibold">Student Name</th>
                            <th class="py-4 px-6 font-semibold hidden sm:table-cell">Assignment</th>
                            <th class="py-4 px-6 font-semibold hidden md:table-cell">Date Submitted</th>
                            <th class="py-4 px-6 font-semibold">Status</th>
                            <th class="py-4 px-6 text-right font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($submissionRows as $row)
                            @php
                                $student      = (string) ($row['student_name'] ?? '--');
                                $initial      = strtoupper(substr($student, 0, 1));
                                $isGraded     = !empty($row['score']) || !empty($row['graded_at']);
                                $needsGrading = !$isGraded;
                                $courseId     = (int) ($row['course_id'] ?? 0);
                                $assignmentId = (int) ($row['assignment_id'] ?? 0);
                                $submissionId = (int) ($row['submission_id'] ?? 0);
                                $canLink      = $courseId > 0 && $assignmentId > 0 && $submissionId > 0;
                                $actionUrl    = $canLink
                                    ? route('teacher.submissions.show', [$courseId, $assignmentId, $submissionId])
                                    : route('teacher.assignments.overview');
                            @endphp
                            <tr>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-slate-200 text-slate-700 text-sm font-semibold flex items-center justify-center flex-shrink-0">{{ $initial !== '' ? $initial : 'S' }}</div>
                                        <div class="font-semibold text-slate-900 truncate max-w-[100px] sm:max-w-none">{{ $student }}</div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-slate-800 hidden sm:table-cell">
                                    <div class="truncate max-w-[120px] sm:max-w-none">{{ (string) ($row['assignment_title'] ?? '--') }}</div>
                                </td>
                                <td class="py-4 px-6 text-slate-600 hidden md:table-cell">{{ !empty($row['submitted_at']) ? \Illuminate\Support\Carbon::parse($row['submitted_at'])->format('Y-m-d') : '--' }}</td>
                                <td class="py-4 px-6">
                                    @if ($needsGrading)
                                        <span class="inline-flex h-7 items-center rounded-full px-3 text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Needs Grading</span>
                                    @else
                                        <span class="inline-flex h-7 items-center rounded-full px-3 text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Graded</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    @if ($needsGrading)
                                        <a href="{{ $actionUrl }}" class="inline-flex h-9 items-center rounded-xl bg-[#eaf0fb] px-3 text-sm font-semibold text-[#0b2d6b] hover:bg-[#dce7fb]">Grade Now</a>
                                    @else
                                        <a href="{{ $actionUrl }}" class="text-slate-600 font-semibold hover:text-[#0b2d6b] text-sm sm:text-base">Review</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 px-6 text-center text-slate-500">No recent submissions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const trendLabels = @json($trendLabels);
        const trendCompletion = @json($trendCompletion);
        const trendEngagement = @json($trendEngagement);
        const trendScore = @json($trendScore);

        const ctx = document.getElementById('teacherTrendsChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Completion %',
                            data: trendCompletion,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79,70,229,0.08)',
                            tension: 0.35,
                            pointRadius: 4,
                            fill: false,
                        },
                        {
                            label: 'Engagement %',
                            data: trendEngagement,
                            borderColor: '#7c3aed',
                            backgroundColor: 'rgba(124,58,237,0.08)',
                            tension: 0.35,
                            pointRadius: 4,
                            fill: false,
                        },
                        {
                            label: 'Avg Score %',
                            data: trendScore,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.08)',
                            tension: 0.35,
                            pointRadius: 4,
                            fill: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8 }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: 'rgba(148,163,184,0.25)' },
                            ticks: { callback: (v) => v }
                        }
                    }
                }
            });
        }
    </script>
@endsection
