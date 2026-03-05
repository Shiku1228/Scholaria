<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->query('student_id', 0);
        $courseId = (int) $request->query('course_id', 0);
        $teacherId = (int) $request->query('teacher_id', 0);
        $semester = trim((string) $request->query('semester', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Enrollment::query()
            ->with(['student', 'teacher', 'course'])
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id');

        if ($studentId > 0) {
            $query->where('student_id', $studentId);
        }
        if ($courseId > 0) {
            $query->where('course_id', $courseId);
        }
        if ($teacherId > 0) {
            $query->where('teacher_id', $teacherId);
        }
        if (in_array($status, ['active', 'completed', 'dropped'], true)) {
            $query->where('status', $status);
        }
        if (in_array($semester, ['first', 'second', 'summer'], true)) {
            $query->whereHas('course', fn ($q) => $q->where('semester', $semester));
        }

        $enrollments = $query->paginate(15)->withQueryString();

        $students = User::role('Student')->orderBy('name')->get();
        $teachers = User::role('Teacher')->orderBy('name')->get();
        $courses = Course::query()->with('teacher')->orderByDesc('id')->get();

        return view('admin.enrollments.index', [
            'enrollments' => $enrollments,
            'students' => $students,
            'teachers' => $teachers,
            'courses' => $courses,
            'filters' => [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'teacher_id' => $teacherId,
                'semester' => $semester,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        $students = User::role('Student')->orderBy('name')->get();
        $courses = Course::query()->with('teacher')->orderBy('title')->get();

        return view('admin.enrollments.create', [
            'students' => $students,
            'courses' => $courses,
        ]);
    }

    public function store(StoreEnrollmentRequest $request)
    {
        $validated = $request->validated();

        $course = Course::query()->with('teacher')->findOrFail((int) $validated['course_id']);
        if (!$course->teacher_id) {
            return back()->withErrors(['course_id' => 'Selected course does not have an assigned teacher.'])->withInput();
        }

        $enrollment = Enrollment::create([
            'student_id' => (int) $validated['student_id'],
            'course_id' => (int) $validated['course_id'],
            'teacher_id' => (int) $course->teacher_id,
            'status' => (string) $validated['status'],
            'enrolled_at' => $validated['enrolled_at'] ?? now(),
        ]);

        return redirect()->route('admin.enrollments.edit', $enrollment)->with('success', 'Enrollment created.');
    }

    public function edit(Enrollment $enrollment): View
    {
        $enrollment->load(['student', 'course.teacher', 'teacher']);

        $students = User::role('Student')->orderBy('name')->get();
        $courses = Course::query()->with('teacher')->orderBy('title')->get();

        return view('admin.enrollments.edit', [
            'enrollment' => $enrollment,
            'students' => $students,
            'courses' => $courses,
        ]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment)
    {
        $validated = $request->validated();

        $course = Course::query()->with('teacher')->findOrFail((int) $validated['course_id']);
        if (!$course->teacher_id) {
            return back()->withErrors(['course_id' => 'Selected course does not have an assigned teacher.'])->withInput();
        }

        $enrollment->update([
            'student_id' => (int) $validated['student_id'],
            'course_id' => (int) $validated['course_id'],
            'teacher_id' => (int) $course->teacher_id,
            'status' => (string) $validated['status'],
            'enrolled_at' => $validated['enrolled_at'] ?? $enrollment->enrolled_at,
        ]);

        return redirect()->route('admin.enrollments.edit', $enrollment)->with('success', 'Enrollment updated.');
    }

    public function destroy(Enrollment $enrollment)
    {
        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')->with('success', 'Enrollment deleted.');
    }
}
