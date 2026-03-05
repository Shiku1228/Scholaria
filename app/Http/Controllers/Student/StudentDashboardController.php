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
                        $myCourses = DB::table('enrollments')
                            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                            ->where('enrollments.student_id', $userId)
                            ->select(['courses.' . $nameCol . ' as course_name'])
                            ->limit(10)
                            ->get()
                            ->map(fn ($r) => ['course_name' => (string) $r->course_name])
                            ->all();
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
