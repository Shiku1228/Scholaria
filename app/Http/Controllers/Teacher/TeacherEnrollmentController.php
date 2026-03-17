<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeacherEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $rows = collect();
        $students = collect();
        $courses = collect();

        try {
            if (!Schema::hasTable('courses') || !Schema::hasTable('enrollments') || !Schema::hasTable('users')) {
                return view('teacher.enrollments.index', ['rows' => $rows, 'students' => $students, 'courses' => $courses]);
            }

            if (!Schema::hasColumn('courses', 'teacher_id') || !Schema::hasColumn('enrollments', 'course_id') || !Schema::hasColumn('enrollments', 'student_id')) {
                return view('teacher.enrollments.index', ['rows' => $rows, 'students' => $students, 'courses' => $courses]);
            }

            $courseNameCol = null;
            foreach (['course_number', 'title', 'name', 'course_name'] as $c) {
                if (Schema::hasColumn('courses', $c)) {
                    $courseNameCol = $c;
                    break;
                }
            }

            if ($courseNameCol === null || !Schema::hasColumn('users', 'name')) {
                return view('teacher.enrollments.index', ['rows' => $rows, 'students' => $students, 'courses' => $courses]);
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

            $studentsQuery = User::query();
            $hasSpatieRoles = in_array('Spatie\Permission\Traits\HasRoles', class_uses_recursive(User::class), true);

            if ($hasSpatieRoles) {
                $studentsQuery->role('Student');
            } elseif (Schema::hasColumn('users', 'role')) {
                $studentsQuery->whereRaw('LOWER(role) = ?', ['student']);
            } else {
                $studentsQuery->whereRaw('1 = 0');
            }

            if (Schema::hasColumn('users', 'status')) {
                $studentsQuery->whereRaw('LOWER(status) = ?', ['active']);
            }

            $students = $studentsQuery->orderBy('name')->get(['id', 'name', 'email']);
            $courses = Course::query()
                ->where('teacher_id', $teacherId)
                ->orderBy('title')
                ->get(['id', 'course_number', 'title']);
        } catch (\Throwable) {
            $rows = collect();
            $students = collect();
            $courses = collect();
        }

        return view('teacher.enrollments.index', [
            'rows' => $rows,
            'students' => $students,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'status' => ['required', Rule::in(['active', 'completed', 'dropped'])],
            'enrolled_at' => ['nullable', 'date'],
        ]);

        $course = Course::query()
            ->where('id', (int) $validated['course_id'])
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$course) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'You can only enroll students to courses assigned to you.');
        }

        $student = User::query()->find((int) $validated['student_id']);
        if (!$student) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'Selected user is not a student.');
        }

        $isStudent = false;
        if (method_exists($student, 'hasRole')) {
            $isStudent = $student->hasRole('Student');
        }
        if (!$isStudent && Schema::hasColumn('users', 'role')) {
            $isStudent = Str::lower((string) ($student->role ?? '')) === 'student';
        }

        if (!$isStudent) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'Selected user is not a student.');
        }

        if (Schema::hasColumn('users', 'status')) {
            $isActive = Str::lower((string) ($student->status ?? '')) === 'active';
            if (!$isActive) {
                return redirect()
                    ->route('teacher.enrollments.index')
                    ->with('error', 'Only active students can be enrolled.');
            }
        }

        $exists = Enrollment::query()
            ->where('student_id', (int) $validated['student_id'])
            ->where('course_id', (int) $validated['course_id'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'This student is already enrolled in the selected course.');
        }

        $payload = [
            'student_id' => (int) $validated['student_id'],
            'course_id' => (int) $validated['course_id'],
        ];

        if (Schema::hasColumn('enrollments', 'teacher_id')) {
            $payload['teacher_id'] = $teacherId;
        }
        if (Schema::hasColumn('enrollments', 'status')) {
            $payload['status'] = (string) $validated['status'];
        }
        if (Schema::hasColumn('enrollments', 'enrolled_at')) {
            $payload['enrolled_at'] = $validated['enrolled_at'] ?? now();
        }

        Enrollment::query()->create($payload);

        return redirect()
            ->route('teacher.enrollments.index')
            ->with('success', 'Student enrollment added successfully.');
    }
}
