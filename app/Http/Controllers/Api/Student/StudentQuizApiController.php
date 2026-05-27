<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\Concerns\ResolvesStudentEnrollment;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentQuizApiController extends Controller
{
    use ResolvesStudentEnrollment;

    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        $quizzes = Quiz::query()
            ->whereHas('course.enrollments', fn ($query) => $query->where('student_id', $studentId))
            ->where('is_published', true)
            ->with(['course'])
            ->orderBy('due_date', 'asc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'quizzes' => $quizzes->getCollection()->map(fn ($quiz) => $this->mapQuiz($quiz, null))->values()->all(),
                'meta' => [
                    'current_page' => $quizzes->currentPage(),
                    'last_page' => $quizzes->lastPage(),
                    'per_page' => $quizzes->perPage(),
                    'total' => $quizzes->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Quiz $quiz): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $quiz->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $quiz->course_id);
        }

        if ($quiz->start_date && $quiz->start_date->isFuture()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'state' => 'upcoming',
                    'quiz' => $this->mapQuiz($quiz, null),
                    'attempts' => [],
                    'selected_attempt' => null,
                    'questions' => [],
                ],
            ]);
        }

        $attempts = $quiz->attempts()
            ->where('student_id', $studentId)
            ->orderBy('attempt_number')
            ->get();

        $activeAttempt = $attempts->where('status', 'in_progress')->first();
        $selectedAttempt = null;
        $questions = collect();
        $questionsCount = $quiz->questions()->count();
        $state = 'available';

        if ($activeAttempt) {
            $state = 'in_progress';
            $questionIds = $activeAttempt->question_ids ?? [];
            $questions = empty($questionIds)
                ? $quiz->questions()->orderBy('order')->get()
                : $quiz->questions()->whereIn('id', $questionIds)->get()->sortBy(fn ($question) => array_search($question->id, $questionIds))->values();
            $selectedAttempt = $activeAttempt;
        } else {
            // Load questions for preview/detail even if not started
            $questions = $quiz->questions()->orderBy('order')->get();

            $selectedAttemptId = $request->query('attempt_id');
            if ($selectedAttemptId) {
                $selectedAttempt = $quiz->attempts()
                    ->where('student_id', $studentId)
                    ->where('id', $selectedAttemptId)
                    ->with('answers.question')
                    ->first();
            } else {
                $selectedAttempt = $attempts->where('status', 'submitted')->last();
                if ($selectedAttempt) {
                    $selectedAttempt->load('answers.question');
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'state' => $state,
                'quiz' => $this->mapQuiz($quiz, null, $questionsCount),
                'attempts' => $attempts->map(fn ($attempt) => $this->mapAttempt($attempt))->values()->all(),
                'selected_attempt' => $selectedAttempt ? $this->mapAttemptDetailed($selectedAttempt) : null,
                'questions' => $questions->map(fn ($question) => $this->mapQuestion($question, $state === 'in_progress'))->values()->all(),
            ],
        ]);
    }

    public function start(Request $request, Quiz $quiz): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $quiz->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $quiz->course_id);
        }

        if (!$quiz->is_published) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is not available.',
            ], 422);
        }

        if ($quiz->start_date && $quiz->start_date->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'This quiz is not open yet.',
            ], 422);
        }

        if ($quiz->due_date && $quiz->due_date->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'The due date for this quiz has passed.',
            ], 422);
        }

        $activeAttempt = $quiz->attempts()
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

        $pastAttemptsCount = $quiz->attempts()
            ->where('student_id', $studentId)
            ->where('status', 'submitted')
            ->count();

        if ($quiz->attempts_allowed && $pastAttemptsCount >= $quiz->attempts_allowed) {
            return response()->json([
                'success' => false,
                'message' => 'You have reached the maximum number of attempts allowed for this quiz.',
            ], 422);
        }

        $questionsQuery = $quiz->questions()->orderBy('order');
        $questions = $quiz->shuffle_questions ? $questionsQuery->inRandomOrder()->get() : $questionsQuery->get();

        if ($quiz->random_subset_count && $quiz->random_subset_count > 0) {
            $questions = $questions->take($quiz->random_subset_count);
        }

        $questionIds = $questions->pluck('id')->toArray();

        $attempt = $quiz->attempts()->create([
            'student_id' => $studentId,
            'attempt_number' => $pastAttemptsCount + 1,
            'started_at' => now(),
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

    public function submit(Request $request, Quiz $quiz): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        $attempt = $quiz->attempts()
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
        $questions = $quiz->questions()->whereIn('id', $questionIds)->get();

        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            $isCorrect = false;
            $pointsEarned = 0;

            if ($question->question_type === 'short_answer') {
                $isCorrect = strtolower(trim($submittedAnswer ?? '')) === strtolower(trim($question->correct_answer ?? ''));
                $pointsEarned = $isCorrect ? (int) $question->points : 0;
            } elseif (in_array($question->question_type, ['multiple_choice', 'true_false'], true)) {
                $isCorrect = $question->correct_answer === $submittedAnswer;
                $pointsEarned = $isCorrect ? (int) $question->points : 0;
            }

            $attempt->answers()->create([
                'question_id' => $question->id,
                'answer' => $submittedAnswer,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
            ]);

            $totalScore += $pointsEarned;
        }

        $attempt->update([
            'submitted_at' => now(),
            'score' => $totalScore,
            'status' => 'submitted',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz submitted successfully!',
            'data' => [
                'attempt' => $this->mapAttempt($attempt->fresh()),
                'total_score' => $totalScore,
            ],
        ]);
    }

    private function mapQuiz(Quiz $quiz, ?QuizAttempt $attempt, ?int $questionsCount = null): array
    {
        return [
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
            'attempt' => $attempt ? $this->mapAttempt($attempt) : null,
            'questions_count' => $questionsCount ?? (int) $quiz->questions()->count(),
        ];
    }

    private function mapAttempt(QuizAttempt $attempt): array
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

    private function mapAttemptDetailed(QuizAttempt $attempt): array
    {
        return [
            'attempt' => $this->mapAttempt($attempt),
            'answers' => $attempt->relationLoaded('answers')
                ? $attempt->answers->map(fn ($answer) => [
                    'id' => (int) $answer->id,
                    'attempt_id' => (int) $answer->attempt_id,
                    'question_id' => (int) $answer->question_id,
                    'answer' => $answer->answer ?? null,
                    'is_correct' => (bool) ($answer->is_correct ?? false),
                    'points_earned' => (int) ($answer->points_earned ?? 0),
                    'feedback' => $answer->feedback ?? null,
                    'question' => $answer->relationLoaded('question') && $answer->question ? $this->mapQuestion($answer->question, false, true) : null,
                ])->values()->all()
                : [],
        ];
    }

    private function mapQuestion(QuizQuestion $question, bool $hideCorrectAnswer = true, bool $includeExplanation = false): array
    {
        return [
            'id' => (int) $question->id,
            'quiz_id' => (int) $question->quiz_id,
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
