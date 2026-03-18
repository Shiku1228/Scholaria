<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherStudentController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $rows = collect();
        $courses = collect();
        $filters = [
            'course_id' => $request->query('course_id'),
            'student' => $request->query('student'),
        ];

        try {
            if (!Schema::hasTable('courses') || !Schema::hasTable('enrollments') || !Schema::hasTable('users')) {
                return view('teacher.students.index', ['rows' => $rows, 'courses' => $courses, 'filters' => $filters]);
            }

            if (!Schema::hasColumn('courses', 'teacher_id') || !Schema::hasColumn('enrollments', 'course_id') || !Schema::hasColumn('enrollments', 'student_id')) {
                return view('teacher.students.index', ['rows' => $rows, 'courses' => $courses, 'filters' => $filters]);
            }

            $courseNameCol = null;
            foreach (['course_number', 'title', 'name', 'course_name'] as $c) {
                if (Schema::hasColumn('courses', $c)) {
                    $courseNameCol = $c;
                    break;
                }
            }

            if ($courseNameCol === null || !Schema::hasColumn('users', 'name')) {
                return view('teacher.students.index', ['rows' => $rows, 'courses' => $courses, 'filters' => $filters]);
            }

            $courses = DB::table('courses')
                ->where('teacher_id', $teacherId)
                ->select(['id', $courseNameCol . ' as name'])
                ->orderBy($courseNameCol)
                ->get();

            $select = [
                'users.name as student_name',
                'courses.' . $courseNameCol . ' as course_name',
            ];

            if (Schema::hasColumn('courses', 'semester')) {
                $select[] = 'courses.semester as semester';
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
                ->when($filters['course_id'], function ($q) use ($filters) {
                    $q->where('courses.id', (int) $filters['course_id']);
                })
                ->when($filters['student'], function ($q) use ($filters) {
                    $term = trim((string) $filters['student']);
                    if ($term !== '') {
                        $q->where('users.name', 'like', '%' . $term . '%');
                    }
                })
                ->select($select)
                ->orderBy('users.name')
                ->limit(500)
                ->get();
        } catch (\Throwable) {
            $rows = collect();
            $courses = collect();
        }

        return view('teacher.students.index', [
            'rows' => $rows,
            'courses' => $courses,
            'filters' => $filters,
        ]);
    }
}
