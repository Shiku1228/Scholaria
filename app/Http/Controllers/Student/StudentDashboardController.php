<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $enrolledCourses = 0;
        $inProgress = 0;
        $completed = 0;
        $myCourses = [];

        try {
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'user_id')) {
                $enrolledCourses = (int) DB::table('enrollments')->where('user_id', $userId)->count();

                if (Schema::hasColumn('enrollments', 'status')) {
                    $inProgress = (int) DB::table('enrollments')->where('user_id', $userId)->where('status', 'in_progress')->count();
                    $completed = (int) DB::table('enrollments')->where('user_id', $userId)->where('status', 'completed')->count();
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
                            ->where('enrollments.user_id', $userId)
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
                'enrolled_courses' => $enrolledCourses,
                'in_progress' => $inProgress,
                'completed' => $completed,
            ],
            'myCourses' => $myCourses,
        ]);
    }
}
