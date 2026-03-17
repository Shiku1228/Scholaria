<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDiscussion;
use App\Models\CourseResource;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentCourseController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;

        $courses = [];

        try {
            if (Schema::hasTable('enrollments') && Schema::hasTable('courses') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
                $courseNameColumn = null;
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameColumn = $candidate;
                        break;
                    }
                }

                if ($courseNameColumn) {
                    $query = DB::table('enrollments')
                        ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                        ->where('enrollments.student_id', $studentId);

                    if (Schema::hasColumn('enrollments', 'status')) {
                        $query->whereRaw('LOWER(enrollments.status) = ?', ['active']);
                    }

                    $hasTeacherJoin = Schema::hasColumn('courses', 'teacher_id')
                        && Schema::hasTable('users')
                        && Schema::hasColumn('users', 'id')
                        && Schema::hasColumn('users', 'name');

                    if ($hasTeacherJoin) {
                        $query->leftJoin('users', 'users.id', '=', 'courses.teacher_id');
                    }

                    $select = [
                        'courses.id as course_id',
                        'courses.' . $courseNameColumn . ' as course_name',
                    ];

                    foreach (['course_number', 'semester', 'school_year', 'cover_image'] as $column) {
                        if (Schema::hasColumn('courses', $column)) {
                            $select[] = 'courses.' . $column . ' as ' . $column;
                        }
                    }

                    if ($hasTeacherJoin) {
                        $select[] = 'users.name as teacher_name';
                    }

                    if (Schema::hasColumn('enrollments', 'status')) {
                        $select[] = 'enrollments.status as enrollment_status';
                    }

                    $rows = $query
                        ->select($select)
                        ->orderBy('courses.id', 'desc')
                        ->get();

                    $courseIds = $rows->pluck('course_id')->map(fn ($v) => (int) $v)->filter()->values()->all();

                    $assignmentTotals = [];
                    $submissionTotals = [];

                    if (!empty($courseIds) && Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                        $totals = DB::table('assignments')
                            ->whereIn('course_id', $courseIds)
                            ->select(['course_id', DB::raw('COUNT(*) as c')])
                            ->groupBy('course_id')
                            ->get();

                        foreach ($totals as $t) {
                            $assignmentTotals[(int) $t->course_id] = (int) ($t->c ?? 0);
                        }

                        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                            $submitted = DB::table('submissions')
                                ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                ->where('submissions.student_id', $studentId)
                                ->whereIn('assignments.course_id', $courseIds)
                                ->select(['assignments.course_id as course_id', DB::raw('COUNT(*) as c')])
                                ->groupBy('assignments.course_id')
                                ->get();

                            foreach ($submitted as $s) {
                                $submissionTotals[(int) $s->course_id] = (int) ($s->c ?? 0);
                            }
                        }
                    }

                    $courses = $rows->map(function ($r) use ($assignmentTotals, $submissionTotals) {
                        $courseId = (int) ($r->course_id ?? 0);
                        $total = (int) ($assignmentTotals[$courseId] ?? 0);
                        $done = (int) ($submissionTotals[$courseId] ?? 0);
                        $progress = $total > 0 ? (int) round(min(100, max(0, ($done / $total) * 100))) : 0;

                        return [
                            'course_id' => $courseId,
                            'course_name' => (string) ($r->course_name ?? ''),
                            'course_number' => (string) ($r->course_number ?? ''),
                            'semester' => (string) ($r->semester ?? ''),
                            'school_year' => (string) ($r->school_year ?? ''),
                            'cover_image' => (string) ($r->cover_image ?? ''),
                            'teacher_name' => (string) ($r->teacher_name ?? ''),
                            'enrollment_status' => (string) ($r->enrollment_status ?? ''),
                            'progress' => $progress,
                            'assignments_total' => $total,
                            'assignments_submitted' => $done,
                        ];
                    })->values()->all();
                }
            }
        } catch (\Throwable) {
        }

        return view('student.courses.index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $course->id;

        $isEnrolled = false;
        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
            $enrollmentQuery = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId);
            if (Schema::hasColumn('enrollments', 'status')) {
                $enrollmentQuery->whereRaw('LOWER(status) = ?', ['active']);
            }
            $isEnrolled = $enrollmentQuery->exists();
        }

        if (!$isEnrolled) {
            abort(403);
        }

        $resources = collect();
        $discussions = collect();
        $assignments = collect();
        $completedAssignments = 0;

        try {
            if (Schema::hasTable('course_resources')) {
                $resources = CourseResource::query()
                    ->where('course_id', $courseId)
                    ->latest('id')
                    ->limit(200)
                    ->get();
            }

            if (Schema::hasTable('course_discussions')) {
                $discussions = CourseDiscussion::query()
                    ->with([
                        'user:id,name',
                        'replies.user:id,name',
                    ])
                    ->where('course_id', $courseId)
                    ->whereNull('parent_id')
                    ->latest('created_at')
                    ->limit(200)
                    ->get();
            }

            if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                $query = DB::table('assignments')->where('course_id', $courseId);
                $hasSubmissions = Schema::hasTable('submissions')
                    && Schema::hasColumn('submissions', 'assignment_id')
                    && Schema::hasColumn('submissions', 'student_id')
                    && Schema::hasColumn('submissions', 'id');

                if ($hasSubmissions) {
                    $query->leftJoin('submissions', function ($join) use ($studentId) {
                        $join->on('submissions.assignment_id', '=', 'assignments.id')
                            ->where('submissions.student_id', '=', $studentId);
                    });
                }

                if (Schema::hasColumn('assignments', 'due_date')) {
                    $query->orderBy('assignments.due_date');
                } else {
                    $query->orderBy('assignments.id');
                }

                $select = [
                    'assignments.id as assignment_id',
                    Schema::hasColumn('assignments', 'title') ? 'assignments.title as title' : DB::raw("'Assignment' as title"),
                    Schema::hasColumn('assignments', 'due_date') ? 'assignments.due_date as due_date' : DB::raw("'' as due_date"),
                    $hasSubmissions ? 'submissions.id as submission_id' : DB::raw('NULL as submission_id'),
                ];

                $assignments = $query->select($select)->limit(300)->get();
                $completedAssignments = (int) $assignments->filter(fn ($a) => !empty($a->submission_id))->count();
            }
        } catch (\Throwable) {
            $resources = collect();
            $discussions = collect();
            $assignments = collect();
            $completedAssignments = 0;
        }

        return view('student.courses.show', [
            'course' => $course,
            'resources' => $resources,
            'discussions' => $discussions,
            'assignments' => $assignments,
            'completedAssignments' => $completedAssignments,
        ]);
    }

    public function storeDiscussion(Request $request, Course $course)
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $course->id;

        if (!Schema::hasTable('course_discussions')) {
            return redirect()->back()->with('error', 'Course discussion table is not available yet. Run migrations first.');
        }

        $enrollmentQuery = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId);
        if (Schema::hasColumn('enrollments', 'status')) {
            $enrollmentQuery->whereRaw('LOWER(status) = ?', ['active']);
        }
        if (!$enrollmentQuery->exists()) {
            abort(403);
        }

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:course_discussions,id'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $parentId = (int) ($validated['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = CourseDiscussion::query()
                ->where('id', $parentId)
                ->where('course_id', $courseId)
                ->first();
            if (!$parent) {
                return redirect()->back()->with('error', 'Invalid discussion reply target.');
            }
        }

        CourseDiscussion::query()->create([
            'course_id' => $courseId,
            'user_id' => $studentId,
            'parent_id' => $parentId > 0 ? $parentId : null,
            'content' => (string) $validated['content'],
        ]);

        $courseName = (string) ($course->title ?: $course->course_number ?: 'your course');

        $teacher = User::query()->find((int) ($course->teacher_id ?? 0));
        if ($teacher) {
            $teacher->notify(new CourseEventNotification(
                'New Student Discussion Comment',
                $request->user()->name . ' posted a new discussion comment in ' . $courseName . '.',
                route('teacher.courses.show', $course) . '#discussion'
            ));
        }

        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasColumn('enrollments', 'student_id')) {
            $peerIdsQuery = DB::table('enrollments')
                ->where('course_id', $courseId);
            if (Schema::hasColumn('enrollments', 'status')) {
                $peerIdsQuery->whereRaw('LOWER(status) = ?', ['active']);
            }
            $peerIds = $peerIdsQuery->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
            if (!empty($peerIds)) {
                $peers = User::query()->whereIn('id', $peerIds)->get();
                foreach ($peers as $peer) {
                    $peer->notify(new CourseEventNotification(
                        'New Discussion Comment',
                        $request->user()->name . ' commented in ' . $courseName . ' discussion.',
                        route('student.courses.show', $course) . '#discussion'
                    ));
                }
            }
        }

        return redirect()->to(route('student.courses.show', $course) . '#discussion')->with('success', 'Your comment has been posted.');
    }

    public function updateDiscussion(Request $request, Course $course, CourseDiscussion $discussion)
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $course->id;

        $enrollmentQuery = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId);
        if (Schema::hasColumn('enrollments', 'status')) {
            $enrollmentQuery->whereRaw('LOWER(status) = ?', ['active']);
        }
        if (!$enrollmentQuery->exists()) {
            abort(403);
        }
        if ((int) $discussion->course_id !== $courseId) {
            abort(404);
        }
        if ((int) $discussion->user_id !== $studentId) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $discussion->content = (string) $validated['content'];
        $discussion->save();

        return redirect()->to(route('student.courses.show', $course) . '#discussion')->with('success', 'Comment updated.');
    }

    public function destroyDiscussion(Request $request, Course $course, CourseDiscussion $discussion)
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $course->id;

        $enrollmentQuery = DB::table('enrollments')
            ->where('student_id', $studentId)
            ->where('course_id', $courseId);
        if (Schema::hasColumn('enrollments', 'status')) {
            $enrollmentQuery->whereRaw('LOWER(status) = ?', ['active']);
        }
        if (!$enrollmentQuery->exists()) {
            abort(403);
        }
        if ((int) $discussion->course_id !== $courseId) {
            abort(404);
        }
        if ((int) $discussion->user_id !== $studentId) {
            abort(403);
        }

        CourseDiscussion::query()
            ->where('course_id', $courseId)
            ->where('parent_id', (int) $discussion->id)
            ->where('user_id', $studentId)
            ->delete();

        $discussion->delete();

        return redirect()->to(route('student.courses.show', $course) . '#discussion')->with('success', 'Comment deleted.');
    }
}
