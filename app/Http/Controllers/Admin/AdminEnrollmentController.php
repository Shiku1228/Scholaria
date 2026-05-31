<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use App\Services\CourseChatGroupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminEnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $filterCourse = trim((string) $request->query('course_id', ''));
        $search       = trim((string) $request->query('search', ''));
        $fTeacherId   = (int) $request->query('teacher_id', 0);
        $fSemester    = trim((string) $request->query('semester', ''));
        $fStatus      = trim((string) $request->query('status', ''));

        $hasFilters       = $search !== '' || $fTeacherId > 0 || $fSemester !== '' || $fStatus !== '';
        $hasUserSno       = Schema::hasColumn('users', 'student_number');
        $hasStudentSno    = Schema::hasTable('students') && Schema::hasColumn('students', 'student_number');
        $hasStudentNumber = $hasUserSno || $hasStudentSno;

        $courses  = Course::query()->with('teacher')->orderBy('title')->get();
        $teachers = User::role('Teacher')->orderBy('name')->get();

        $selectedCourse     = null;
        $enrollments        = collect();
        $groupedEnrollments = null;
        $availableStudents  = collect();
        $totalActive        = 0;

        if ($filterCourse !== '') {
            $selectedCourse = $courses->firstWhere('id', (int) $filterCourse);
            if (! $selectedCourse) {
                $filterCourse = '';
            }
        }

        // Pre-fetch student IDs matching search by student_number
        $matchingStudentIds = [];
        if ($search !== '' && $hasStudentNumber) {
            try {
                $ids = [];
                // Primary: students.student_number is plain text — SQL LIKE works
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

        $baseQuery = Enrollment::query()
            ->with(['student.student', 'teacher', 'course'])
            ->orderByDesc('enrolled_at')
            ->orderByDesc('id');

        if ($filterCourse !== '') {
            $baseQuery->where('course_id', (int) $filterCourse);
        }
        if ($fTeacherId > 0) {
            $baseQuery->where('teacher_id', $fTeacherId);
        }
        if (in_array($fStatus, ['active', 'completed', 'dropped', 'unenrolled'], true)) {
            $baseQuery->where('status', $fStatus);
        }
        if (in_array($fSemester, ['first', 'second', 'summer'], true)) {
            $baseQuery->whereHas('course', fn ($q) => $q->where('semester', $fSemester));
        }
        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search, $matchingStudentIds) {
                $q->whereHas('student', function ($sq) use ($search, $matchingStudentIds) {
                    $sq->where('name', 'like', '%' . $search . '%')
                       ->orWhere('email', 'like', '%' . $search . '%');
                    if (! empty($matchingStudentIds)) {
                        $sq->orWhereIn('id', $matchingStudentIds);
                    }
                })
                ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', '%' . $search . '%')
                      ->orWhere('course_code', 'like', '%' . $search . '%')
                      ->orWhere('course_number', 'like', '%' . $search . '%'))
                ->orWhereHas('teacher', fn ($tq) => $tq->where('name', 'like', '%' . $search . '%'));
            });
        }

        if ($filterCourse !== '') {
            $enrollments = $baseQuery->paginate(25)->withQueryString();
            $totalActive = Enrollment::where('course_id', (int) $filterCourse)
                ->where('status', 'active')
                ->count();

            $enrolledIds = Enrollment::where('course_id', (int) $filterCourse)
                ->where('status', 'active')
                ->pluck('student_id')
                ->toArray();

            $availableStudents = User::role('Student')
                ->with('student')
                ->whereNotIn('id', $enrolledIds)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        } elseif ($hasFilters) {
            $allRows            = $baseQuery->limit(500)->get();
            $groupedEnrollments = $allRows->groupBy('course_id');
        }

        return view('admin.enrollments.index', [
            'courses'            => $courses,
            'teachers'           => $teachers,
            'selectedCourse'     => $selectedCourse,
            'enrollments'        => $enrollments,
            'groupedEnrollments' => $groupedEnrollments,
            'availableStudents'  => $availableStudents,
            'totalActive'        => $totalActive,
            'filterCourse'       => $filterCourse,
            'search'             => $search,
            'fTeacherId'         => $fTeacherId,
            'fSemester'          => $fSemester,
            'fStatus'            => $fStatus,
            'hasFilters'         => $hasFilters,
            'hasStudentNumber'   => $hasStudentNumber,
            'hasStudentSno'      => $hasStudentSno,
        ]);
    }

    public function create(): View
    {
        $students = User::role('Student')->orderBy('name')->get();
        $courses  = Course::query()->with('teacher')->orderBy('title')->get();

        return view('admin.enrollments.create', [
            'students' => $students,
            'courses'  => $courses,
        ]);
    }

    public function store(StoreEnrollmentRequest $request, CourseChatGroupService $chatService)
    {
        $validated = $request->validated();

        $course = Course::query()->with('teacher')->findOrFail((int) $validated['course_id']);
        if (! $course->teacher_id) {
            return back()->withErrors(['course_id' => 'Selected course does not have an assigned teacher.'])->withInput();
        }

        // Check for any existing enrollment record (active or not)
        $existing = Enrollment::query()
            ->where('student_id', (int) $validated['student_id'])
            ->where('course_id', (int) $validated['course_id'])
            ->first();

        if ($existing) {
            if ((string) $existing->status === 'active') {
                return back()->withErrors(['student_id' => 'This student is already actively enrolled in the selected course.'])->withInput();
            }
            return back()->withErrors(['student_id' => 'This student has an existing enrollment record for this course. Edit that record to reactivate them.'])->withInput();
        }

        $enrollment = Enrollment::create([
            'student_id'  => (int) $validated['student_id'],
            'course_id'   => (int) $validated['course_id'],
            'teacher_id'  => (int) $course->teacher_id,
            'status'      => 'active',
            'enrolled_at' => $validated['enrolled_at'] ?? now(),
        ]);

        $chatService->syncCourseMembers($course);
        $this->notifyStudentEnrollment($enrollment->student, $course, 'active', true);

        return redirect()->route('admin.enrollments.index', ['course_id' => $enrollment->course_id])
            ->with('success', 'Student enrolled successfully.');
    }

    public function edit(Enrollment $enrollment): View
    {
        $enrollment->load(['student.student', 'course.teacher', 'teacher']);

        $students = User::role('Student')->orderBy('name')->get();
        $courses  = Course::query()->with('teacher')->orderBy('title')->get();

        return view('admin.enrollments.edit', [
            'enrollment' => $enrollment,
            'students'   => $students,
            'courses'    => $courses,
        ]);
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment, CourseChatGroupService $chatService)
    {
        $oldCourseId  = (int) $enrollment->course_id;
        $oldStudentId = (int) $enrollment->student_id;
        $oldStatus    = (string) $enrollment->status;
        $validated    = $request->validated();

        $course = Course::query()->with('teacher')->findOrFail((int) $validated['course_id']);
        if (! $course->teacher_id) {
            return back()->withErrors(['course_id' => 'Selected course does not have an assigned teacher.'])->withInput();
        }

        $enrollment->update([
            'student_id'  => (int) $validated['student_id'],
            'course_id'   => (int) $validated['course_id'],
            'teacher_id'  => (int) $course->teacher_id,
            'status'      => (string) $validated['status'],
            'enrolled_at' => $validated['enrolled_at'] ?? $enrollment->enrolled_at,
        ]);

        if ($oldCourseId > 0 && $oldCourseId !== (int) $course->id) {
            $oldCourse = Course::query()->find($oldCourseId);
            if ($oldCourse) {
                $chatService->syncCourseMembers($oldCourse);
            }
        }
        $chatService->syncCourseMembers($course);

        $enrollment->load('student');
        $hasEnrollmentChanged = $oldCourseId !== (int) $enrollment->course_id
            || $oldStudentId !== (int) $enrollment->student_id
            || $oldStatus !== (string) $enrollment->status;

        if ($hasEnrollmentChanged) {
            $isNewRecord = $oldStudentId !== (int) $enrollment->student_id
                || $oldCourseId !== (int) $enrollment->course_id;
            $this->notifyStudentEnrollment($enrollment->student, $course, (string) $enrollment->status, $isNewRecord);
        }

        return redirect()->route('admin.enrollments.edit', $enrollment)->with('success', 'Enrollment updated.');
    }

    public function destroy(Enrollment $enrollment, CourseChatGroupService $chatService)
    {
        $course = $enrollment->course()->first();
        $enrollment->delete();
        if ($course) {
            $chatService->syncCourseMembers($course);
        }

        return redirect()->route('admin.enrollments.index')->with('success', 'Enrollment deleted.');
    }

    private function notifyStudentEnrollment(?User $student, Course $course, string $status, bool $isNewEnrollment): void
    {
        if (! $student) {
            return;
        }

        $courseName  = $this->courseDisplayName($course);
        $status      = Str::lower(trim($status));
        $statusLabel = $status !== '' ? $status : 'active';
        $url = $statusLabel === 'active'
            ? route('student.courses.show', $course)
            : route('student.courses.index');

        $title   = $isNewEnrollment ? 'Course Enrollment Added' : 'Course Enrollment Updated';
        $message = $isNewEnrollment
            ? 'You have been added to ' . $courseName . ' with ' . $statusLabel . ' status.'
            : 'Your enrollment in ' . $courseName . ' is now ' . $statusLabel . '.';

        $student->notify(new CourseEventNotification($title, $message, $url));
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
