<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $totalCourses = 0;
        $totalEnrollments = 0;
        $totalStudents = 0;
        $totalTeachers = 0;

        $enrolledCourses = 0;
        $inProgress = 0;
        $completed = 0;
        $myCourses = [];
        $upcomingAssignments = [];
        $recentAnnouncements = [];
        $learningProgress = [];

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

            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id')) {
                $enrolledCourses = (int) DB::table('enrollments')->where('student_id', $userId)->count();

                if (Schema::hasColumn('enrollments', 'status')) {
                    $inProgress = (int) DB::table('enrollments')->where('student_id', $userId)->where('status', 'active')->count();
                    $completed = (int) DB::table('enrollments')->where('student_id', $userId)->where('status', 'completed')->count();
                }

                if (Schema::hasColumn('enrollments', 'course_id') && Schema::hasTable('courses') && Schema::hasColumn('courses', 'id')) {
                    $nameCol = null;
                    foreach (['title', 'name', 'course_name'] as $c) {
                        if (Schema::hasColumn('courses', $c)) {
                            $nameCol = $c;
                            break;
                        }
                    }

                    if ($nameCol) {
                        $enrollmentsQuery = DB::table('enrollments')
                            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                            ->where('enrollments.student_id', $userId);

                        $hasTeacherJoin = Schema::hasColumn('courses', 'teacher_id')
                            && Schema::hasTable('users')
                            && Schema::hasColumn('users', 'id')
                            && Schema::hasColumn('users', 'name');

                        if ($hasTeacherJoin) {
                            $enrollmentsQuery->leftJoin('users', 'users.id', '=', 'courses.teacher_id');
                        }

                        $select = [
                            'courses.id as course_id',
                            'courses.' . $nameCol . ' as course_name',
                        ];

                        if ($hasTeacherJoin) {
                            $select[] = 'users.name as teacher_name';
                        }

                        if (Schema::hasColumn('enrollments', 'status')) {
                            $select[] = 'enrollments.status as enrollment_status';
                        }

                        $courseRows = $enrollmentsQuery
                            ->select($select)
                            ->orderByDesc('enrollments.id')
                            ->limit(12)
                            ->get();

                        $courseIds = $courseRows->pluck('course_id')->map(fn ($v) => (int) $v)->filter()->values()->all();

                        $assignmentTotals = [];
                        $submissionTotals = [];

                        if (!empty($courseIds) && Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                            $totals = DB::table('assignments')
                                ->whereIn('course_id', $courseIds)
                                ->select(['course_id', DB::raw('COUNT(*) as c')])
                                ->groupBy('course_id')
                                ->get();

                            foreach ($totals as $t) {
                                $assignmentTotals[(int) $t->course_id] = (int) ($t->c ?? 0);
                            }

                            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                                $submitted = DB::table('submissions')
                                    ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                    ->where('submissions.student_id', $userId)
                                    ->whereIn('assignments.course_id', $courseIds)
                                    ->select(['assignments.course_id as course_id', DB::raw('COUNT(*) as c')])
                                    ->groupBy('assignments.course_id')
                                    ->get();

                                foreach ($submitted as $s) {
                                    $submissionTotals[(int) $s->course_id] = (int) ($s->c ?? 0);
                                }
                            }
                        }

                        $myCourses = $courseRows
                            ->map(function ($r) use ($assignmentTotals, $submissionTotals) {
                                $courseId = (int) ($r->course_id ?? 0);
                                $total = (int) ($assignmentTotals[$courseId] ?? 0);
                                $done = (int) ($submissionTotals[$courseId] ?? 0);
                                $progress = $total > 0 ? (int) round(min(100, max(0, ($done / $total) * 100))) : 0;

                                return [
                                    'course_id' => $courseId,
                                    'course_name' => (string) ($r->course_name ?? ''),
                                    'teacher_name' => (string) ($r->teacher_name ?? ''),
                                    'enrollment_status' => (string) ($r->enrollment_status ?? ''),
                                    'progress' => $progress,
                                ];
                            })
                            ->values()
                            ->all();

                        $learningProgress = collect($myCourses)
                            ->map(fn ($c) => [
                                'course_id' => (int) ($c['course_id'] ?? 0),
                                'course_name' => (string) ($c['course_name'] ?? ''),
                                'progress' => (int) ($c['progress'] ?? 0),
                            ])
                            ->values()
                            ->all();

                        if (!empty($courseIds) && Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                            $assignQuery = DB::table('assignments')
                                ->join('courses', 'courses.id', '=', 'assignments.course_id')
                                ->whereIn('assignments.course_id', $courseIds);

                            if (Schema::hasColumn('assignments', 'due_date')) {
                                $assignQuery->where(function ($q) {
                                    $q->whereNull('assignments.due_date')
                                        ->orWhere('assignments.due_date', '>=', now());
                                })->orderBy('assignments.due_date');
                            } else {
                                $assignQuery->orderByDesc('assignments.id');
                            }

                            $upcomingAssignments = $assignQuery
                                ->select([
                                    'assignments.id as assignment_id',
                                    'assignments.title as assignment_title',
                                    'courses.' . $nameCol . ' as course_name',
                                    Schema::hasColumn('assignments', 'due_date') ? 'assignments.due_date as due_date' : DB::raw("'' as due_date"),
                                ])
                                ->limit(5)
                                ->get()
                                ->map(fn ($r) => [
                                    'assignment_id' => (int) ($r->assignment_id ?? 0),
                                    'assignment_title' => (string) ($r->assignment_title ?? ''),
                                    'course_name' => (string) ($r->course_name ?? ''),
                                    'due_date' => (string) ($r->due_date ?? ''),
                                ])
                                ->values()
                                ->all();
                        }

                        if (!empty($courseIds) && Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'course_id') && Schema::hasColumn('announcements', 'title')) {
                            $annQuery = DB::table('announcements')
                                ->join('courses', 'courses.id', '=', 'announcements.course_id')
                                ->whereIn('announcements.course_id', $courseIds);

                            if (Schema::hasColumn('announcements', 'created_at')) {
                                $annQuery->orderByDesc('announcements.created_at');
                            } else {
                                $annQuery->orderByDesc('announcements.id');
                            }

                            $recentAnnouncements = $annQuery
                                ->select([
                                    'courses.' . $nameCol . ' as course_name',
                                    'announcements.title as title',
                                    Schema::hasColumn('announcements', 'created_at') ? 'announcements.created_at as created_at' : DB::raw("'' as created_at"),
                                ])
                                ->limit(6)
                                ->get()
                                ->map(fn ($r) => [
                                    'course_name' => (string) ($r->course_name ?? ''),
                                    'title' => (string) ($r->title ?? ''),
                                    'created_at' => (string) ($r->created_at ?? ''),
                                ])
                                ->values()
                                ->all();
                        }
                    }
                }
            }
        } catch (\Throwable) {
        }

        return view('student.dashboard', [
            'stats' => [
                'total_courses' => $totalCourses,
                'total_enrollments' => $totalEnrollments,
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'enrolled_courses' => $enrolledCourses,
                'in_progress' => $inProgress,
                'completed' => $completed,
            ],
            'myCourses' => $myCourses,
            'upcomingAssignments' => $upcomingAssignments,
            'recentAnnouncements' => $recentAnnouncements,
            'learningProgress' => $learningProgress,
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
