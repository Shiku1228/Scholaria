<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use App\Services\CourseChatGroupService;
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
        $unenrolledRows = collect();
        $students = collect();
        $courses = collect();

        try {
            if (!Schema::hasTable('courses') || !Schema::hasTable('enrollments') || !Schema::hasTable('users')) {
                return view('teacher.enrollments.index', ['rows' => $rows, 'unenrolledRows' => $unenrolledRows, 'students' => $students, 'courses' => $courses]);
            }

            if (!Schema::hasColumn('courses', 'teacher_id') || !Schema::hasColumn('enrollments', 'course_id') || !Schema::hasColumn('enrollments', 'student_id')) {
                return view('teacher.enrollments.index', ['rows' => $rows, 'unenrolledRows' => $unenrolledRows, 'students' => $students, 'courses' => $courses]);
            }

            $courseNameCol = null;
            foreach (['course_number', 'title', 'name', 'course_name'] as $c) {
                if (Schema::hasColumn('courses', $c)) {
                    $courseNameCol = $c;
                    break;
                }
            }

            if ($courseNameCol === null || !Schema::hasColumn('users', 'name')) {
                return view('teacher.enrollments.index', ['rows' => $rows, 'unenrolledRows' => $unenrolledRows, 'students' => $students, 'courses' => $courses]);
            }

            $select = [
                'enrollments.id as enrollment_id',
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

            if (Schema::hasColumn('enrollments', 'updated_at')) {
                $select[] = 'enrollments.updated_at as unenrolled_at';
            }

            $baseQuery = DB::table('enrollments')
                ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                ->join('users', 'users.id', '=', 'enrollments.student_id')
                ->where('courses.teacher_id', $teacherId)
                ->select($select);

            $rows = (clone $baseQuery)
                ->when(Schema::hasColumn('enrollments', 'status'), fn ($query) => $query->where('enrollments.status', '!=', 'dropped'))
                ->orderByDesc(Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrollments.enrolled_at' : 'enrollments.created_at')
                ->limit(500)
                ->get();

            $unenrolledRows = collect();
            if (Schema::hasColumn('enrollments', 'status')) {
                $unenrolledRows = (clone $baseQuery)
                    ->where('enrollments.status', 'dropped')
                    ->orderByDesc(Schema::hasColumn('enrollments', 'updated_at') ? 'enrollments.updated_at' : 'enrollments.created_at')
                ->limit(500)
                ->get();
            }

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
            'unenrolledRows' => $unenrolledRows,
            'students' => $students,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request, CourseChatGroupService $chatService): RedirectResponse
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

        $existingEnrollment = Enrollment::query()
            ->where('student_id', (int) $validated['student_id'])
            ->where('course_id', (int) $validated['course_id'])
            ->first();

        if ($existingEnrollment) {
            if ((string) $existingEnrollment->status !== 'dropped') {
                return redirect()
                    ->route('teacher.enrollments.index')
                    ->with('error', 'This student is already enrolled in the selected course.');
            }

            $existingEnrollment->update([
                'teacher_id' => Schema::hasColumn('enrollments', 'teacher_id') ? $teacherId : $existingEnrollment->teacher_id,
                'status' => (string) $validated['status'],
                'enrolled_at' => Schema::hasColumn('enrollments', 'enrolled_at') ? ($validated['enrolled_at'] ?? now()) : $existingEnrollment->enrolled_at,
            ]);

            $chatService->syncCourseMembers($course);
            $this->notifyStudentEnrollment($student, $course, (string) $existingEnrollment->fresh()->status);

            return redirect()
                ->route('teacher.enrollments.index')
                ->with('success', 'Student enrollment restored successfully.');
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

        $enrollment = Enrollment::query()->create($payload);
        $chatService->syncCourseMembers($course);
        $this->notifyStudentEnrollment($student, $course, (string) ($enrollment->status ?? $validated['status']));

        return redirect()
            ->route('teacher.enrollments.index')
            ->with('success', 'Student enrollment added successfully.');
    }

    public function unenroll(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;

        $course = Course::query()
            ->where('id', (int) $enrollment->course_id)
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$course) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'You can only unenroll students from courses assigned to you.');
        }

        $enrollment->update(['status' => 'dropped']);
        $chatService->syncCourseMembers($course);

        return redirect()
            ->route('teacher.enrollments.index')
            ->with('success', 'Student unenrolled successfully.');
    }

    public function destroy(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;

        $course = Course::query()
            ->where('id', (int) $enrollment->course_id)
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$course) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'You can only delete unenrolled records from courses assigned to you.');
        }

        if ((string) $enrollment->status !== 'dropped') {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'Only unenrolled records can be deleted.');
        }

        $enrollment->delete();
        $chatService->syncCourseMembers($course);

        return redirect()
            ->route('teacher.enrollments.index')
            ->with('success', 'Unenrolled student record deleted successfully.');
    }

    public function reenroll(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;

        $course = Course::query()
            ->where('id', (int) $enrollment->course_id)
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$course) {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'You can only re-enroll students to courses assigned to you.');
        }

        if ((string) $enrollment->status !== 'dropped') {
            return redirect()
                ->route('teacher.enrollments.index')
                ->with('error', 'Only unenrolled records can be re-enrolled.');
        }

        $enrollment->update([
            'status' => 'active',
            'enrolled_at' => Schema::hasColumn('enrollments', 'enrolled_at') ? now() : $enrollment->enrolled_at,
        ]);

        $chatService->syncCourseMembers($course);

        if ($enrollment->student) {
            $this->notifyStudentEnrollment($enrollment->student, $course, 'active');
        }

        return redirect()
            ->route('teacher.enrollments.index')
            ->with('success', 'Student re-enrolled successfully.');
    }

    private function notifyStudentEnrollment(User $student, Course $course, string $status): void
    {
        $courseName = $this->courseDisplayName($course);
        $status = Str::lower(trim($status));
        $statusLabel = $status !== '' ? $status : 'active';
        $url = $statusLabel === 'active'
            ? route('student.courses.show', $course)
            : route('student.courses.index');

        $student->notify(new CourseEventNotification(
            'Course Enrollment Updated',
            'You have been added to ' . $courseName . ' with ' . $statusLabel . ' status.',
            $url
        ));
    }

    private function courseDisplayName(Course $course): string
    {
        $number = trim((string) ($course->course_number ?? ''));
        $title = trim((string) ($course->title ?? ''));

        if ($number !== '' && $title !== '') {
            return $number . ' - ' . $title;
        }

        return $title !== '' ? $title : ($number !== '' ? $number : 'your course');
    }
}
