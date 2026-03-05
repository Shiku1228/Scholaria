<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $rows = collect();

        try {
            if (!Schema::hasTable('courses') || !Schema::hasTable('enrollments') || !Schema::hasTable('users')) {
                return view('teacher.enrollments.index', ['rows' => $rows]);
            }

            if (!Schema::hasColumn('courses', 'teacher_id') || !Schema::hasColumn('enrollments', 'course_id') || !Schema::hasColumn('enrollments', 'student_id')) {
                return view('teacher.enrollments.index', ['rows' => $rows]);
            }

            $courseNameCol = null;
            foreach (['course_number', 'title', 'name', 'course_name'] as $c) {
                if (Schema::hasColumn('courses', $c)) {
                    $courseNameCol = $c;
                    break;
                }
            }

            if ($courseNameCol === null || !Schema::hasColumn('users', 'name')) {
                return view('teacher.enrollments.index', ['rows' => $rows]);
            }

            $select = [
                'users.name as student_name',
                'courses.' . $courseNameCol . ' as course_name',
            ];

            if (Schema::hasColumn('enrollments', 'status')) {
                $select[] = 'enrollments.status as status';
            }

            if (Schema::hasColumn('enrollments', 'enrolled_at')) {
                $select[] = 'enrollments.enrolled_at as enrolled_at';
            } elseif (Schema::hasColumn('enrollments', 'created_at')) {
                $select[] = 'enrollments.created_at as enrolled_at';
            }

            $rows = DB::table('enrollments')
                ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                ->join('users', 'users.id', '=', 'enrollments.student_id')
                ->where('courses.teacher_id', $teacherId)
                ->select($select)
                ->orderByDesc(Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrollments.enrolled_at' : 'enrollments.created_at')
                ->limit(500)
                ->get();
        } catch (\Throwable) {
            $rows = collect();
        }

        return view('teacher.enrollments.index', [
            'rows' => $rows,
        ]);
    }
}
