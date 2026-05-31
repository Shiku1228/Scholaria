<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\Concerns\ResolvesStudentEnrollment;
use App\Models\Assignment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentAssignmentApiController extends Controller
{
    use ResolvesStudentEnrollment;

    // ── List ─────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $courseId  = (int) $request->query('course_id', 0);
        $assignments = [];

        try {
            if (!Schema::hasTable('enrollments')
                || !Schema::hasColumn('enrollments', 'student_id')
                || !Schema::hasColumn('enrollments', 'course_id')) {
                return response()->json(['success' => true, 'data' => ['assignments' => []]]);
            }

            if (!Schema::hasTable('assignments')) {
                return response()->json(['success' => true, 'data' => ['assignments' => []]]);
            }

            $courseNameCol = $this->courseNameColumn();

            $enrolledCourseIds = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->when(Schema::hasColumn('enrollments', 'status'), fn ($q) =>
                    $q->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['dropped', 'dropped'])
                )
                ->pluck('course_id')
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->values()
                ->all();

            if (empty($enrolledCourseIds)) {
                return response()->json(['success' => true, 'data' => ['assignments' => []]]);
            }

            if ($courseId > 0 && !in_array($courseId, $enrolledCourseIds, true)) {
                return $this->enrollmentDeniedResponse($studentId, $courseId);
            }

            $query = DB::table('assignments');

            if ($courseNameCol && Schema::hasTable('courses') && Schema::hasColumn('assignments', 'course_id')) {
                $query->join('courses', 'courses.id', '=', 'assignments.course_id');
            }

            if (Schema::hasTable('submissions')
                && Schema::hasColumn('submissions', 'assignment_id')
                && Schema::hasColumn('submissions', 'student_id')) {
                $query->leftJoin('submissions', function ($join) use ($studentId): void {
                    $join->on('submissions.assignment_id', '=', 'assignments.id')
                         ->where('submissions.student_id', '=', $studentId);
                });
            }

            $targetIds = $courseId > 0 ? [$courseId] : $enrolledCourseIds;
            if (Schema::hasColumn('assignments', 'course_id')) {
                $query->whereIn('assignments.course_id', $targetIds);
            }

            if (Schema::hasColumn('assignments', 'due_date')) {
                $query->orderBy('assignments.due_date');
            } else {
                $query->orderByDesc('assignments.id');
            }

            // ── SELECT columns ──
            $select = [
                'assignments.id    as assignment_id',
                'assignments.title as title',
            ];

            foreach (['due_date', 'max_score', 'type', 'assignment_format'] as $col) {
                if (Schema::hasColumn('assignments', $col)) {
                    $select[] = "assignments.{$col} as {$col}";
                }
            }

            if ($courseNameCol) {
                $select[] = "courses.{$courseNameCol} as course_name";
                $select[] = 'courses.id as course_id';
                if (Schema::hasColumn('courses', 'course_number')) {
                    $select[] = 'courses.course_number as course_number';
                }
            } elseif (Schema::hasColumn('assignments', 'course_id')) {
                $select[] = 'assignments.course_id as course_id';
            }

            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'id')) {
                $select[] = 'submissions.id as submission_id';
                foreach (['submitted_at', 'score'] as $col) {
                    if (Schema::hasColumn('submissions', $col)) {
                        $select[] = "submissions.{$col} as {$col}";
                    }
                }
            }

            $rows = $query->select($select)->limit(200)->get();
            $now  = now();

            $assignments = $rows->map(function ($row) use ($now) {
                $format = (string) ($row->assignment_format ?? 'essay');
                return [
                    'assignment_id'     => (int) ($row->assignment_id ?? 0),
                    'title'             => (string) ($row->title ?? ''),
                    'course_id'         => (int) ($row->course_id ?? 0),
                    'course_name'       => (string) ($row->course_name ?? ''),
                    'course_number'     => (string) ($row->course_number ?? ''),
                    'due_date'          => isset($row->due_date) && $row->due_date ? (string) $row->due_date : null,
                    'max_score'         => isset($row->max_score) ? (int) $row->max_score : null,
                    'type'              => (string) ($row->type ?? 'assignment'),
                    'assignment_format' => $format,
                    'submission_id'     => (int) ($row->submission_id ?? 0),
                    'submitted_at'      => isset($row->submitted_at) && $row->submitted_at ? (string) $row->submitted_at : null,
                    'score'             => isset($row->score) && $row->score !== null ? (int) $row->score : null,
                    'status'            => $this->computeListStatus($row, $format, $now),
                ];
            })->values()->all();

        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data'    => ['assignments' => $assignments],
        ]);
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    public function show(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        // Enrollment guard
        try {
            if (Schema::hasTable('enrollments')
                && Schema::hasColumn('enrollments', 'student_id')
                && Schema::hasColumn('enrollments', 'course_id')
                && Schema::hasColumn('assignments', 'course_id')) {
                $cId = (int) ($assignment->course_id ?? 0);
                if ($cId > 0) {
                    $enrolled = DB::table('enrollments')
                        ->where('student_id', $studentId)
                        ->where('course_id', $cId)
                        ->when(Schema::hasColumn('enrollments', 'status'), fn ($q) =>
                            $q->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['dropped', 'dropped'])
                        )
                        ->exists();
                    if (!$enrolled) {
                        return $this->enrollmentDeniedResponse($studentId, $cId);
                    }
                }
            }
        } catch (\Throwable) {
        }

        // Course
        $course = null;
        try {
            if (Schema::hasTable('courses')) {
                $course = DB::table('courses')->where('id', $assignment->course_id)->first();
            }
        } catch (\Throwable) {
        }

        // Questions
        $questions    = collect();
        $hasQuestions = false;
        try {
            if (Schema::hasTable('assignment_questions')) {
                $questions    = $assignment->questions()->with('choices')->get();
                $hasQuestions = $questions->isNotEmpty();
            }
        } catch (\Throwable) {
        }

        // Submission (question-based submissions use submitted_at as the presence check)
        $submission = null;
        try {
            if (Schema::hasTable('submissions')) {
                $submission = DB::table('submissions')
                    ->where('assignment_id', $assignment->id)
                    ->where('student_id', $studentId)
                    ->whereNotNull('submitted_at')
                    ->orderByDesc('id')
                    ->first();
            }
        } catch (\Throwable) {
        }

        // Per-question answers (only relevant when has questions and is submitted)
        $answers = collect();
        try {
            if ($submission && $hasQuestions && Schema::hasTable('assignment_answers')) {
                $answers = DB::table('assignment_answers')
                    ->where('submission_id', $submission->id)
                    ->get()
                    ->keyBy('question_id');
            }
        } catch (\Throwable) {
        }

        $format     = (string) ($assignment->assignment_format ?? 'essay');
        $isMC       = $format === 'multiple_choice';
        $isSubmitted = $submission !== null;

        // Build questions payload
        $questionsPayload = $questions->map(function ($q) use ($isMC, $isSubmitted, $answers) {
            $answer = $answers->get($q->id);
            $qData  = [
                'id'            => (int) $q->id,
                'question_text' => (string) $q->question_text,
                'points'        => (int) $q->points,
                'order'         => (int) ($q->order ?? 0),
            ];

            if ($isMC) {
                $qData['choices'] = $q->choices->map(function ($c) use ($isSubmitted) {
                    $choice = [
                        'id'          => (int) $c->id,
                        'choice_text' => (string) $c->choice_text,
                        'order'       => (int) ($c->order ?? 0),
                    ];
                    // Reveal correct answer after submission (consistent with web view)
                    if ($isSubmitted) {
                        $choice['is_correct'] = (bool) $c->is_correct;
                    }
                    return $choice;
                })->values()->all();
            }

            if ($answer) {
                $qData['student_answer'] = $isMC
                    ? [
                        'selected_choice_id' => $answer->selected_choice_id !== null ? (int) $answer->selected_choice_id : null,
                        'score'              => $answer->score !== null ? (int) $answer->score : null,
                    ]
                    : [
                        'essay_answer' => (string) ($answer->essay_answer ?? ''),
                        'score'        => $answer->score !== null ? (int) $answer->score : null,
                    ];
            }

            return $qData;
        })->values()->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'assignment' => [
                    'id'                => (int) $assignment->id,
                    'course_id'         => (int) ($assignment->course_id ?? 0),
                    'title'             => (string) ($assignment->title ?? ''),
                    'description'       => (string) ($assignment->description ?? ''),
                    'due_date'          => optional($assignment->due_date)->toDateTimeString(),
                    'max_score'         => $assignment->max_score !== null ? (int) $assignment->max_score : null,
                    'type'              => (string) ($assignment->type ?? 'assignment'),
                    'type_label'        => $assignment->getTypeLabel(),
                    'assignment_format' => $format,
                    'has_questions'     => $hasQuestions,
                ],
                'questions'  => $questionsPayload,
                'course'     => $course ? [
                    'id'            => (int) $course->id,
                    'title'         => (string) ($course->title ?? ''),
                    'course_number' => (string) ($course->course_number ?? ''),
                ] : null,
                'submission' => $submission ? $this->formatSubmission($submission, $format) : null,
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatSubmission(object $sub, string $format): array
    {
        $score  = $sub->score ?? null;
        $status = $score !== null
            ? 'graded'
            : ($format === 'essay' ? 'pending_grading' : 'submitted');

        return [
            'id'              => (int) $sub->id,
            'submission_type' => (string) ($sub->submission_type ?? ''),
            'content'         => $sub->content ?? null,
            'file_path'       => $sub->file_path ?? null,
            'submitted_at'    => (string) $sub->submitted_at,
            'score'           => $score !== null ? (int) $score : null,
            'feedback'        => $sub->feedback ?? null,
            'status'          => $status,
        ];
    }

    private function computeListStatus(object $row, string $format, Carbon $now): string
    {
        if (!empty($row->submitted_at)) {
            $score = $row->score ?? null;
            if ($score !== null) return 'graded';
            return $format === 'essay' ? 'pending_grading' : 'submitted';
        }
        if (!empty($row->due_date) && Carbon::parse($row->due_date)->lt($now)) {
            return 'overdue';
        }
        return 'not_submitted';
    }

    private function courseNameColumn(): ?string
    {
        if (!Schema::hasTable('courses')) return null;
        foreach (['title', 'name', 'course_name'] as $col) {
            if (Schema::hasColumn('courses', $col)) return $col;
        }
        return null;
    }
}
