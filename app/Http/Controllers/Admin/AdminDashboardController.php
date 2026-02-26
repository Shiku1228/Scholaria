<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        return view('admin.dashboard', [
            'filters' => [
                'range' => $range,
            ],
            'stats' => $stats,
            'overview' => $overview,
            'bestSellingCourse' => $bestSellingCourse,
            'bestSellingCourses' => $bestSellingCourses,
            'recentEnrollments' => $recentEnrollments,
        ]);
    }

    private function buildStats(int $rangeDays): array
    {
        $coursesCount = $this->countTable('courses');
        $enrollmentsCount = $this->countTable('enrollments');
        $studentsCount = $this->countTable('students');
        $teachersCount = $this->countTable('teachers');

        if ($teachersCount === 0) {
            $teachersCount = $this->countUsersByRole('teacher');
        }

        if ($studentsCount === 0) {
            $studentsCount = $this->countUsersByRole('student');
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
        if ($range === '12m') {
            $labels = [];
            $courses = [];
            $enroll = [];
            $users = [];

            $now = new \DateTimeImmutable('first day of this month');
            for ($i = 11; $i >= 0; $i--) {
                $m = $now->modify('-' . $i . ' months');
                $labels[] = $m->format('M');
                $courses[] = 0;
                $enroll[] = 0;
                $users[] = 0;
            }

            return [
                'labels' => $labels,
                'series' => [
                    'courses' => $courses,
                    'enrollments' => $enroll,
                    'users' => $users,
                ],
            ];
        }

        $days = $range === '30d' ? 30 : 7;
        $labels = [];
        $courses = [];
        $enroll = [];
        $users = [];

        $start = new \DateTimeImmutable('-' . ($days - 1) . ' days');
        for ($i = 0; $i < $days; $i++) {
            $d = $start->modify('+' . $i . ' days');
            $labels[] = $d->format('M j');
            $courses[] = 0;
            $enroll[] = 0;
            $users[] = 0;
        }

        return [
            'labels' => $labels,
            'series' => [
                'courses' => $courses,
                'enrollments' => $enroll,
                'users' => $users,
            ],
        ];
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
                'courses.' . $courseNameColumn . ' as course_name',
                DB::raw('COUNT(*) as sales'),
            ];

            if ($hasTeacher) {
                $select[] = 'users.name as teacher_name';
            }

            $groupBy = ['courses.' . $courseNameColumn];
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

            $hasUserJoin = false;
            if (Schema::hasTable('users') && Schema::hasColumn('enrollments', 'user_id') && Schema::hasColumn('users', 'id')) {
                $query->leftJoin('users', 'users.id', '=', 'enrollments.user_id');
                $hasUserJoin = true;
            }

            $select = [];
            if (Schema::hasColumn('enrollments', 'created_at')) {
                $select[] = 'enrollments.created_at as enrolled_at';
            }
            if (Schema::hasColumn('enrollments', 'id')) {
                $select[] = 'enrollments.id as enrollment_id';
            }
            if ($courseNameColumn) {
                $select[] = 'courses.' . $courseNameColumn . ' as course_name';
            }
            if ($hasUserJoin && Schema::hasColumn('users', 'name')) {
                $select[] = 'users.name as student_name';
            }

            if (empty($select)) {
                return [];
            }

            if (Schema::hasColumn('enrollments', 'created_at')) {
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

    private function countUsersByRole(string $role): int
    {
        try {
            if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role')) {
                return 0;
            }

            return (int) DB::table('users')->where('role', $role)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
