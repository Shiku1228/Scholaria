<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
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
            if (!Schema::hasTable('users')) {
                return view('teacher.students.index', ['rows' => $rows, 'courses' => $courses, 'filters' => $filters]);
            }

            if (!Schema::hasColumn('users', 'name')) {
                return view('teacher.students.index', ['rows' => $rows, 'courses' => $courses, 'filters' => $filters]);
            }

            // Get teacher's courses for filter dropdown
            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $courseNameCol = null;
                foreach (['course_number', 'title', 'name', 'course_name'] as $c) {
                    if (Schema::hasColumn('courses', $c)) {
                        $courseNameCol = $c;
                        break;
                    }
                }

                if ($courseNameCol) {
                    $courses = DB::table('courses')
                        ->where('teacher_id', $teacherId)
                        ->select(['id', $courseNameCol . ' as name'])
                        ->orderBy($courseNameCol)
                        ->get();
                }
            }

            // Get ALL students with Student role in the system
            $studentsQuery = User::query();
            $hasSpatieRoles = in_array('Spatie\Permission\Traits\HasRoles', class_uses_recursive(User::class), true);

            if ($hasSpatieRoles) {
                $studentsQuery->role('Student');
            } elseif (Schema::hasColumn('users', 'role')) {
                $studentsQuery->whereRaw('LOWER(role) = ?', ['student']);
            }

            // Apply student name filter if provided
            if ($filters['student']) {
                $term = trim((string) $filters['student']);
                if ($term !== '') {
                    $studentsQuery->where('name', 'like', '%' . $term . '%');
                }
            }

            // Get students with their enrolled courses (if any)
            $students = $studentsQuery->orderBy('name')->limit(500)->get();

            // Build rows with student info and their courses
            $rows = $students->map(function ($student) use ($courses, $filters) {
                // Get student's enrolled courses
                $enrolledCourses = [];
                if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id')) {
                    $enrolledCourseIds = DB::table('enrollments')
                        ->where('student_id', $student->id)
                        ->pluck('course_id')
                        ->toArray();

                    $enrolledCourses = $courses->whereIn('id', $enrolledCourseIds)->pluck('name')->toArray();
                }

                return (object) [
                    'student_name' => $student->name,
                    'student_id' => $student->id,
                    'student_email' => $student->email,
                    'course_name' => !empty($enrolledCourses) ? implode(', ', $enrolledCourses) : 'Not enrolled in any course',
                    'semester' => '—',
                    'enrolled_at' => $student->created_at?->format('Y-m-d') ?? '—',
                ];
            });

            // Filter by course if selected
            if ($filters['course_id']) {
                $courseId = (int) $filters['course_id'];
                $rows = $rows->filter(function ($row) use ($courseId) {
                    // Check if this student is enrolled in the selected course
                    $isEnrolled = DB::table('enrollments')
                        ->where('student_id', $row->student_id)
                        ->where('course_id', $courseId)
                        ->exists();
                    return $isEnrolled;
                })->values();
            }
        } catch (\Throwable $e) {
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
