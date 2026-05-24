<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDiscussion;
use App\Models\CourseResource;
use App\Models\StudentExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentCourseApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $courses = [];

        try {
            if (Schema::hasTable('enrollments') && Schema::hasTable('courses') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
                $courseNameColumn = $this->courseNameColumn();

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
                        ->orderByDesc('courses.id')
                        ->get();

                    $courseIds = $rows->pluck('course_id')->map(fn ($value) => (int) $value)->filter()->values()->all();
                    $assignmentTotals = [];
                    $submissionTotals = [];

                    if (!empty($courseIds) && Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                        $totals = DB::table('assignments')
                            ->whereIn('course_id', $courseIds)
                            ->select(['course_id', DB::raw('COUNT(*) as c')])
                            ->groupBy('course_id')
                            ->get();

                        foreach ($totals as $total) {
                            $assignmentTotals[(int) $total->course_id] = (int) ($total->c ?? 0);
                        }

                        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                            $submitted = DB::table('submissions')
                                ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                ->where('submissions.student_id', $studentId)
                                ->whereIn('assignments.course_id', $courseIds)
                                ->select(['assignments.course_id as course_id', DB::raw('COUNT(*) as c')])
                                ->groupBy('assignments.course_id')
                                ->get();

                            foreach ($submitted as $item) {
                                $submissionTotals[(int) $item->course_id] = (int) ($item->c ?? 0);
                            }
                        }
                    }

                    $courses = $rows->map(function ($row) use ($assignmentTotals, $submissionTotals) {
                        $courseId = (int) ($row->course_id ?? 0);
                        $total = (int) ($assignmentTotals[$courseId] ?? 0);
                        $done = (int) ($submissionTotals[$courseId] ?? 0);
                        $progress = $total > 0 ? (int) round(min(100, max(0, ($done / $total) * 100))) : 0;

                        return [
                            'course_id' => $courseId,
                            'course_name' => (string) ($row->course_name ?? ''),
                            'course_number' => (string) ($row->course_number ?? ''),
                            'semester' => (string) ($row->semester ?? ''),
                            'school_year' => (string) ($row->school_year ?? ''),
                            'cover_image' => (string) ($row->cover_image ?? ''),
                            'teacher_name' => (string) ($row->teacher_name ?? ''),
                            'enrollment_status' => (string) ($row->enrollment_status ?? ''),
                            'progress' => $progress,
                            'assignments_total' => $total,
                            'assignments_submitted' => $done,
                        ];
                    })->values()->all();
                }
            }
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $course->id;

        if (!$this->isStudentEnrolled($studentId, $courseId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course.',
            ], 403);
        }

        $resources = collect();
        $discussions = collect();
        $assignments = collect();
        $completedAssignments = 0;
        $exams = collect();
        $quizzes = collect();

        if (Schema::hasTable('course_resources')) {
            try {
                $resources = CourseResource::query()
                    ->with('uploader:id,name')
                    ->where('course_id', $courseId)
                    ->latest('id')
                    ->limit(200)
                    ->get();
            } catch (\Throwable) {
                $resources = collect();
            }
        }

        if (Schema::hasTable('course_discussions')) {
            try {
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
            } catch (\Throwable) {
                $discussions = collect();
            }
        }

        if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
            try {
                $query = DB::table('assignments')->where('course_id', $courseId);
                $hasSubmissions = Schema::hasTable('submissions')
                    && Schema::hasColumn('submissions', 'assignment_id')
                    && Schema::hasColumn('submissions', 'student_id')
                    && Schema::hasColumn('submissions', 'id');

                if ($hasSubmissions) {
                    $query->leftJoin('submissions', function ($join) use ($studentId): void {
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
                $completedAssignments = (int) $assignments->filter(fn ($item) => !empty($item->submission_id))->count();
            } catch (\Throwable) {
                $assignments = collect();
                $completedAssignments = 0;
            }
        }

        if (Schema::hasTable('exams')) {
            try {
                $exams = $course->exams()
                    ->where('is_published', true)
                    ->where('exam_type', 'online')
                    ->orderBy('exam_date', 'asc')
                    ->get();

                foreach ($exams as $exam) {
                    $exam->attempts = StudentExamAttempt::where('exam_id', $exam->id)
                        ->where('student_id', $studentId)
                        ->get();
                }
            } catch (\Throwable) {
                $exams = collect();
            }
        }

        if (Schema::hasTable('quizzes')) {
            try {
                $quizzes = $course->quizzes()
                    ->where('is_published', true)
                    ->with(['attempts' => fn ($query) => $query->where('student_id', $studentId)])
                    ->orderBy('created_at', 'desc')
                    ->get();
            } catch (\Throwable) {
                $quizzes = collect();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'course' => $this->mapCourse($course),
                'resources' => $resources->map(fn ($resource) => [
                    'id' => (int) $resource->id,
                    'course_id' => (int) $resource->course_id,
                    'uploaded_by' => (int) $resource->uploaded_by,
                    'title' => (string) ($resource->title ?? ''),
                    'file_path' => (string) ($resource->file_path ?? ''),
                    'file_name' => (string) ($resource->file_name ?? ''),
                    'mime_type' => (string) ($resource->mime_type ?? ''),
                    'file_size' => (int) ($resource->file_size ?? 0),
                    'uploader' => $resource->relationLoaded('uploader') && $resource->uploader ? [
                        'id' => (int) $resource->uploader->id,
                        'name' => (string) $resource->uploader->name,
                    ] : null,
                ])->values()->all(),
                'discussions' => $discussions->map(fn ($discussion) => $this->mapDiscussion($discussion))->values()->all(),
                'assignments' => $assignments->map(fn ($item) => [
                    'assignment_id' => (int) ($item->assignment_id ?? 0),
                    'title' => (string) ($item->title ?? ''),
                    'due_date' => (string) ($item->due_date ?? ''),
                    'submission_id' => (int) ($item->submission_id ?? 0),
                ])->values()->all(),
                'completedAssignments' => $completedAssignments,
                'exams' => $exams->map(fn ($exam) => $this->mapExam($exam, false))->values()->all(),
                'quizzes' => $quizzes->map(fn ($quiz) => $this->mapQuiz($quiz, false))->values()->all(),
            ],
        ]);
    }

    private function courseNameColumn(): ?string
    {
        if (!Schema::hasTable('courses')) {
            return null;
        }

        foreach (['title', 'name', 'course_name'] as $column) {
            if (Schema::hasColumn('courses', $column)) {
                return $column;
            }
        }

        return null;
    }

    private function isStudentEnrolled(int $studentId, int $courseId): bool
    {
        try {
            if (!Schema::hasTable('enrollments') || !Schema::hasColumn('enrollments', 'student_id') || !Schema::hasColumn('enrollments', 'course_id')) {
                return false;
            }

            $query = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId);

            if (Schema::hasColumn('enrollments', 'status')) {
                $query->whereRaw('LOWER(status) = ?', ['active']);
            }

            return $query->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    private function mapCourse(Course $course): array
    {
        return [
            'id' => (int) $course->id,
            'course_number' => (string) ($course->course_number ?? ''),
            'title' => (string) ($course->title ?? ''),
            'description' => (string) ($course->description ?? ''),
            'semester' => (string) ($course->semester ?? ''),
            'school_year' => (string) ($course->school_year ?? ''),
            'start_date' => optional($course->start_date)->toDateString(),
            'end_date' => optional($course->end_date)->toDateString(),
            'days_pattern' => (string) ($course->days_pattern ?? ''),
            'start_time' => (string) ($course->start_time ?? ''),
            'end_time' => (string) ($course->end_time ?? ''),
            'teacher_id' => $course->teacher_id ? (int) $course->teacher_id : null,
            'cover_image' => (string) ($course->cover_image ?? ''),
            'overview' => (string) ($course->overview ?? ''),
        ];
    }

    private function mapDiscussion(CourseDiscussion $discussion): array
    {
        return [
            'id' => (int) $discussion->id,
            'course_id' => (int) $discussion->course_id,
            'user_id' => (int) $discussion->user_id,
            'parent_id' => $discussion->parent_id ? (int) $discussion->parent_id : null,
            'content' => (string) ($discussion->content ?? ''),
            'created_at' => optional($discussion->created_at)->toDateTimeString(),
            'updated_at' => optional($discussion->updated_at)->toDateTimeString(),
            'user' => $discussion->relationLoaded('user') && $discussion->user ? [
                'id' => (int) $discussion->user->id,
                'name' => (string) $discussion->user->name,
            ] : null,
            'replies' => $discussion->relationLoaded('replies')
                ? $discussion->replies->map(fn ($reply) => [
                    'id' => (int) $reply->id,
                    'course_id' => (int) $reply->course_id,
                    'user_id' => (int) $reply->user_id,
                    'parent_id' => $reply->parent_id ? (int) $reply->parent_id : null,
                    'content' => (string) ($reply->content ?? ''),
                    'created_at' => optional($reply->created_at)->toDateTimeString(),
                    'updated_at' => optional($reply->updated_at)->toDateTimeString(),
                    'user' => $reply->relationLoaded('user') && $reply->user ? [
                        'id' => (int) $reply->user->id,
                        'name' => (string) $reply->user->name,
                    ] : null,
                ])->values()->all()
                : [],
        ];
    }

    private function mapExam($exam, bool $includeAttempts = true): array
    {
        $payload = [
            'id' => (int) $exam->id,
            'course_id' => (int) ($exam->course_id ?? 0),
            'exam_type' => (string) ($exam->exam_type ?? ''),
            'title' => (string) ($exam->title ?? ''),
            'description' => (string) ($exam->description ?? ''),
            'exam_date' => optional($exam->exam_date)->toDateTimeString(),
            'due_date' => optional($exam->due_date)->toDateTimeString(),
            'duration' => (int) ($exam->duration ?? 0),
            'attempts_allowed' => (int) ($exam->attempts_allowed ?? 0),
            'max_score' => (int) ($exam->max_score ?? 0),
            'location' => (string) ($exam->location ?? ''),
            'instructions' => (string) ($exam->instructions ?? ''),
            'feedback_type' => (string) ($exam->feedback_type ?? ''),
            'results_released' => (bool) ($exam->results_released ?? false),
            'show_results' => (bool) ($exam->show_results ?? false),
            'shuffle_questions' => (bool) ($exam->shuffle_questions ?? false),
            'random_subset_count' => (int) ($exam->random_subset_count ?? 0),
            'is_published' => (bool) ($exam->is_published ?? false),
            'course' => $exam->relationLoaded('course') && $exam->course ? [
                'id' => (int) $exam->course->id,
                'title' => (string) ($exam->course->title ?? ''),
                'course_number' => (string) ($exam->course->course_number ?? ''),
            ] : null,
        ];

        if ($includeAttempts && isset($exam->attempts)) {
            $payload['attempts'] = collect($exam->attempts)->map(fn ($attempt) => $this->mapExamAttempt($attempt))->values()->all();
        }

        return $payload;
    }

    private function mapExamAttempt($attempt): array
    {
        return [
            'id' => (int) $attempt->id,
            'exam_id' => (int) $attempt->exam_id,
            'student_id' => (int) $attempt->student_id,
            'attempt_number' => (int) $attempt->attempt_number,
            'started_at' => optional($attempt->started_at)->toDateTimeString(),
            'submitted_at' => optional($attempt->submitted_at)->toDateTimeString(),
            'score' => $attempt->score !== null ? (int) $attempt->score : null,
            'max_score' => $attempt->max_score !== null ? (int) $attempt->max_score : null,
            'status' => (string) ($attempt->status ?? ''),
            'question_ids' => is_array($attempt->question_ids) ? array_values($attempt->question_ids) : [],
        ];
    }

    private function mapQuiz($quiz, bool $includeAttempts = true): array
    {
        $payload = [
            'id' => (int) $quiz->id,
            'course_id' => (int) ($quiz->course_id ?? 0),
            'title' => (string) ($quiz->title ?? ''),
            'description' => (string) ($quiz->description ?? ''),
            'start_date' => optional($quiz->start_date)->toDateTimeString(),
            'due_date' => optional($quiz->due_date)->toDateTimeString(),
            'max_score' => (int) ($quiz->max_score ?? 0),
            'time_limit' => (int) ($quiz->time_limit ?? 0),
            'attempts_allowed' => (int) ($quiz->attempts_allowed ?? 0),
            'shuffle_questions' => (bool) ($quiz->shuffle_questions ?? false),
            'random_subset_count' => (int) ($quiz->random_subset_count ?? 0),
            'show_results' => (bool) ($quiz->show_results ?? false),
            'feedback_type' => (string) ($quiz->feedback_type ?? ''),
            'results_released' => (bool) ($quiz->results_released ?? false),
            'is_published' => (bool) ($quiz->is_published ?? false),
            'points' => (int) ($quiz->points ?? 0),
            'duration' => $quiz->duration ?? null,
            'course' => $quiz->relationLoaded('course') && $quiz->course ? [
                'id' => (int) $quiz->course->id,
                'title' => (string) ($quiz->course->title ?? ''),
                'course_number' => (string) ($quiz->course->course_number ?? ''),
            ] : null,
        ];

        if ($includeAttempts && isset($quiz->attempts)) {
            $payload['attempts'] = collect($quiz->attempts)->map(fn ($attempt) => $this->mapQuizAttempt($attempt))->values()->all();
        }

        return $payload;
    }

    private function mapQuizAttempt($attempt): array
    {
        return [
            'id' => (int) $attempt->id,
            'quiz_id' => (int) $attempt->quiz_id,
            'student_id' => (int) $attempt->student_id,
            'attempt_number' => (int) $attempt->attempt_number,
            'started_at' => optional($attempt->started_at)->toDateTimeString(),
            'submitted_at' => optional($attempt->submitted_at)->toDateTimeString(),
            'score' => $attempt->score !== null ? (int) $attempt->score : null,
            'status' => (string) ($attempt->status ?? ''),
            'question_ids' => is_array($attempt->question_ids) ? array_values($attempt->question_ids) : [],
        ];
    }
}
