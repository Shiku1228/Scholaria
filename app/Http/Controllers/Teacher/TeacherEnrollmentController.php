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
use Illuminate\View\View;

class TeacherEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId    = (int) $request->user()->id;
        $filterCourse = trim((string) $request->input('course_id', ''));
        $search       = trim((string) $request->input('search', ''));
        $filterStatus = trim((string) $request->input('status', ''));

        $hasUserSno       = Schema::hasColumn('users', 'student_number');
        $hasStudentSno    = Schema::hasTable('students') && Schema::hasColumn('students', 'student_number');
        $hasStudentNumber = $hasUserSno || $hasStudentSno;
        $hasCompletedAt   = Schema::hasColumn('enrollments', 'completed_at');
        $hasDroppedAt     = Schema::hasColumn('enrollments', 'dropped_at');
        $hasUnenrolledAt  = Schema::hasColumn('enrollments', 'unenrolled_at');

        $courses = collect();
        try {
            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $cols = ['id', 'course_number', 'title', 'semester', 'school_year', 'days_pattern', 'start_time', 'end_time', 'teacher_id'];
                if (Schema::hasColumn('courses', 'course_code')) {
                    $cols[] = 'course_code';
                }
                $courses = Course::query()
                    ->where('teacher_id', $teacherId)
                    ->orderBy('title')
                    ->get($cols);
            }
        } catch (\Throwable) {
            $courses = collect();
        }

        $selectedCourse    = null;
        $activeRows        = collect();
        $completedRows     = collect();
        $droppedRows       = collect();
        $unenrolledRows    = collect();
        $availableStudents = collect();
        $studentNumberMap  = collect();
        $totalActive       = 0;

        if ($filterCourse !== '') {
            $selectedCourse = $courses->firstWhere('id', (int) $filterCourse);
            if (! $selectedCourse) {
                $filterCourse = '';
            }
        }

        if ($filterCourse !== '') {
            try {
                // Pre-fetch student IDs matching search by student_number
                $matchingStudentIds = [];
                if ($search !== '' && $hasStudentNumber) {
                    try {
                        $ids = [];
                        // Primary: students table student_number is plain text — SQL LIKE works
                        if ($hasStudentSno) {
                            $ids = User::role('Student')
                                ->whereHas('student', fn ($q) => $q->where('student_number', 'like', '%' . $search . '%'))
                                ->pluck('id')
                                ->toArray();
                        }
                        // Fallback: users.student_number is encrypted — must decrypt via Eloquent
                        if ($hasUserSno) {
                            $encMatches = User::role('Student')
                                ->whereNotNull('student_number')
                                ->get(['id', 'student_number'])
                                ->filter(fn ($u) => $u->student_number !== null
                                    && str_contains(strtolower((string) $u->student_number), strtolower($search)))
                                ->pluck('id')
                                ->toArray();
                            $ids = array_unique(array_merge($ids, $encMatches));
                        }
                        $matchingStudentIds = $ids;
                    } catch (\Throwable) {
                        $matchingStudentIds = [];
                    }
                }

                $selectCols = [
                    'enrollments.id as enrollment_id',
                    'users.id as student_id',
                    'users.name as student_name',
                    'users.email as student_email',
                    'enrollments.status',
                    'enrollments.enrolled_at',
                ];
                if ($hasCompletedAt)  $selectCols[] = 'enrollments.completed_at';
                if ($hasDroppedAt)    $selectCols[] = 'enrollments.dropped_at';
                if ($hasUnenrolledAt) $selectCols[] = 'enrollments.unenrolled_at';

                $baseQuery = DB::table('enrollments')
                    ->join('users', 'users.id', '=', 'enrollments.student_id')
                    ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                    ->where('enrollments.course_id', (int) $filterCourse)
                    ->where('courses.teacher_id', $teacherId)
                    ->select($selectCols);

                if ($search !== '') {
                    $baseQuery->where(function ($q) use ($search, $matchingStudentIds) {
                        $q->where('users.name', 'like', '%' . $search . '%')
                            ->orWhere('users.email', 'like', '%' . $search . '%');
                        if (! empty($matchingStudentIds)) {
                            $q->orWhereIn('users.id', $matchingStudentIds);
                        }
                    });
                }

                $allRows = $baseQuery->orderBy('users.name')->get();

                $activeRows     = $allRows->where('status', 'active')->values();
                $completedRows  = $allRows->where('status', 'completed')->values();
                $droppedRows    = $allRows->where('status', 'dropped')->values();
                $unenrolledRows = $allRows->where('status', 'unenrolled')->values();
                $totalActive    = $activeRows->count();

                // Build student_number map: prefer students table (plain text), fallback to users.student_number
                if ($hasStudentNumber && $allRows->isNotEmpty()) {
                    $sIds = $allRows->pluck('student_id')->unique()->toArray();
                    $usersWithProfile = User::whereIn('id', $sIds)->with('student')->get(['id', 'student_number']);
                    $studentNumberMap = $usersWithProfile->mapWithKeys(function ($u) use ($hasStudentSno) {
                        $sno = null;
                        if ($hasStudentSno && $u->student && !empty($u->student->student_number)) {
                            $sno = $u->student->student_number;
                        }
                        if (!$sno) {
                            try { $sno = $u->student_number ?: null; } catch (\Throwable) {}
                        }
                        return [$u->id => $sno];
                    });
                }

                // Available students: not actively enrolled + exclude from dropdown
                $enrolledStudentIds = DB::table('enrollments')
                    ->where('course_id', (int) $filterCourse)
                    ->where('status', 'active')
                    ->pluck('student_id')
                    ->toArray();

                $studentsQuery  = User::query();
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

                $availableStudents = $studentsQuery
                    ->with('student')
                    ->whereNotIn('id', $enrolledStudentIds)
                    ->orderBy('name')
                    ->get(['id', 'name', 'email']);

            } catch (\Throwable) {
                $activeRows = $completedRows = $droppedRows = $unenrolledRows = collect();
                $availableStudents = collect();
                $studentNumberMap  = collect();
            }
        }

        return view('teacher.enrollments.index', compact(
            'courses', 'filterCourse', 'search', 'filterStatus',
            'selectedCourse', 'activeRows', 'completedRows', 'droppedRows', 'unenrolledRows',
            'availableStudents', 'totalActive', 'studentNumberMap', 'hasStudentNumber', 'hasStudentSno'
        ));
    }

    public function store(Request $request, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id'  => ['required', 'integer', 'exists:courses,id'],
        ]);

        $courseId = (int) $validated['course_id'];

        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->first();

        if (! $course) {
            return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
                ->with('error', 'You can only enroll students into courses assigned to you.');
        }

        $student = User::query()->find((int) $validated['student_id']);
        if (! $student) {
            return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
                ->with('error', 'Selected student was not found.');
        }

        $isStudent = method_exists($student, 'hasRole') && $student->hasRole('Student');
        if (! $isStudent && Schema::hasColumn('users', 'role')) {
            $isStudent = Str::lower((string) ($student->role ?? '')) === 'student';
        }
        if (! $isStudent) {
            return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
                ->with('error', 'Selected user is not a student.');
        }

        $existing = Enrollment::query()
            ->where('student_id', (int) $validated['student_id'])
            ->where('course_id', $courseId)
            ->first();

        if ($existing) {
            if ((string) $existing->status === 'active') {
                return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
                    ->with('error', 'This student is already enrolled in this course.');
            }
            return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
                ->with('error', 'This student has an existing record for this course. Use the Re-enroll button to reactivate them.');
        }

        $payload = [
            'student_id'  => (int) $validated['student_id'],
            'course_id'   => $courseId,
            'status'      => 'active',
            'enrolled_at' => now(),
        ];
        if (Schema::hasColumn('enrollments', 'teacher_id')) {
            $payload['teacher_id'] = $teacherId;
        }

        Enrollment::query()->create($payload);
        $chatService->syncCourseMembers($course);
        $this->notifyStudent($student, $course, 'active');

        return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
            ->with('success', 'Student enrolled successfully.');
    }

    public function complete(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;
        $course    = $this->authorizeEnrollment($enrollment, $teacherId);

        if (! $course) {
            return redirect()->route('teacher.enrollments.index')
                ->with('error', 'You can only manage enrollments for courses assigned to you.');
        }

        $data = ['status' => 'completed'];
        if (Schema::hasColumn('enrollments', 'completed_at')) {
            $data['completed_at'] = now();
        }
        $enrollment->update($data);
        $chatService->syncCourseMembers($course);

        return redirect()->route('teacher.enrollments.index', ['course_id' => $enrollment->course_id])
            ->with('success', 'Student marked as completed.');
    }

    public function drop(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;
        $course    = $this->authorizeEnrollment($enrollment, $teacherId);

        if (! $course) {
            return redirect()->route('teacher.enrollments.index')
                ->with('error', 'You can only manage enrollments for courses assigned to you.');
        }

        $data = ['status' => 'dropped'];
        if (Schema::hasColumn('enrollments', 'dropped_at')) {
            $data['dropped_at'] = now();
        }
        $enrollment->update($data);
        $chatService->syncCourseMembers($course);

        return redirect()->route('teacher.enrollments.index', ['course_id' => $enrollment->course_id])
            ->with('success', 'Student dropped from the course.');
    }

    public function unenroll(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;
        $course    = $this->authorizeEnrollment($enrollment, $teacherId);

        if (! $course) {
            return redirect()->route('teacher.enrollments.index')
                ->with('error', 'You can only manage enrollments for courses assigned to you.');
        }

        $data = ['status' => 'unenrolled'];
        if (Schema::hasColumn('enrollments', 'unenrolled_at')) {
            $data['unenrolled_at'] = now();
        }
        $enrollment->update($data);
        $chatService->syncCourseMembers($course);

        return redirect()->route('teacher.enrollments.index', ['course_id' => $enrollment->course_id])
            ->with('success', 'Student unenrolled from the course.');
    }

    public function reenroll(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;
        $course    = $this->authorizeEnrollment($enrollment, $teacherId);

        if (! $course) {
            return redirect()->route('teacher.enrollments.index')
                ->with('error', 'You can only manage enrollments for courses assigned to you.');
        }

        if ((string) $enrollment->status === 'active') {
            return redirect()->route('teacher.enrollments.index', ['course_id' => $enrollment->course_id])
                ->with('error', 'Student is already active in this course.');
        }

        $data = [
            'status'      => 'active',
            'enrolled_at' => now(),
        ];
        if (Schema::hasColumn('enrollments', 'completed_at'))  $data['completed_at']  = null;
        if (Schema::hasColumn('enrollments', 'dropped_at'))    $data['dropped_at']    = null;
        if (Schema::hasColumn('enrollments', 'unenrolled_at')) $data['unenrolled_at'] = null;

        $enrollment->update($data);
        $chatService->syncCourseMembers($course);

        if ($enrollment->student) {
            $this->notifyStudent($enrollment->student, $course, 'active');
        }

        return redirect()->route('teacher.enrollments.index', ['course_id' => $enrollment->course_id])
            ->with('success', 'Student re-enrolled successfully.');
    }

    public function destroy(Request $request, Enrollment $enrollment, CourseChatGroupService $chatService): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;
        $courseId  = (int) $enrollment->course_id;
        $course    = $this->authorizeEnrollment($enrollment, $teacherId);

        if (! $course) {
            return redirect()->route('teacher.enrollments.index')
                ->with('error', 'You can only manage enrollments for courses assigned to you.');
        }

        if ((string) $enrollment->status === 'active') {
            return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
                ->with('error', 'Cannot delete an active enrollment. Use Drop or Unenroll first.');
        }

        $enrollment->delete();
        $chatService->syncCourseMembers($course);

        return redirect()->route('teacher.enrollments.index', ['course_id' => $courseId])
            ->with('success', 'Enrollment record deleted.');
    }

    private function authorizeEnrollment(Enrollment $enrollment, int $teacherId): ?Course
    {
        return Course::query()
            ->where('id', (int) $enrollment->course_id)
            ->where('teacher_id', $teacherId)
            ->first();
    }

    private function notifyStudent(User $student, Course $course, string $status): void
    {
        $name = $this->courseDisplayName($course);
        $url  = $status === 'active'
            ? route('student.courses.show', $course)
            : route('student.courses.index');

        $student->notify(new CourseEventNotification(
            'Course Enrollment Updated',
            'You have been added to ' . $name . ' with ' . $status . ' status.',
            $url
        ));
    }

    private function courseDisplayName(Course $course): string
    {
        $code   = trim((string) ($course->course_code ?? ''));
        $number = trim((string) ($course->course_number ?? ''));
        $title  = trim((string) ($course->title ?? ''));

        if ($code !== '' && $title !== '') {
            return $code . ' — ' . $title;
        }

        return $title !== '' ? $title : ($number !== '' ? $number : 'your course');
    }
}
