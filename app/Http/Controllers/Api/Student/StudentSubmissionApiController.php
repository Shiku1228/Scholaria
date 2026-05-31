<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\Concerns\ResolvesStudentEnrollment;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentSubmissionApiController extends Controller
{
    use ResolvesStudentEnrollment;

    // ── GET /student/submissions ──────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $courseId  = (int) $request->query('course_id', 0);
        $submissions = [];

        try {
            if (!Schema::hasTable('submissions') || !Schema::hasColumn('submissions', 'student_id')) {
                return response()->json([
                    'success' => true,
                    'data'    => ['submissions' => [], 'filters' => ['course_id' => $courseId]],
                ]);
            }

            $query = DB::table('submissions')->where('submissions.student_id', $studentId);

            $hasAssignments = Schema::hasTable('assignments') && Schema::hasColumn('submissions', 'assignment_id');
            if ($hasAssignments) {
                $query->leftJoin('assignments', 'assignments.id', '=', 'submissions.assignment_id');
            }

            $courseNameCol  = $this->courseNameColumn();
            $hasCourseJoin  = $courseNameCol && $hasAssignments && Schema::hasTable('courses') && Schema::hasColumn('assignments', 'course_id');
            if ($hasCourseJoin) {
                $query->leftJoin('courses', 'courses.id', '=', 'assignments.course_id');
            }

            if ($courseId > 0 && $hasAssignments && Schema::hasColumn('assignments', 'course_id')) {
                $query->where('assignments.course_id', $courseId);
            } elseif ($courseId > 0) {
                return response()->json([
                    'success' => true,
                    'data'    => ['submissions' => [], 'filters' => ['course_id' => $courseId]],
                ]);
            }

            $select = [
                'submissions.id            as submission_id',
                'submissions.assignment_id as assignment_id',
                'submissions.student_id    as student_id',
            ];

            foreach (['submission_type', 'content', 'file_path', 'submitted_at', 'score', 'feedback', 'created_at', 'updated_at'] as $col) {
                if (Schema::hasColumn('submissions', $col)) {
                    $select[] = "submissions.{$col} as {$col}";
                }
            }

            if ($hasAssignments) {
                foreach (['title', 'due_date', 'max_score', 'assignment_format'] as $col) {
                    if (Schema::hasColumn('assignments', $col)) {
                        $select[] = "assignments.{$col} as " . ($col === 'title' ? 'assignment_title' : $col);
                    }
                }
                if (Schema::hasColumn('assignments', 'course_id')) {
                    $select[] = 'assignments.course_id as course_id';
                }
            }

            if ($hasCourseJoin) {
                $select[] = "courses.{$courseNameCol} as course_name";
            }

            $rows = $query
                ->select($select)
                ->orderByDesc(Schema::hasColumn('submissions', 'submitted_at') ? 'submissions.submitted_at' : 'submissions.id')
                ->limit(300)
                ->get();

            $submissions = $rows->map(fn ($row) => $this->mapSubmissionRow($row))->values()->all();

        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data'    => ['submissions' => $submissions, 'filters' => ['course_id' => $courseId]],
        ]);
    }

    // ── GET /student/assignments/{assignment}/submit ───────────────────────────

    public function create(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $assignment->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $assignment->course_id);
        }

        // Existing submission
        $submission = null;
        if (Schema::hasTable('submissions')) {
            $submission = DB::table('submissions')
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->whereNotNull('submitted_at')
                ->orderByDesc('id')
                ->first();
        }

        // Questions (with choices; is_correct hidden until submitted)
        $questions    = collect();
        $hasQuestions = false;
        try {
            if (Schema::hasTable('assignment_questions')) {
                $questions    = $assignment->questions()->with('choices')->get();
                $hasQuestions = $questions->isNotEmpty();
            }
        } catch (\Throwable) {
        }

        $format      = (string) ($assignment->assignment_format ?? 'essay');
        $isMC        = $format === 'multiple_choice';
        $isSubmitted = $submission !== null;

        // Existing answers
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
                    'assignment_format' => $format,
                    'has_questions'     => $hasQuestions,
                ],
                'questions'  => $questionsPayload,
                'submission' => $submission ? $this->formatSubmission($submission, $format) : null,
            ],
        ]);
    }

    // ── POST /student/assignments/{assignment}/submit ─────────────────────────

    public function store(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $assignment->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $assignment->course_id);
        }

        if (!Schema::hasTable('submissions')) {
            return response()->json(['success' => false, 'message' => 'Submissions are not available yet.'], 422);
        }

        // ── Question-based path ────────────────────────────────────────────────
        $hasQuestions = Schema::hasTable('assignment_questions')
            && Schema::hasTable('assignment_answers')
            && $assignment->questions()->exists();

        if ($hasQuestions) {
            $questions = $assignment->questions()->with('choices')->get();
            $format    = (string) ($assignment->assignment_format ?? 'essay');
            $isMC      = $format === 'multiple_choice';

            // Build per-question validation rules
            $rules = [];
            foreach ($questions as $q) {
                if ($isMC) {
                    $choiceIds = $q->choices->pluck('id')->toArray();
                    $rules["answers.{$q->id}"] = ['required', 'integer', Rule::in($choiceIds)];
                } else {
                    $rules["answers.{$q->id}"] = ['required', 'string', 'min:1', 'max:10000'];
                }
            }

            $validated = $request->validate($rules);

            $successMessage = $isMC
                ? 'Assignment submitted! Your score has been calculated automatically.'
                : 'Assignment submitted. Your teacher will review and grade your answers.';

            DB::transaction(function () use ($validated, $assignment, $studentId, $questions, $isMC): void {
                $qCount     = $questions->count();
                $submission = Submission::firstOrNew([
                    'assignment_id' => (int) $assignment->id,
                    'student_id'    => $studentId,
                ]);
                $submission->submission_type = 'text';
                $submission->submitted_at    = now();
                $submission->content         = $qCount . ' question' . ($qCount !== 1 ? 's' : '') . ' answered';
                $submission->file_path       = null;
                $submission->save();

                $totalAutoScore = 0;

                foreach ($questions as $q) {
                    $answerValue = $validated['answers'][$q->id] ?? null;

                    if ($isMC) {
                        $selectedChoice = $q->choices->firstWhere('id', (int) $answerValue);
                        $autoScore      = ($selectedChoice && $selectedChoice->is_correct) ? (int) $q->points : 0;
                        $totalAutoScore += $autoScore;

                        DB::table('assignment_answers')->updateOrInsert(
                            ['submission_id' => $submission->id, 'question_id' => $q->id],
                            [
                                'selected_choice_id' => (int) $answerValue,
                                'essay_answer'       => null,
                                'score'              => $autoScore,
                                'updated_at'         => now(),
                                'created_at'         => now(),
                            ]
                        );
                    } else {
                        DB::table('assignment_answers')->updateOrInsert(
                            ['submission_id' => $submission->id, 'question_id' => $q->id],
                            [
                                'essay_answer'       => (string) $answerValue,
                                'selected_choice_id' => null,
                                'score'              => null,
                                'updated_at'         => now(),
                                'created_at'         => now(),
                            ]
                        );
                    }
                }

                if ($isMC) {
                    $submission->update(['score' => $totalAutoScore]);
                }
            });

            // Reload submission for response
            $savedSubmission = DB::table('submissions')
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->orderByDesc('id')
                ->first();

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'data'    => [
                    'submission' => $savedSubmission ? $this->formatSubmission($savedSubmission, $format) : null,
                ],
            ]);
        }

        // ── Legacy text / file / link path ────────────────────────────────────
        $submissionType = (string) $request->input('submission_type', 'file');

        $rules = ['submission_type' => ['required', 'in:text,file,link']];
        if ($submissionType === 'text') {
            $rules['text_content'] = ['required', 'string', 'min:10'];
        } elseif ($submissionType === 'file') {
            $rules['file'] = ['required', 'file', 'max:10240'];
        } elseif ($submissionType === 'link') {
            $rules['link_content'] = ['required', 'url', 'max:500'];
        }

        $validated  = $request->validate($rules);
        $submission = Submission::query()->firstOrNew([
            'assignment_id' => (int) $assignment->id,
            'student_id'    => $studentId,
        ]);

        $submission->submission_type = $submissionType;
        $submission->submitted_at    = now();

        switch ($submissionType) {
            case 'text':
                $submission->content   = strip_tags($validated['text_content'], '<p><br><strong><em><u><ul><ol><li><a><code><pre><h1><h2><h3><h4><h5><h6>');
                $submission->file_path = null;
                break;
            case 'file':
                $file = $validated['file'];
                $path = $file->storeAs(
                    'submissions',
                    now()->format('Ymd_His') . '_' . Str::random(10) . '_' . $file->getClientOriginalName(),
                    'public'
                );
                $submission->file_path = $path;
                $submission->content   = null;
                break;
            case 'link':
                $submission->content   = $validated['link_content'];
                $submission->file_path = null;
                break;
        }

        $submission->save();

        $message = match ($submissionType) {
            'text'  => 'Text submission saved successfully.',
            'file'  => 'File uploaded successfully.',
            'link'  => 'Link submitted successfully.',
            default => 'Assignment submitted successfully.',
        };

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => [
                'submission' => [
                    'id'              => (int) $submission->id,
                    'assignment_id'   => (int) $submission->assignment_id,
                    'student_id'      => (int) $submission->student_id,
                    'submission_type' => (string) $submission->submission_type,
                    'content'         => $submission->content ?? null,
                    'file_path'       => $submission->file_path ?? null,
                    'file_url'        => $submission->file_path ? Storage::disk('public')->url($submission->file_path) : null,
                    'submitted_at'    => optional($submission->submitted_at)->toDateTimeString(),
                    'score'           => $submission->score ?? null,
                    'feedback'        => $submission->feedback ?? null,
                    'status'          => 'submitted',
                ],
            ],
        ]);
    }

    // ── GET /student/assignments/{assignment}/submission ──────────────────────
    // Lightweight endpoint: returns the current submission + per-question answers

    public function showForAssignment(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $assignment->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $assignment->course_id);
        }

        $submission = null;
        if (Schema::hasTable('submissions')) {
            $submission = DB::table('submissions')
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->whereNotNull('submitted_at')
                ->orderByDesc('id')
                ->first();
        }

        if (!$submission) {
            return response()->json([
                'success' => true,
                'data'    => ['submission' => null, 'answers' => []],
            ]);
        }

        $format = (string) ($assignment->assignment_format ?? 'essay');
        $isMC   = $format === 'multiple_choice';

        // Questions with answers
        $questions = collect();
        $answers   = collect();
        try {
            if (Schema::hasTable('assignment_questions')) {
                $questions = $assignment->questions()->with('choices')->get();
            }
            if ($questions->isNotEmpty() && Schema::hasTable('assignment_answers')) {
                $answers = DB::table('assignment_answers')
                    ->where('submission_id', $submission->id)
                    ->get()
                    ->keyBy('question_id');
            }
        } catch (\Throwable) {
        }

        $answersPayload = $questions->map(function ($q) use ($isMC, $answers) {
            $answer = $answers->get($q->id);
            $aData  = [
                'question_id'   => (int) $q->id,
                'question_text' => (string) $q->question_text,
                'points'        => (int) $q->points,
                'order'         => (int) ($q->order ?? 0),
            ];

            if ($isMC) {
                // Always show is_correct in the submission review
                $aData['choices'] = $q->choices->map(fn ($c) => [
                    'id'          => (int) $c->id,
                    'choice_text' => (string) $c->choice_text,
                    'is_correct'  => (bool) $c->is_correct,
                ])->values()->all();
                $aData['selected_choice_id'] = $answer && $answer->selected_choice_id !== null ? (int) $answer->selected_choice_id : null;
                $aData['score']              = $answer && $answer->score !== null ? (int) $answer->score : null;
            } else {
                $aData['essay_answer'] = $answer ? (string) ($answer->essay_answer ?? '') : null;
                $aData['score']        = $answer && $answer->score !== null ? (int) $answer->score : null;
            }

            return $aData;
        })->values()->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'submission' => $this->formatSubmission($submission, $format),
                'answers'    => $answersPayload,
            ],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function formatSubmission(object $sub, string $format): array
    {
        $score  = $sub->score ?? null;
        $status = $score !== null
            ? 'graded'
            : ($format === 'essay' ? 'pending_grading' : 'submitted');

        return [
            'id'              => (int) $sub->id,
            'assignment_id'   => (int) ($sub->assignment_id ?? 0),
            'student_id'      => (int) ($sub->student_id ?? 0),
            'submission_type' => (string) ($sub->submission_type ?? ''),
            'content'         => $sub->content ?? null,
            'file_path'       => $sub->file_path ?? null,
            'file_url'        => !empty($sub->file_path) ? Storage::disk('public')->url($sub->file_path) : null,
            'submitted_at'    => (string) $sub->submitted_at,
            'score'           => $score !== null ? (int) $score : null,
            'feedback'        => $sub->feedback ?? null,
            'status'          => $status,
        ];
    }

    private function mapSubmissionRow(object $row): array
    {
        $format   = (string) ($row->assignment_format ?? 'essay');
        $score    = $row->score ?? null;
        $feedback = (string) ($row->feedback ?? '');
        $filePath = (string) ($row->file_path ?? '');

        $status = $score !== null
            ? 'graded'
            : (
                !empty($row->submitted_at)
                    ? ($format === 'essay' ? 'pending_grading' : 'submitted')
                    : 'draft'
            );

        return [
            'submission_id'     => (int) ($row->submission_id ?? 0),
            'assignment_id'     => (int) ($row->assignment_id ?? 0),
            'student_id'        => (int) ($row->student_id ?? 0),
            'course_id'         => (int) ($row->course_id ?? 0),
            'course_name'       => (string) ($row->course_name ?? ''),
            'assignment_title'  => (string) ($row->assignment_title ?? ''),
            'assignment_format' => $format,
            'submission_type'   => (string) ($row->submission_type ?? ''),
            'content'           => $row->content ?? null,
            'file_path'         => $filePath !== '' ? $filePath : null,
            'file_url'          => $filePath !== '' ? Storage::disk('public')->url($filePath) : null,
            'due_date'          => !empty($row->due_date) ? (string) $row->due_date : null,
            'max_score'         => isset($row->max_score) ? (int) $row->max_score : null,
            'submitted_at'      => !empty($row->submitted_at) ? (string) $row->submitted_at : null,
            'score'             => $score !== null ? (int) $score : null,
            'feedback'          => $feedback !== '' ? $feedback : null,
            'status'            => $status,
            'created_at'        => (string) ($row->created_at ?? ''),
            'updated_at'        => (string) ($row->updated_at ?? ''),
        ];
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
