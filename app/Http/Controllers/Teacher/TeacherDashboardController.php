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
