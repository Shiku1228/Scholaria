<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
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

        try {
            $totalCourses = $this->countTable('courses');
            $totalEnrollments = $this->countTable('enrollments');
            $totalStudents = $this->countTable('students');
            $totalTeachers = $this->countTable('teachers');

            if ($totalTeachers === 0) {
                $totalTeachers = $this->countUsersByRole('teacher');
            }

            if ($totalStudents === 0) {
                $totalStudents = $this->countUsersByRole('student');
                if ($totalStudents === 0) {
                    $totalStudents = $this->countTable('users');
                }
            }

            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $myCourses = (int) DB::table('courses')->where('teacher_id', $userId)->count();

                if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id')) {
                    $courseIds = DB::table('courses')->where('teacher_id', $userId)->pluck('id');

                    if ($courseIds->isNotEmpty()) {
                        $studentsInMyCourses = (int) DB::table('enrollments')->whereIn('course_id', $courseIds)->count();

                        $start = now()->subDays(7);
                        if (Schema::hasColumn('enrollments', 'created_at')) {
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

                        if (Schema::hasColumn('enrollments', 'created_at')) {
                            $recentEnrollments = DB::table('enrollments')
                                ->whereIn('course_id', $courseIds)
                                ->orderByDesc('created_at')
                                ->limit(10)
                                ->get()
                                ->map(fn ($r) => [
                                    'course_id' => (int) ($r->course_id ?? 0),
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
