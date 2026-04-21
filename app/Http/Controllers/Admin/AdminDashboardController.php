<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $rangeAllowed = ['7d', '30d', '12m'];

        $range = $request->query('range', '7d');
        if (!in_array($range, $rangeAllowed, true)) {
            $range = '7d';
        }

        $rangeDays = match ($range) {
            '7d' => 7,
            '30d' => 30,
            '12m' => 365,
            default => 7,
        };

        $stats = $this->buildStats($rangeDays);
        $overview = $this->buildOverviewSeries($range);
        $bestSellingCourse = $this->buildBestSellingCourse();
        $bestSellingCourses = $this->buildBestSellingCourses(10);
        $recentEnrollments = $this->buildRecentEnrollments();
        $systemOverview = $this->buildSystemOverview();
        $recentActivity = $this->buildRecentActivity();
        $analytics = $this->buildAnalyticsSeries();

        return view('admin.dashboard', [
            'filters' => [
                'range' => $range,
            ],
            'stats' => $stats,
            'overview' => $overview,
            'bestSellingCourse' => $bestSellingCourse,
            'bestSellingCourses' => $bestSellingCourses,
            'recentEnrollments' => $recentEnrollments,
            'systemOverview' => $systemOverview,
            'recentActivity' => $recentActivity,
            'analytics' => $analytics,
        ]);
    }

    private function buildStats(int $rangeDays): array
    {
        $coursesCount = $this->countTable('courses');
        $enrollmentsCount = $this->countTable('enrollments');
        $studentsCount = $this->countTable('students');
        $teachersCount = $this->countTable('teachers');

        if ($teachersCount === 0) {
            $teachersCount = (int) User::role('Teacher')->count();
        }

        if ($studentsCount === 0) {
            $studentsCount = (int) User::role('Student')->count();
            if ($studentsCount === 0) {
                $studentsCount = $this->countTable('users');
            }
        }

        return [
            'total_courses' => $coursesCount,
            'total_enrollments' => $enrollmentsCount,
            'total_students' => $studentsCount,
            'total_teachers' => $teachersCount,
        ];
    }

    private function buildOverviewSeries(string $range): array
    {
        try {
            if ($range === '12m') {
                $labels = [];
                $courses = [];
                $enroll = [];
                $users = [];

                $now = new \DateTimeImmutable('first day of this month');
                $monthKeys = [];
                for ($i = 11; $i >= 0; $i--) {
                    $m = $now->modify('-' . $i . ' months');
                    $labels[] = $m->format('M');
                    $key = $m->format('Y-m');
                    $monthKeys[] = $key;
                    $courses[$key] = 0;
                    $enroll[$key] = 0;
                    $users[$key] = 0;
                }

                if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'created_at')) {
                    $rows = DB::table('courses')
                        ->select([DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as c')])
                        ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                        ->groupBy('ym')
                        ->get();

                    foreach ($rows as $r) {
                        $k = (string) ($r->ym ?? '');
                        if ($k !== '' && array_key_exists($k, $courses)) {
                            $courses[$k] = (int) ($r->c ?? 0);
                        }
                    }
                }

                if (Schema::hasTable('enrollments')) {
                    $col = Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrolled_at' : (Schema::hasColumn('enrollments', 'created_at') ? 'created_at' : null);
                    if ($col) {
                        $rows = DB::table('enrollments')
                            ->select([DB::raw("DATE_FORMAT($col, '%Y-%m') as ym"), DB::raw('COUNT(*) as c')])
                            ->where($col, '>=', now()->subMonths(11)->startOfMonth())
                            ->groupBy('ym')
                            ->get();

                        foreach ($rows as $r) {
                            $k = (string) ($r->ym ?? '');
                            if ($k !== '' && array_key_exists($k, $enroll)) {
                                $enroll[$k] = (int) ($r->c ?? 0);
                            }
                        }
                    }
                }

                if (Schema::hasTable('users') && Schema::hasColumn('users', 'created_at')) {
                    $rows = DB::table('users')
                        ->select([DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as c')])
                        ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                        ->groupBy('ym')
                        ->get();

                    foreach ($rows as $r) {
                        $k = (string) ($r->ym ?? '');
                        if ($k !== '' && array_key_exists($k, $users)) {
                            $users[$k] = (int) ($r->c ?? 0);
                        }
                    }
                }

                return [
                    'labels' => $labels,
                    'series' => [
                        'courses' => array_values($courses),
                        'enrollments' => array_values($enroll),
                        'users' => array_values($users),
                    ],
                ];
            }

            $days = $range === '30d' ? 30 : 7;
            $labels = [];
            $keys = [];
            $courses = [];
            $enroll = [];
            $users = [];

            $start = now()->subDays($days - 1)->startOfDay();
            for ($i = 0; $i < $days; $i++) {
                $d = $start->copy()->addDays($i);
                $labels[] = $d->format('M j');
                $k = $d->format('Y-m-d');
                $keys[] = $k;
                $courses[$k] = 0;
                $enroll[$k] = 0;
                $users[$k] = 0;
            }

            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'created_at')) {
                $rows = DB::table('courses')
                    ->select([DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c')])
                    ->where('created_at', '>=', $start)
                    ->groupBy('d')
                    ->get();

                foreach ($rows as $r) {
                    $k = (string) ($r->d ?? '');
                    if ($k !== '' && array_key_exists($k, $courses)) {
                        $courses[$k] = (int) ($r->c ?? 0);
                    }
                }
            }

            if (Schema::hasTable('enrollments')) {
                $col = Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrolled_at' : (Schema::hasColumn('enrollments', 'created_at') ? 'created_at' : null);
                if ($col) {
                    $rows = DB::table('enrollments')
                        ->select([DB::raw("DATE($col) as d"), DB::raw('COUNT(*) as c')])
                        ->where($col, '>=', $start)
                        ->groupBy('d')
                        ->get();

                    foreach ($rows as $r) {
                        $k = (string) ($r->d ?? '');
                        if ($k !== '' && array_key_exists($k, $enroll)) {
                            $enroll[$k] = (int) ($r->c ?? 0);
                        }
                    }
                }
            }

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'created_at')) {
                $rows = DB::table('users')
                    ->select([DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c')])
                    ->where('created_at', '>=', $start)
                    ->groupBy('d')
                    ->get();

                foreach ($rows as $r) {
                    $k = (string) ($r->d ?? '');
                    if ($k !== '' && array_key_exists($k, $users)) {
                        $users[$k] = (int) ($r->c ?? 0);
                    }
                }
            }

            return [
                'labels' => $labels,
                'series' => [
                    'courses' => array_values($courses),
                    'enrollments' => array_values($enroll),
                    'users' => array_values($users),
                ],
            ];
        } catch (\Throwable) {
            return [
                'labels' => [],
                'series' => [
                    'courses' => [],
                    'enrollments' => [],
                    'users' => [],
                ],
            ];
        }
    }

    private function buildTopCourses(): array
    {
        try {
            if (!Schema::hasTable('courses') || !Schema::hasTable('enrollments')) {
                return [];
            }

            if (!Schema::hasColumn('enrollments', 'course_id')) {
                return [];
            }

            $courseNameColumn = null;
            foreach (['title', 'name', 'course_name'] as $candidate) {
                if (Schema::hasColumn('courses', $candidate)) {
                    $courseNameColumn = $candidate;
                    break;
                }
            }

            if ($courseNameColumn === null || !Schema::hasColumn('courses', 'id')) {
                return [];
            }

            $rows = DB::table('enrollments')
                ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                ->select([
                    'courses.' . $courseNameColumn . ' as course_name',
                    DB::raw('COUNT(*) as enrollments'),
                ])
                ->groupBy('courses.' . $courseNameColumn)
                ->orderByDesc('enrollments')
                ->limit(5)
                ->get();

            return $rows
                ->map(fn ($r) => [
                    'course_name' => (string) ($r->course_name ?? ''),
                    'enrollments' => (int) ($r->enrollments ?? 0),
                ])
                ->filter(fn ($r) => $r['course_name'] !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildBestSellingCourse(): array
    {
        $rows = $this->buildBestSellingCourses(1);

        return $rows[0] ?? [
            'course_id' => 0,
            'course_name' => '',
            'teacher_name' => '',
            'sales' => 0,
        ];
    }

    private function buildBestSellingCourses(int $limit): array
    {
        try {
            if (!Schema::hasTable('courses') || !Schema::hasTable('enrollments')) {
                return [];
            }

            if (!Schema::hasColumn('enrollments', 'course_id')) {
                return [];
            }

            $courseNameColumn = null;
            foreach (['title', 'name', 'course_name'] as $candidate) {
                if (Schema::hasColumn('courses', $candidate)) {
                    $courseNameColumn = $candidate;
                    break;
                }
            }

            if ($courseNameColumn === null || !Schema::hasColumn('courses', 'id')) {
                return [];
            }

            $query = DB::table('enrollments')
                ->join('courses', 'courses.id', '=', 'enrollments.course_id');

            $hasTeacher = Schema::hasColumn('courses', 'teacher_id')
                && Schema::hasTable('users')
                && Schema::hasColumn('users', 'id')
                && Schema::hasColumn('users', 'name');

            if ($hasTeacher) {
                $query->leftJoin('users', 'users.id', '=', 'courses.teacher_id');
            }

            $select = [
                'courses.id as course_id',
                'courses.' . $courseNameColumn . ' as course_name',
                DB::raw('COUNT(*) as sales'),
            ];

            if ($hasTeacher) {
                $select[] = 'users.name as teacher_name';
            }

            $groupBy = ['courses.' . $courseNameColumn];
            if (Schema::hasColumn('courses', 'id')) {
                $groupBy[] = 'courses.id';
            }
            if ($hasTeacher) {
                $groupBy[] = 'users.name';
            }

            $rows = $query
                ->select($select)
                ->groupBy($groupBy)
                ->orderByDesc('sales')
                ->limit($limit)
                ->get();

            return $rows
                ->map(fn ($r) => [
                    'course_id' => (int) ($r->course_id ?? 0),
                    'course_name' => (string) ($r->course_name ?? ''),
                    'teacher_name' => (string) ($r->teacher_name ?? ''),
                    'sales' => (int) ($r->sales ?? 0),
                ])
                ->filter(fn ($r) => $r['course_name'] !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildRecentEnrollments(): array
    {
        try {
            if (!Schema::hasTable('enrollments')) {
                return [];
            }

            $query = DB::table('enrollments');

            $courseNameColumn = null;
            if (Schema::hasTable('courses') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasColumn('courses', 'id')) {
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameColumn = $candidate;
                        break;
                    }
                }

                if ($courseNameColumn) {
                    $query->leftJoin('courses', 'courses.id', '=', 'enrollments.course_id');
                }
            }

            $hasStudentJoin = false;
            if (Schema::hasTable('users') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('users', 'id')) {
                $query->leftJoin('users', 'users.id', '=', 'enrollments.student_id');
                $hasStudentJoin = true;
            }

            $select = [];
            $enrolledCol = Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrollments.enrolled_at as enrolled_at' : (Schema::hasColumn('enrollments', 'created_at') ? 'enrollments.created_at as enrolled_at' : null);
            if ($enrolledCol) {
                $select[] = $enrolledCol;
            }
            if (Schema::hasColumn('enrollments', 'id')) {
                $select[] = 'enrollments.id as enrollment_id';
            }
            if ($courseNameColumn) {
                $select[] = 'courses.' . $courseNameColumn . ' as course_name';
            }
            if ($hasStudentJoin && Schema::hasColumn('users', 'name')) {
                $select[] = 'users.name as student_name';
            }

            if (empty($select)) {
                return [];
            }

            if (Schema::hasColumn('enrollments', 'enrolled_at')) {
                $query->orderByDesc('enrollments.enrolled_at');
            } elseif (Schema::hasColumn('enrollments', 'created_at')) {
                $query->orderByDesc('enrollments.created_at');
            } elseif (Schema::hasColumn('enrollments', 'id')) {
                $query->orderByDesc('enrollments.id');
            }

            $rows = $query
                ->select($select)
                ->limit(10)
                ->get();

            return $rows
                ->map(fn ($r) => [
                    'course_name' => (string) ($r->course_name ?? 'Course'),
                    'student_name' => (string) ($r->student_name ?? 'Student'),
                    'enrolled_at' => (string) ($r->enrolled_at ?? ''),
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildSystemOverview(): array
    {
        $activeCourses = 0;
        $inactiveCourses = 0;
        $teachersWithoutCourses = 0;
        $studentsNotEnrolled = 0;

        try {
            if (Schema::hasTable('courses')) {
                if (Schema::hasColumn('courses', 'end_date')) {
                    $activeCourses = (int) DB::table('courses')->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString())->count();
                    $inactiveCourses = (int) DB::table('courses')->whereNotNull('end_date')->where('end_date', '<', now()->toDateString())->count();
                } else {
                    $activeCourses = (int) DB::table('courses')->count();
                    $inactiveCourses = 0;
                }
            }

            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $teachersWithCourses = DB::table('courses')->whereNotNull('teacher_id')->distinct('teacher_id')->count('teacher_id');
                $totalTeachers = (int) User::role('Teacher')->count();
                $teachersWithoutCourses = max(0, $totalTeachers - (int) $teachersWithCourses);
            }

            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id')) {
                $enrolledStudents = (int) DB::table('enrollments')->distinct('student_id')->count('student_id');
                $totalStudents = (int) User::role('Student')->count();
                $studentsNotEnrolled = max(0, $totalStudents - $enrolledStudents);
            }
        } catch (\Throwable) {
        }

        return [
            'active_courses' => $activeCourses,
            'inactive_courses' => $inactiveCourses,
            'teachers_without_courses' => $teachersWithoutCourses,
            'students_not_enrolled' => $studentsNotEnrolled,
        ];
    }

    private function buildRecentActivity(): array
    {
        $items = [];

        try {
            $courseNameColumn = null;
            if (Schema::hasTable('courses')) {
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameColumn = $candidate;
                        break;
                    }
                }
            }

            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasTable('users') && Schema::hasColumn('users', 'name') && $courseNameColumn) {
                $col = Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrollments.enrolled_at' : (Schema::hasColumn('enrollments', 'created_at') ? 'enrollments.created_at' : null);
                if ($col) {
                    $rows = DB::table('enrollments')
                        ->join('users', 'users.id', '=', 'enrollments.student_id')
                        ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                        ->select([
                            DB::raw("'enrollment' as type"),
                            'users.name as actor',
                            'courses.' . $courseNameColumn . ' as target',
                            DB::raw("$col as happened_at"),
                        ])
                        ->orderByDesc('happened_at')
                        ->limit(10)
                        ->get();

                    foreach ($rows as $r) {
                        $items[] = [
                            'type' => 'enrollment',
                            'message' => (string) ($r->actor ?? 'Student') . ' enrolled in ' . (string) ($r->target ?? 'Course'),
                            'happened_at' => (string) ($r->happened_at ?? ''),
                        ];
                    }
                }
            }

            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'created_at') && Schema::hasColumn('courses', 'teacher_id') && Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
                $nameCol = $courseNameColumn ?? (Schema::hasColumn('courses', 'title') ? 'title' : null);
                if ($nameCol) {
                    $rows = DB::table('courses')
                        ->leftJoin('users', 'users.id', '=', 'courses.teacher_id')
                        ->select([
                            DB::raw("'course' as type"),
                            'users.name as actor',
                            'courses.' . $nameCol . ' as target',
                            'courses.created_at as happened_at',
                        ])
                        ->orderByDesc('courses.created_at')
                        ->limit(10)
                        ->get();

                    foreach ($rows as $r) {
                        $items[] = [
                            'type' => 'course',
                            'message' => ((string) ($r->actor ?? 'Teacher') !== '' ? (string) $r->actor : 'Teacher') . ' created course ' . (string) ($r->target ?? 'Course'),
                            'happened_at' => (string) ($r->happened_at ?? ''),
                        ];
                    }
                }
            }

            if (Schema::hasTable('submissions') && Schema::hasTable('assignments') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id') && Schema::hasColumn('assignments', 'title') && Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
                $submittedCol = Schema::hasColumn('submissions', 'submitted_at') ? 'submissions.submitted_at' : (Schema::hasColumn('submissions', 'created_at') ? 'submissions.created_at' : null);
                if ($submittedCol) {
                    $rows = DB::table('submissions')
                        ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                        ->join('users', 'users.id', '=', 'submissions.student_id')
                        ->select([
                            DB::raw("'submission' as type"),
                            'users.name as actor',
                            'assignments.title as target',
                            DB::raw("$submittedCol as happened_at"),
                        ])
                        ->orderByDesc('happened_at')
                        ->limit(10)
                        ->get();

                    foreach ($rows as $r) {
                        $items[] = [
                            'type' => 'submission',
                            'message' => (string) ($r->actor ?? 'Student') . ' submitted ' . (string) ($r->target ?? 'Assignment'),
                            'happened_at' => (string) ($r->happened_at ?? ''),
                        ];
                    }
                }
            }

            if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'title') && Schema::hasColumn('announcements', 'teacher_id') && Schema::hasColumn('announcements', 'created_at') && Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
                $rows = DB::table('announcements')
                    ->leftJoin('users', 'users.id', '=', 'announcements.teacher_id')
                    ->select([
                        DB::raw("'announcement' as type"),
                        'users.name as actor',
                        'announcements.title as target',
                        'announcements.created_at as happened_at',
                    ])
                    ->orderByDesc('announcements.created_at')
                    ->limit(10)
                    ->get();

                foreach ($rows as $r) {
                    $items[] = [
                        'type' => 'announcement',
                        'message' => ((string) ($r->actor ?? 'Teacher') !== '' ? (string) $r->actor : 'Teacher') . ' posted announcement ' . (string) ($r->target ?? ''),
                        'happened_at' => (string) ($r->happened_at ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
        }

        usort($items, fn ($a, $b) => strcmp((string) ($b['happened_at'] ?? ''), (string) ($a['happened_at'] ?? '')));

        return array_slice($items, 0, 10);
    }

    private function buildAnalyticsSeries(): array
    {
        $enrollmentsLast7Days = [
            'labels' => [],
            'data' => [],
        ];
        $coursesPerMonth = [
            'labels' => [],
            'data' => [],
        ];
        $studentsPerCourse = [
            'labels' => [],
            'data' => [],
        ];
        $userGrowth = [
            'labels' => [],
            'students' => [],
            'teachers' => [],
        ];

        try {
            $start = now()->subDays(6)->startOfDay();
            $labels = [];
            $keys = [];
            $series = [];

            for ($i = 0; $i < 7; $i++) {
                $d = $start->copy()->addDays($i);
                $labels[] = $d->format('M j');
                $k = $d->format('Y-m-d');
                $keys[] = $k;
                $series[$k] = 0;
            }

            if (Schema::hasTable('enrollments')) {
                $col = Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrolled_at' : (Schema::hasColumn('enrollments', 'created_at') ? 'created_at' : null);
                if ($col) {
                    $rows = DB::table('enrollments')
                        ->select([DB::raw("DATE($col) as d"), DB::raw('COUNT(*) as c')])
                        ->where($col, '>=', $start)
                        ->groupBy('d')
                        ->get();

                    foreach ($rows as $r) {
                        $k = (string) ($r->d ?? '');
                        if ($k !== '' && array_key_exists($k, $series)) {
                            $series[$k] = (int) ($r->c ?? 0);
                        }
                    }
                }
            }

            $enrollmentsLast7Days = [
                'labels' => $labels,
                'data' => array_values($series),
            ];

            $monthLabels = [];
            $monthKeys = [];
            $monthSeries = [];
            $now = now()->startOfMonth();
            for ($i = 11; $i >= 0; $i--) {
                $m = $now->copy()->subMonths($i);
                $monthLabels[] = $m->format('M');
                $k = $m->format('Y-m');
                $monthKeys[] = $k;
                $monthSeries[$k] = 0;
            }

            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'created_at')) {
                $rows = DB::table('courses')
                    ->select([DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('COUNT(*) as c')])
                    ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                    ->groupBy('ym')
                    ->get();

                foreach ($rows as $r) {
                    $k = (string) ($r->ym ?? '');
                    if ($k !== '' && array_key_exists($k, $monthSeries)) {
                        $monthSeries[$k] = (int) ($r->c ?? 0);
                    }
                }
            }

            $coursesPerMonth = [
                'labels' => $monthLabels,
                'data' => array_values($monthSeries),
            ];

            if (Schema::hasTable('courses') && Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id')) {
                $courseNameColumn = null;
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameColumn = $candidate;
                        break;
                    }
                }

                if ($courseNameColumn && Schema::hasColumn('courses', 'id')) {
                    $rows = DB::table('enrollments')
                        ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                        ->select([
                            'courses.' . $courseNameColumn . ' as name',
                            DB::raw('COUNT(*) as c'),
                        ])
                        ->groupBy('courses.' . $courseNameColumn)
                        ->orderByDesc('c')
                        ->limit(10)
                        ->get();

                    $labels = [];
                    $data = [];
                    foreach ($rows as $r) {
                        $labels[] = (string) ($r->name ?? 'Course');
                        $data[] = (int) ($r->c ?? 0);
                    }

                    $studentsPerCourse = [
                        'labels' => $labels,
                        'data' => $data,
                    ];
                }
            }

            $days = 30;
            $start = now()->subDays($days - 1)->startOfDay();
            $labels = [];
            $keys = [];
            $students = [];
            $teachers = [];
            for ($i = 0; $i < $days; $i++) {
                $d = $start->copy()->addDays($i);
                $labels[] = $d->format('M j');
                $k = $d->format('Y-m-d');
                $keys[] = $k;
                $students[$k] = 0;
                $teachers[$k] = 0;
            }

            if (Schema::hasTable('users') && Schema::hasColumn('users', 'created_at') && Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
                $roleRows = DB::table('roles')->whereIn('name', ['Student', 'Teacher'])->select(['id', 'name'])->get();
                $roleIds = [];
                foreach ($roleRows as $r) {
                    $roleIds[(string) $r->name] = (int) $r->id;
                }

                foreach (['Student' => &$students, 'Teacher' => &$teachers] as $roleName => &$seriesRef) {
                    $rid = $roleIds[$roleName] ?? 0;
                    if ($rid > 0) {
                        $rows = DB::table('users')
                            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                            ->where('model_has_roles.role_id', $rid)
                            ->where('model_has_roles.model_type', User::class)
                            ->where('users.created_at', '>=', $start)
                            ->select([DB::raw('DATE(users.created_at) as d'), DB::raw('COUNT(*) as c')])
                            ->groupBy('d')
                            ->get();

                        foreach ($rows as $r) {
                            $k = (string) ($r->d ?? '');
                            if ($k !== '' && array_key_exists($k, $seriesRef)) {
                                $seriesRef[$k] = (int) ($r->c ?? 0);
                            }
                        }
                    }
                }
            }

            $userGrowth = [
                'labels' => $labels,
                'students' => array_values($students),
                'teachers' => array_values($teachers),
            ];
        } catch (\Throwable) {
        }

        return [
            'enrollments_last_7_days' => $enrollmentsLast7Days,
            'courses_per_month' => $coursesPerMonth,
            'students_per_course' => $studentsPerCourse,
            'user_growth' => $userGrowth,
        ];
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
