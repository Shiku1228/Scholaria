<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $totalCourses = 0;
        $totalEnrollments = 0;
        $totalStudents = 0;
        $totalTeachers = 0;

        $myCourses = 0;
        $studentsInMyCourses = 0;
        $newEnrollments = 0;
        $courseList = [];
        $recentEnrollments = [];
        $recentSubmissions = [];
        $recentAnnouncements = [];
        $courseStats = [];
        $upcomingClasses = [];
        $avgEngagement = 0;
        $performanceTrends = [
            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
            'completion' => [0, 0, 0, 0, 0],
            'engagement' => [0, 0, 0, 0, 0],
            'avg_score' => [0, 0, 0, 0, 0],
        ];

        try {
            $totalCourses = $this->countTable('courses');
            $totalEnrollments = $this->countTable('enrollments');
            $totalStudents = $this->countTable('students');
            $totalTeachers = $this->countTable('teachers');

            if ($totalTeachers === 0) {
                $totalTeachers = (int) User::role('Teacher')->count();
            }

            if ($totalStudents === 0) {
                $totalStudents = (int) User::role('Student')->count();
                if ($totalStudents === 0) {
                    $totalStudents = $this->countTable('users');
                }
            }

            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $myCourses = (int) DB::table('courses')->where('teacher_id', $userId)->count();

                if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id')) {
                    $courseIds = DB::table('courses')->where('teacher_id', $userId)->pluck('id');

                    if ($courseIds->isNotEmpty()) {
                        if (Schema::hasColumn('enrollments', 'student_id')) {
                            $studentsInMyCourses = (int) DB::table('enrollments')
                                ->whereIn('course_id', $courseIds)
                                ->distinct('student_id')
                                ->count('student_id');
                        } else {
                            $studentsInMyCourses = (int) DB::table('enrollments')->whereIn('course_id', $courseIds)->count();
                        }

                        $start = now()->subDays(7);
                        if (Schema::hasColumn('enrollments', 'enrolled_at')) {
                            $newEnrollments = (int) DB::table('enrollments')
                                ->whereIn('course_id', $courseIds)
                                ->where('enrolled_at', '>=', $start)
                                ->count();
                        } elseif (Schema::hasColumn('enrollments', 'created_at')) {
                            $newEnrollments = (int) DB::table('enrollments')
                                ->whereIn('course_id', $courseIds)
                                ->where('created_at', '>=', $start)
                                ->count();
                        }

                        $nameCol = null;
                        foreach (['title', 'name', 'course_name'] as $c) {
                            if (Schema::hasColumn('courses', $c)) {
                                $nameCol = $c;
                                break;
                            }
                        }

                        if ($nameCol) {
                            $courseList = DB::table('courses')
                                ->where('teacher_id', $userId)
                                ->select(['id', $nameCol . ' as course_name'])
                                ->orderBy('id', 'desc')
                                ->limit(10)
                                ->get()
                                ->map(fn ($r) => ['id' => (int) $r->id, 'course_name' => (string) $r->course_name])
                                ->all();
                        }

                        $hasStudentId = Schema::hasColumn('enrollments', 'student_id');
                        $hasCourseId = Schema::hasColumn('enrollments', 'course_id');
                        $hasEnrolledAt = Schema::hasColumn('enrollments', 'enrolled_at');

                        $courseNameCol = null;
                        foreach (['title', 'name', 'course_name'] as $c) {
                            if (Schema::hasColumn('courses', $c)) {
                                $courseNameCol = $c;
                                break;
                            }
                        }

                        if ($hasStudentId && $hasCourseId && $courseNameCol && Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
                            $recentEnrollments = DB::table('enrollments')
                                ->join('users', 'users.id', '=', 'enrollments.student_id')
                                ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                                ->whereIn('enrollments.course_id', $courseIds)
                                ->select([
                                    'users.name as student_name',
                                    'courses.' . $courseNameCol . ' as course_name',
                                    $hasEnrolledAt ? 'enrollments.enrolled_at as enrolled_at' : 'enrollments.created_at as enrolled_at',
                                ])
                                ->orderByDesc($hasEnrolledAt ? 'enrollments.enrolled_at' : 'enrollments.created_at')
                                ->limit(10)
                                ->get()
                                ->map(fn ($r) => [
                                    'student_name' => (string) ($r->student_name ?? ''),
                                    'course_name' => (string) ($r->course_name ?? ''),
                                    'enrolled_at' => (string) ($r->enrolled_at ?? ''),
                                ])
                                ->all();
                        } elseif (Schema::hasColumn('enrollments', 'created_at')) {
                            $recentEnrollments = DB::table('enrollments')
                                ->whereIn('course_id', $courseIds)
                                ->orderByDesc('created_at')
                                ->limit(10)
                                ->get()
                                ->map(fn ($r) => [
                                    'student_name' => '',
                                    'course_name' => 'Course #' . (string) ($r->course_id ?? 0),
                                    'enrolled_at' => (string) ($r->created_at ?? ''),
                                ])
                                ->all();
                        }

                        if (Schema::hasTable('submissions') && Schema::hasTable('assignments') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                            $hasSubmittedAt = Schema::hasColumn('submissions', 'submitted_at');
                            $hasCourseIdOnAssignments = Schema::hasColumn('assignments', 'course_id');
                            $hasAssignmentTitle = Schema::hasColumn('assignments', 'title');

                            if ($hasCourseIdOnAssignments && $hasAssignmentTitle && Schema::hasTable('users') && Schema::hasColumn('users', 'name') && $courseNameCol) {
                                $recentSubmissions = DB::table('submissions')
                                    ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                    ->join('courses', 'courses.id', '=', 'assignments.course_id')
                                    ->join('users', 'users.id', '=', 'submissions.student_id')
                                    ->whereIn('assignments.course_id', $courseIds)
                                    ->select([
                                        'users.name as student_name',
                                        'assignments.title as assignment_title',
                                        'courses.' . $courseNameCol . ' as course_name',
                                        $hasSubmittedAt ? 'submissions.submitted_at as submitted_at' : 'submissions.created_at as submitted_at',
                                    ])
                                    ->orderByDesc($hasSubmittedAt ? 'submissions.submitted_at' : 'submissions.created_at')
                                    ->limit(10)
                                    ->get()
                                    ->map(fn ($r) => [
                                        'student_name' => (string) ($r->student_name ?? ''),
                                        'assignment_title' => (string) ($r->assignment_title ?? ''),
                                        'course_name' => (string) ($r->course_name ?? ''),
                                        'submitted_at' => (string) ($r->submitted_at ?? ''),
                                    ])
                                    ->all();
                            }
                        }

                        if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'course_id') && Schema::hasColumn('announcements', 'title') && Schema::hasColumn('announcements', 'message') && $courseNameCol) {
                            $recentAnnouncements = DB::table('announcements')
                                ->join('courses', 'courses.id', '=', 'announcements.course_id')
                                ->whereIn('announcements.course_id', $courseIds)
                                ->select([
                                    'announcements.title as title',
                                    'announcements.message as message',
                                    'courses.' . $courseNameCol . ' as course_name',
                                    'announcements.created_at as created_at',
                                ])
                                ->orderByDesc('announcements.created_at')
                                ->limit(10)
                                ->get()
                                ->map(fn ($r) => [
                                    'title' => (string) ($r->title ?? ''),
                                    'message' => (string) ($r->message ?? ''),
                                    'course_name' => (string) ($r->course_name ?? ''),
                                    'created_at' => (string) ($r->created_at ?? ''),
                                ])
                                ->all();
                        }

                        if ($courseNameCol) {
                            $courses = DB::table('courses')
                                ->whereIn('id', $courseIds)
                                ->select(['id', $courseNameCol . ' as course_name'])
                                ->orderBy('id', 'desc')
                                ->get();

                            $enrolledStudentsByCourse = [];
                            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasColumn('enrollments', 'student_id')) {
                                $enrolledRows = DB::table('enrollments')
                                    ->whereIn('course_id', $courseIds)
                                    ->select(['course_id', DB::raw('COUNT(DISTINCT student_id) as enrolled_students')])
                                    ->groupBy('course_id')
                                    ->get();

                                foreach ($enrolledRows as $row) {
                                    $enrolledStudentsByCourse[(int) $row->course_id] = (int) ($row->enrolled_students ?? 0);
                                }
                            }

                            $assignmentCounts = [];
                            if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                                $assignmentRows = DB::table('assignments')
                                    ->whereIn('course_id', $courseIds)
                                    ->select(['course_id', DB::raw('COUNT(*) as assignment_count')])
                                    ->groupBy('course_id')
                                    ->get();

                                foreach ($assignmentRows as $row) {
                                    $assignmentCounts[(int) $row->course_id] = (int) ($row->assignment_count ?? 0);
                                }
                            }

                            $gradeAggByCourse = [];
                            if (
                                Schema::hasTable('submissions')
                                && Schema::hasTable('assignments')
                                && Schema::hasColumn('submissions', 'assignment_id')
                                && Schema::hasColumn('submissions', 'student_id')
                                && Schema::hasColumn('submissions', 'score')
                                && Schema::hasColumn('assignments', 'id')
                                && Schema::hasColumn('assignments', 'course_id')
                                && Schema::hasColumn('assignments', 'max_score')
                            ) {
                                $scoreRows = DB::table('submissions')
                                    ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                    ->whereIn('assignments.course_id', $courseIds)
                                    ->whereNotNull('submissions.score')
                                    ->where('assignments.max_score', '>', 0)
                                    ->select([
                                        'assignments.course_id as course_id',
                                        DB::raw('SUM((submissions.score / assignments.max_score) * 100) as pct_sum'),
                                        DB::raw('COUNT(*) as pct_count'),
                                        DB::raw('COUNT(DISTINCT submissions.student_id) as submitted_students'),
                                    ])
                                    ->groupBy('assignments.course_id')
                                    ->get();

                                foreach ($scoreRows as $row) {
                                    $gradeAggByCourse[(int) $row->course_id] = [
                                        'pct_sum' => (float) ($row->pct_sum ?? 0),
                                        'pct_count' => (int) ($row->pct_count ?? 0),
                                        'submitted_students' => (int) ($row->submitted_students ?? 0),
                                    ];
                                }
                            }

                            $courseStats = $courses->map(function ($course) use ($enrolledStudentsByCourse, $assignmentCounts, $gradeAggByCourse) {
                                $courseId = (int) ($course->id ?? 0);
                                $enrolledStudents = (int) ($enrolledStudentsByCourse[$courseId] ?? 0);
                                $assignmentCount = (int) ($assignmentCounts[$courseId] ?? 0);
                                $agg = $gradeAggByCourse[$courseId] ?? [
                                    'pct_sum' => 0.0,
                                    'pct_count' => 0,
                                    'submitted_students' => 0,
                                ];

                                $avgGrade = $agg['pct_count'] > 0
                                    ? (int) round($agg['pct_sum'] / max(1, $agg['pct_count']))
                                    : 0;

                                // Completion = students who submitted at least one scored work / enrolled students.
                                $completion = $enrolledStudents > 0
                                    ? (int) round((($agg['submitted_students'] ?? 0) / $enrolledStudents) * 100)
                                    : 0;

                                return [
                                    'name' => (string) ($course->course_name ?? ''),
                                    'students' => $enrolledStudents,
                                    'avg_grade' => max(0, min(100, $avgGrade)),
                                    'completion' => max(0, min(100, $completion)),
                                    'assignment_count' => $assignmentCount,
                                ];
                            })->all();
                        }

                        // Build accurate upcoming classes from course schedule + enrollment counts.
                        if ($courseNameCol && Schema::hasColumn('courses', 'days_pattern') && Schema::hasColumn('courses', 'start_time')) {
                            $courseRows = DB::table('courses')
                                ->whereIn('id', $courseIds)
                                ->select(['id', $courseNameCol . ' as course_name', 'days_pattern', 'start_time'])
                                ->get();

                            $dayMap = [
                                'Sun' => 0,
                                'Mon' => 1,
                                'Tue' => 2,
                                'Wed' => 3,
                                'Thu' => 4,
                                'Fri' => 5,
                                'Sat' => 6,
                            ];

                            $now = now();
                            $today = $now->copy()->startOfDay();
                            $tomorrow = $today->copy()->addDay();
                            $upcoming = [];

                            foreach ($courseRows as $courseRow) {
                                $cid = (int) ($courseRow->id ?? 0);
                                $courseName = (string) ($courseRow->course_name ?? '');
                                $daysRaw = (string) ($courseRow->days_pattern ?? '');
                                $startTime = (string) ($courseRow->start_time ?? '');

                                if ($courseName === '' || $daysRaw === '' || $startTime === '') {
                                    continue;
                                }

                                $enrolledStudents = (int) ($enrolledStudentsByCourse[$cid] ?? 0);
                                $days = array_values(array_filter(array_map('trim', explode(',', $daysRaw))));

                                $bestDt = null;
                                foreach ($days as $d) {
                                    if (!array_key_exists($d, $dayMap)) {
                                        continue;
                                    }

                                    $targetDow = $dayMap[$d];
                                    $candidate = $now->copy()->startOfDay();
                                    $delta = ($targetDow - (int) $candidate->dayOfWeek + 7) % 7;
                                    $candidate->addDays($delta);

                                    // apply course start time
                                    [$hh, $mm, $ss] = array_pad(explode(':', $startTime), 3, '00');
                                    $candidate->setTime((int) $hh, (int) $mm, (int) $ss);

                                    // if the class time today already passed, move to next week
                                    if ($candidate->lessThanOrEqualTo($now)) {
                                        $candidate->addWeek();
                                    }

                                    if ($bestDt === null || $candidate->lessThan($bestDt)) {
                                        $bestDt = $candidate->copy();
                                    }
                                }

                                if ($bestDt === null) {
                                    continue;
                                }

                                $dateLabel = $bestDt->copy()->startOfDay()->equalTo($today)
                                    ? 'Today'
                                    : ($bestDt->copy()->startOfDay()->equalTo($tomorrow) ? 'Tomorrow' : $bestDt->format('M j'));

                                $upcoming[] = [
                                    'course_name' => $courseName,
                                    'time' => $bestDt->format('g:i A'),
                                    'label' => $dateLabel,
                                    'students' => $enrolledStudents,
                                    'next_at' => $bestDt->toDateTimeString(),
                                ];
                            }

                            usort($upcoming, fn ($a, $b) => strcmp((string) ($a['next_at'] ?? ''), (string) ($b['next_at'] ?? '')));
                            $upcomingClasses = array_slice($upcoming, 0, 3);
                        }

                        // Build accurate trend metrics from real data for the last 5 weeks.
                        $weekStarts = [];
                        $baseWeek = now()->startOfWeek();
                        for ($i = 4; $i >= 0; $i--) {
                            $weekStarts[] = $baseWeek->copy()->subWeeks($i);
                        }

                        $completionSeries = [];
                        $engagementSeries = [];
                        $avgScoreSeries = [];

                        $submissionDateCol = Schema::hasColumn('submissions', 'submitted_at')
                            ? 'submitted_at'
                            : (Schema::hasColumn('submissions', 'created_at') ? 'created_at' : null);

                        $enrollmentDateCol = Schema::hasColumn('enrollments', 'enrolled_at')
                            ? 'enrolled_at'
                            : (Schema::hasColumn('enrollments', 'created_at') ? 'created_at' : null);

                        $enrolledTotal = max(0, (int) $studentsInMyCourses);

                        foreach ($weekStarts as $start) {
                            $end = $start->copy()->endOfWeek();

                            $submittedStudentIds = [];
                            $newEnrollmentStudentIds = [];
                            $avgScorePct = 0.0;

                            if (
                                $submissionDateCol
                                && Schema::hasTable('submissions')
                                && Schema::hasTable('assignments')
                                && Schema::hasColumn('submissions', 'assignment_id')
                                && Schema::hasColumn('submissions', 'student_id')
                                && Schema::hasColumn('assignments', 'id')
                                && Schema::hasColumn('assignments', 'course_id')
                            ) {
                                $submittedStudentIds = DB::table('submissions')
                                    ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                    ->whereIn('assignments.course_id', $courseIds)
                                    ->whereBetween('submissions.' . $submissionDateCol, [$start, $end])
                                    ->distinct()
                                    ->pluck('submissions.student_id')
                                    ->map(fn ($v) => (int) $v)
                                    ->all();

                                if (Schema::hasColumn('submissions', 'score') && Schema::hasColumn('assignments', 'max_score')) {
                                    $avgRow = DB::table('submissions')
                                        ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                        ->whereIn('assignments.course_id', $courseIds)
                                        ->whereBetween('submissions.' . $submissionDateCol, [$start, $end])
                                        ->whereNotNull('submissions.score')
                                        ->where('assignments.max_score', '>', 0)
                                        ->selectRaw('AVG((submissions.score / assignments.max_score) * 100) as avg_pct')
                                        ->first();

                                    $avgScorePct = (float) (($avgRow->avg_pct ?? 0) ?: 0);
                                }
                            }

                            if (
                                $enrollmentDateCol
                                && Schema::hasTable('enrollments')
                                && Schema::hasColumn('enrollments', 'course_id')
                                && Schema::hasColumn('enrollments', 'student_id')
                            ) {
                                $newEnrollmentStudentIds = DB::table('enrollments')
                                    ->whereIn('course_id', $courseIds)
                                    ->whereBetween($enrollmentDateCol, [$start, $end])
                                    ->distinct()
                                    ->pluck('student_id')
                                    ->map(fn ($v) => (int) $v)
                                    ->all();
                            }

                            $submittedCount = count(array_unique($submittedStudentIds));
                            $activeStudentCount = count(array_unique(array_merge($submittedStudentIds, $newEnrollmentStudentIds)));

                            $completionPct = $enrolledTotal > 0 ? (int) round(($submittedCount / $enrolledTotal) * 100) : 0;
                            $engagementPct = $enrolledTotal > 0 ? (int) round(($activeStudentCount / $enrolledTotal) * 100) : 0;

                            $completionSeries[] = max(0, min(100, $completionPct));
                            $engagementSeries[] = max(0, min(100, $engagementPct));
                            $avgScoreSeries[] = max(0, min(100, (int) round($avgScorePct)));
                        }

                        $performanceTrends = [
                            'labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'],
                            'completion' => $completionSeries,
                            'engagement' => $engagementSeries,
                            'avg_score' => $avgScoreSeries,
                        ];

                        $avgEngagement = !empty($engagementSeries)
                            ? (int) round(array_sum($engagementSeries) / count($engagementSeries))
                            : 0;
                    }
                }
            }
        } catch (\Throwable) {
        }

        return view('teacher.dashboard', [
            'stats' => [
                'total_courses' => $totalCourses,
                'total_enrollments' => $totalEnrollments,
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'my_courses' => $myCourses,
                'students_in_my_courses' => $studentsInMyCourses,
                'new_enrollments' => $newEnrollments,
            ],
            'courseList' => $courseList,
            'recentEnrollments' => $recentEnrollments,
            'recentSubmissions' => $recentSubmissions,
            'recentAnnouncements' => $recentAnnouncements,
            'courseStats' => $courseStats,
            'upcomingClasses' => $upcomingClasses,
            'avgEngagement' => $avgEngagement,
            'performanceTrends' => $performanceTrends,
        ]);
    }

    private function countTable(string $table): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }

            return (int) DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
