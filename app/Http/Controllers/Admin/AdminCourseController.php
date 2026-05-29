<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use App\Services\CourseChatGroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminCourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $semester = trim((string) $request->query('semester', ''));

        $query = Course::query()->with('teacher')->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('course_number', 'like', $search . '%')
                    ->orWhere('title', 'like', $search . '%');
            });
        }

        if (in_array($semester, ['first', 'second', 'summer'], true)) {
            $query->where('semester', $semester);
        }

        $courses = $query->paginate(15)->withQueryString();

        return view('admin.courses.index', [
            'courses' => $courses,
            'filters' => [
                'search' => $search,
                'semester' => $semester,
            ],
        ]);
    }

    public function create(): View
    {
        $teachers = $this->getTeacherOptions();

        return view('admin.courses.create', [
            'teachers' => $teachers,
        ]);
    }

    public function store(StoreCourseRequest $request, CourseChatGroupService $chatService)
    {
        $validated = $request->validated();

        $course = Course::create([
            'course_number' => $validated['course_number'],
            'course_code' => $validated['course_code'],
            'title' => $validated['course_title'],
            'description' => $validated['course_description'] ?? null,
            'semester' => $validated['semester'],
            'school_year' => $validated['school_year'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['class_time_start'] ?? null,
            'end_time' => $validated['class_time_end'] ?? null,
            'days_pattern' => isset($validated['class_days']) ? implode(',', $validated['class_days']) : null,
            'teacher_id' => $validated['teacher_id'],
        ]);

        $chatService->syncCourseMembers($course);
        $this->notifyAssignedTeacher($course);

        return redirect()->route('admin.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $teachers = $this->getTeacherOptions();

        return view('admin.courses.edit', [
            'course' => $course,
            'teachers' => $teachers,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course, CourseChatGroupService $chatService)
    {
        $validated = $request->validated();

        $oldTeacherId = (int) $course->teacher_id;

        $course->update([
            'course_number' => $validated['course_number'],
            'course_code' => $validated['course_code'],
            'title' => $validated['course_title'],
            'description' => $validated['course_description'] ?? null,
            'semester' => $validated['semester'],
            'school_year' => $validated['school_year'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['class_time_start'] ?? null,
            'end_time' => $validated['class_time_end'] ?? null,
            'days_pattern' => isset($validated['class_days']) ? implode(',', $validated['class_days']) : null,
            'teacher_id' => $validated['teacher_id'],
        ]);

        $course = $course->fresh();
        $chatService->syncCourseMembers($course);

        if ($oldTeacherId !== (int) $course->teacher_id) {
            $this->notifyAssignedTeacher($course);
        }

        return redirect()->route('admin.courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course)
    {
        DB::transaction(function () use ($course): void {
            $courseId = (int) $course->id;

            if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                $assignmentIds = DB::table('assignments')->where('course_id', $courseId)->pluck('id');

                if ($assignmentIds->isNotEmpty()) {
                    if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'assignment_id')) {
                        DB::table('grades')->whereIn('assignment_id', $assignmentIds->all())->delete();
                    }

                    if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id')) {
                        DB::table('submissions')->whereIn('assignment_id', $assignmentIds->all())->delete();
                    }
                }

                DB::table('assignments')->where('course_id', $courseId)->delete();
            }

            if (Schema::hasTable('quizzes') && Schema::hasColumn('quizzes', 'course_id')) {
                $quizIds = DB::table('quizzes')->where('course_id', $courseId)->pluck('id');

                if ($quizIds->isNotEmpty()) {
                    if (Schema::hasTable('quiz_attempts') && Schema::hasColumn('quiz_attempts', 'quiz_id')) {
                        $quizAttemptIds = DB::table('quiz_attempts')->whereIn('quiz_id', $quizIds->all())->pluck('id');
                        if ($quizAttemptIds->isNotEmpty() && Schema::hasTable('quiz_answers') && Schema::hasColumn('quiz_answers', 'attempt_id')) {
                            DB::table('quiz_answers')->whereIn('attempt_id', $quizAttemptIds->all())->delete();
                        }
                        DB::table('quiz_attempts')->whereIn('quiz_id', $quizIds->all())->delete();
                    }

                    if (Schema::hasTable('quiz_questions') && Schema::hasColumn('quiz_questions', 'quiz_id')) {
                        DB::table('quiz_questions')->whereIn('quiz_id', $quizIds->all())->delete();
                    }
                }

                DB::table('quizzes')->where('course_id', $courseId)->delete();
            }

            if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'course_id')) {
                $examIds = DB::table('exams')->where('course_id', $courseId)->pluck('id');

                if ($examIds->isNotEmpty()) {
                    if (Schema::hasTable('student_exam_attempts') && Schema::hasColumn('student_exam_attempts', 'exam_id')) {
                        $examAttemptIds = DB::table('student_exam_attempts')->whereIn('exam_id', $examIds->all())->pluck('id');
                        if ($examAttemptIds->isNotEmpty() && Schema::hasTable('exam_answers') && Schema::hasColumn('exam_answers', 'attempt_id')) {
                            DB::table('exam_answers')->whereIn('attempt_id', $examAttemptIds->all())->delete();
                        }
                        DB::table('student_exam_attempts')->whereIn('exam_id', $examIds->all())->delete();
                    }

                    if (Schema::hasTable('exam_questions') && Schema::hasColumn('exam_questions', 'exam_id')) {
                        DB::table('exam_questions')->whereIn('exam_id', $examIds->all())->delete();
                    }
                }

                DB::table('exams')->where('course_id', $courseId)->delete();
            }

            if (Schema::hasTable('announcements') && Schema::hasColumn('announcements', 'course_id')) {
                DB::table('announcements')->where('course_id', $courseId)->delete();
            }

            if (Schema::hasTable('course_resources') && Schema::hasColumn('course_resources', 'course_id')) {
                DB::table('course_resources')->where('course_id', $courseId)->delete();
            }

            if (Schema::hasTable('course_discussions') && Schema::hasColumn('course_discussions', 'course_id')) {
                DB::table('course_discussions')->where('course_id', $courseId)->delete();
            }

            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id')) {
                DB::table('enrollments')->where('course_id', $courseId)->delete();
            }

            $course->delete();
        });

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }

    public function checkNumber(Request $request): JsonResponse
    {
        $courseNumber = strtoupper(trim((string) $request->query('course_number', '')));
        $ignoreId = (int) $request->query('ignore_id', 0);

        if ($courseNumber === '') {
            return response()->json(['exists' => false]);
        }

        $query = Course::query()->where('course_number', $courseNumber);
        if ($ignoreId > 0) {
            $query->where('id', '!=', $ignoreId);
        }

        return response()->json(['exists' => $query->exists()]);
    }

    private function getTeacherOptions()
    {
        return User::query()
            ->where(function ($query) {
                $query->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Teacher');
                });

                if (Schema::hasColumn('users', 'role')) {
                    $query->orWhere('role', 'teacher');
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function notifyAssignedTeacher(Course $course): void
    {
        $teacher = User::query()->find((int) $course->teacher_id);
        if (!$teacher) {
            return;
        }

        $courseName = $this->courseDisplayName($course);

        $teacher->notify(new CourseEventNotification(
            'New Course Assigned',
            'You have been assigned to ' . $courseName . '.',
            route('teacher.courses.show', $course)
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
