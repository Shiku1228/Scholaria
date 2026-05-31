<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\Concerns\ResolvesStudentEnrollment;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamQuestion;
use App\Models\StudentExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentExamApiController extends Controller
{
    use ResolvesStudentEnrollment;

    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        $exams = Exam::query()
            ->whereHas('course.enrollments', fn ($query) => $query->where('student_id', $studentId))
            ->where('is_published', true)
            ->with(['course'])
            ->orderBy('exam_date', 'asc')
            ->paginate(20);

        $attempts = StudentExamAttempt::query()
            ->where('student_id', $studentId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->orderBy('attempt_number', 'desc')
            ->get()
            ->unique('exam_id')
            ->keyBy('exam_id');
        
        // Note: index doesn't easily have question counts without heavy queries,
        // so we rely on show() for detailed question metadata.
        return response()->json([
            'success' => true,
            'data' => [
                'exams' => $exams->getCollection()->map(fn ($exam) => $this->mapExam($exam, $attempts->get($exam->id)))->values()->all(),
                'meta' => [
                    'current_page' => $exams->currentPage(),
                    'last_page' => $exams->lastPage(),
                    'per_page' => $exams->perPage(),
                    'total' => $exams->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Exam $exam): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $exam->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $exam->course_id);
        }

        // Face-to-Face: always return info-only, skip all date/attempt checks
        if ($exam->isFaceToFace()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'state' => 'face_to_face',
                    'exam' => $this->mapExam($exam),
                    'attempts' => [],
                    'selected_attempt' => null,
                    'questions' => [],
                ],
            ]);
        }

        if ($exam->exam_date && $exam->exam_date->isFuture()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'state' => 'upcoming',
                    'exam' => $this->mapExam($exam),
                    'attempts' => [],
                    'selected_attempt' => null,
                    'questions' => [],
                ],
            ]);
        }

        $attempts = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->orderBy('attempt_number')
            ->get();

        $activeAttempt = $attempts->where('status', 'in_progress')->first();
        $selectedAttempt = null;
        $questions = collect();
        $questionsCount = $exam->questions()->count();
        $state = 'available';

        if ($activeAttempt) {
            $state = 'in_progress';
            $questionIds = $activeAttempt->question_ids ?? [];
            $questions = empty($questionIds)
                ? $exam->questions()->orderBy('order')->get()
                : $exam->questions()->whereIn('id', $questionIds)->get()->sortBy(fn ($question) => array_search($question->id, $questionIds))->values();

            $selectedAttempt = $activeAttempt;
        } elseif ($exam->due_date && $exam->due_date->isPast() && $attempts->isEmpty()) {
            $state = 'missed';
        } else {
            // Load questions for preview/detail even if not started
            $questions = $exam->questions()->orderBy('order')->get();

            $selectedAttemptId = $request->query('attempt_id');
            if ($selectedAttemptId) {
                $selectedAttempt = StudentExamAttempt::query()
                    ->where('student_id', $studentId)
                    ->where('exam_id', $exam->id)
                    ->where('id', $selectedAttemptId)
                    ->with('answers.question')
                    ->first();
            } else {
                $selectedAttempt = $attempts->whereIn('status', ['submitted', 'graded'])->last();
                if ($selectedAttempt) {
                    $selectedAttempt->load('answers.question');
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'state' => $state,
                'exam' => $this->mapExam($exam, null, $questionsCount),
                'attempts' => $attempts->map(fn ($attempt) => $this->mapAttempt($attempt))->values()->all(),
                'selected_attempt' => $selectedAttempt ? $this->mapAttemptDetailed($selectedAttempt) : null,
                'questions' => $questions->map(fn ($question) => $this->mapQuestion($question, $state === 'in_progress'))->values()->all(),
            ],
        ]);
    }

    public function start(Request $request, Exam $exam): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $exam->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $exam->course_id);
        }

        if ($exam->isFaceToFace()) {
            return response()->json([
                'success' => false,
                'message' => 'This exam will be conducted face-to-face. Please follow your teacher\'s instructions.',
            ], 422);
        }

        if (!$exam->is_published || !$exam->isOnline()) {
            return response()->json([
                'success' => false,
                'message' => 'This exam is not available.',
            ], 422);
        }

        if ($exam->exam_date && $exam->exam_date->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'This exam has not started yet.',
            ], 422);
        }

        if ($exam->due_date && $exam->due_date->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'The due date for this exam has passed.',
            ], 422);
        }

        $activeAttempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->first();

        if ($activeAttempt) {
            return response()->json([
                'success' => true,
                'data' => [
                    'attempt' => $this->mapAttempt($activeAttempt),
                ],
            ]);
        }

        $pastAttemptsCount = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($exam->attempts_allowed && $pastAttemptsCount >= $exam->attempts_allowed) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum number of attempts allowed for this exam.',
            ], 422);
        }

        $questionsQuery = $exam->questions()->orderBy('order');
        $questions = $exam->shuffle_questions ? $questionsQuery->inRandomOrder()->get() : $questionsQuery->get();

        if ($exam->random_subset_count && $exam->random_subset_count > 0) {
            $questions = $questions->take($exam->random_subset_count);
        }

        $questionIds = $questions->pluck('id')->toArray();

        $attempt = StudentExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $studentId,
            'attempt_number' => $pastAttemptsCount + 1,
            'started_at' => now(),
            'max_score' => $exam->max_score,
            'status' => 'in_progress',
            'question_ids' => $questionIds,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $this->mapAttempt($attempt),
                'questions' => $questions->map(fn ($question) => $this->mapQuestion($question, true))->values()->all(),
            ],
        ]);
    }

    public function submit(Request $request, Exam $exam): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        $attempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);

        $answers = $validated['answers'] ?? [];
        $totalScore = 0;

        $questionIds = $attempt->question_ids ?? [];
        $questions = $exam->questions()->whereIn('id', $questionIds)->get();

        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;

            $isCorrect = null;
            $score = null;
            if ($question->question_type === 'short_answer') {
                $isCorrect = strtolower(trim((string) $submittedAnswer)) === strtolower(trim((string) ($question->correct_answer ?? '')));
                $score = $isCorrect ? (int) $question->points : 0;
                $totalScore += $score;
            } elseif (in_array($question->question_type, ['multiple_choice', 'true_false'], true)) {
                $isCorrect = $question->correct_answer === $submittedAnswer;
                $score = $isCorrect ? (int) $question->points : 0;
                $totalScore += $score;
            }

            ExamAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer' => $submittedAnswer,
                'is_correct' => $isCorrect,
                'points_earned' => $score,
            ]);
        }

        $attempt->update([
            'submitted_at' => now(),
            'score' => $totalScore,
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam submitted successfully!',
            'data' => [
                'attempt' => $this->mapAttempt($attempt->fresh()),
                'total_score' => $totalScore,
            ],
        ]);
    }

    private function mapExam(Exam $exam, ?StudentExamAttempt $attempt = null, ?int $questionsCount = null): array
    {
        $isFaceToFace = $exam->isFaceToFace();
        $status = 'available';
        if ($isFaceToFace) {
            $status = 'face_to_face';
        } elseif ($attempt) {
            $status = $attempt->status === 'in_progress' ? 'in_progress' : ($attempt->status === 'graded' ? 'graded' : 'submitted');
        } elseif ($exam->exam_date && $exam->exam_date->isFuture()) {
            $status = 'upcoming';
        } elseif ($exam->due_date && $exam->due_date->isPast()) {
            $status = 'closed';
        }

        return [
            'id' => (int) $exam->id,
            'course_id' => (int) ($exam->course_id ?? 0),
            'exam_type' => (string) ($exam->exam_type ?? ''),
            'exam_method' => $isFaceToFace ? 'face_to_face' : 'online',
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
            'status' => $status,
            'can_answer' => !$isFaceToFace && $exam->isOnline() && $exam->is_published,
            'can_view_result' => (bool) ($exam->show_results ?? false) && (bool) ($exam->results_released ?? false),
            'course' => $exam->relationLoaded('course') && $exam->course ? [
                'id' => (int) $exam->course->id,
                'title' => (string) ($exam->course->title ?? ''),
                'course_number' => (string) ($exam->course->course_number ?? ''),
            ] : null,
            'attempt' => $attempt ? $this->mapAttempt($attempt) : null,
            'questions_count' => $isFaceToFace ? 0 : ($questionsCount ?? (int) $exam->questions()->count()),
        ];
    }

    private function mapAttempt(StudentExamAttempt $attempt): array
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

    private function mapAttemptDetailed(StudentExamAttempt $attempt): array
    {
        return [
            'attempt' => $this->mapAttempt($attempt),
            'answers' => $attempt->relationLoaded('answers')
                ? $attempt->answers->map(fn ($answer) => [
                    'id' => (int) $answer->id,
                    'attempt_id' => (int) $answer->attempt_id,
                    'question_id' => (int) $answer->question_id,
                    'answer' => $answer->answer ?? null,
                    'score' => $answer->points_earned ?? null,
                    'feedback' => $answer->feedback ?? null,
                    'question' => $answer->relationLoaded('question') && $answer->question ? $this->mapQuestion($answer->question, false, true) : null,
                ])->values()->all()
                : [],
        ];
    }

    private function mapQuestion(ExamQuestion $question, bool $hideCorrectAnswer = true, bool $includeExplanation = false): array
    {
        return [
            'id' => (int) $question->id,
            'exam_id' => (int) $question->exam_id,
            'question_text' => (string) ($question->question_text ?? ''),
            'question_type' => (string) ($question->question_type ?? ''),
            'options' => is_array($question->options) ? $question->options : json_decode($question->options ?? '[]', true),
            'correct_answer' => $hideCorrectAnswer ? null : ($question->correct_answer ?? null),
            'explanation' => $includeExplanation ? ($question->explanation ?? null) : null,
            'points' => (int) ($question->points ?? 0),
            'order' => (int) ($question->order ?? 0),
        ];
    }

}
