<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\StudentExamAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StudentExamController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;
        
        $exams = Exam::query()
            ->whereHas('course.enrollments', fn ($q) => $q->where('student_id', $studentId))
            ->where('is_published', true)
            ->where('exam_type', 'online')
            ->with(['course'])
            ->orderBy('exam_date', 'asc')
            ->paginate(20);
        
        // Get student's latest attempts keyed by exam_id
        $attempts = StudentExamAttempt::query()
            ->where('student_id', $studentId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->orderBy('attempt_number', 'desc')
            ->get()
            ->unique('exam_id')
            ->keyBy('exam_id');
        
        return view('student.exams.index', [
            'exams' => $exams,
            'attempts' => $attempts,
        ]);
    }
    
    public function show(Request $request, Exam $exam): View|RedirectResponse
    {
        $studentId = (int) $request->user()->id;
        
        // Check if student is enrolled in this exam's course
        $isEnrolled = $exam->course->enrollments()
            ->where('student_id', $studentId)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->exists();
        
        if (!$isEnrolled) {
            abort(403, 'You are not enrolled in this course.');
        }
        
        // Check if exam hasn't started yet
        if ($exam->exam_date && $exam->exam_date->isFuture()) {
            return view('student.exams.upcoming', [
                'exam' => $exam,
            ]);
        }
        
        // Get student's attempts
        $attempts = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->orderBy('attempt_number')
            ->get();
            
        $activeAttempt = $attempts->where('status', 'in_progress')->first();
        
        // If in progress, go to take view directly
        if ($activeAttempt) {
            $questionIds = $activeAttempt->question_ids ?? [];
            if (!empty($questionIds)) {
                $questions = $exam->questions()
                    ->whereIn('id', $questionIds)
                    ->get()
                    ->sortBy(function ($q) use ($questionIds) {
                        return array_search($q->id, $questionIds);
                    })->values();
            } else {
                $questions = $exam->questions()->orderBy('order')->get();
            }

            return view('student.exams.take', [
                'exam' => $exam,
                'questions' => $questions,
                'attempt' => $activeAttempt,
            ]);
        }
        
        // If exam is past and no attempts
        if ($exam->due_date && $exam->due_date->isPast() && $attempts->isEmpty()) {
            return view('student.exams.missed', [
                'exam' => $exam,
            ]);
        }
        
        // If student wants to view a specific past attempt
        $selectedAttemptId = $request->query('attempt_id');
        $selectedAttempt = null;
        if ($selectedAttemptId) {
            $selectedAttempt = StudentExamAttempt::query()
                ->where('student_id', $studentId)
                ->where('exam_id', $exam->id)
                ->where('id', $selectedAttemptId)
                ->with('answers.question')
                ->first();
        } else {
            // Default to latest submitted attempt
            $selectedAttempt = $attempts->whereIn('status', ['submitted', 'graded'])->last();
            if ($selectedAttempt) {
                $selectedAttempt->load('answers.question');
            }
        }
        
        return view('student.exams.show', [
            'exam' => $exam,
            'attempts' => $attempts,
            'selectedAttempt' => $selectedAttempt,
        ]);
    }
    
    public function start(Request $request, Exam $exam): RedirectResponse
    {
        $studentId = (int) $request->user()->id;
        
        // Check enrollment
        $isEnrolled = $exam->course->enrollments()
            ->where('student_id', $studentId)
            ->exists();
        
        if (!$isEnrolled) {
            abort(403);
        }
        
        // Check if exam is published and available
        if (!$exam->is_published || !$exam->isOnline()) {
            return back()->with('error', 'This exam is not available.');
        }
        
        // Check exam time window
        if ($exam->exam_date && $exam->exam_date->isFuture()) {
            return back()->with('error', 'This exam has not started yet.');
        }

        // Check due date window (if any)
        if ($exam->due_date && $exam->due_date->isPast()) {
            return back()->with('error', 'The due date for this exam has passed.');
        }
        
        // Check if already has active attempt
        $activeAttempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->first();
        
        if ($activeAttempt) {
            return redirect()->route('student.exams.show', $exam);
        }

        // Check attempts limit
        $pastAttemptsCount = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if ($exam->attempts_allowed && $pastAttemptsCount >= $exam->attempts_allowed) {
            return back()->with('error', 'You have reached the maximum number of attempts allowed for this exam.');
        }

        // Determine question order and subset
        $questionsQuery = $exam->questions()->orderBy('order');
        if ($exam->shuffle_questions) {
            $questions = $questionsQuery->inRandomOrder()->get();
        } else {
            $questions = $questionsQuery->get();
        }

        if ($exam->random_subset_count && $exam->random_subset_count > 0) {
            $questions = $questions->take($exam->random_subset_count);
        }

        $questionIds = $questions->pluck('id')->toArray();
        
        // Create new attempt
        StudentExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $studentId,
            'attempt_number' => $pastAttemptsCount + 1,
            'started_at' => now(),
            'max_score' => $exam->max_score,
            'status' => 'in_progress',
            'question_ids' => $questionIds,
        ]);
        
        return redirect()->route('student.exams.show', $exam);
    }
    
    public function submit(Request $request, Exam $exam): RedirectResponse
    {
        $studentId = (int) $request->user()->id;
        
        $attempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->firstOrFail();
        
        $questionIds = $attempt->question_ids ?? [];
        $answers = $this->normalizeSubmittedAnswers($request, $questionIds);

        $validated = validator([
            'answers' => $answers,
        ], [
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string'],
        ])->validate();
        
        $answers = $validated['answers'] ?? [];
        $totalScore = 0;
        
        // Load questions for the attempt
        $questions = $exam->questions()->whereIn('id', $questionIds)->get();

        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            
            // Calculate score for auto-gradable questions
            $score = null;
            if (in_array($question->question_type, ['multiple_choice', 'true_false'])) {
                $score = $question->correct_answer === $submittedAnswer ? $question->points : 0;
                $totalScore += $score;
            }
            
            ExamAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer' => $submittedAnswer,
                'score' => $score,
            ]);
        }
        
        // Update attempt
        $attempt->update([
            'submitted_at' => now(),
            'score' => $totalScore,
            'status' => 'submitted',
        ]);
        
        return redirect()->route('student.exams.show', $exam)
            ->with('success', 'Exam submitted successfully! Score: ' . $totalScore);
    }

    private function normalizeSubmittedAnswers(Request $request, array $questionIds = []): array
    {
        $rawAnswers = $request->input('answers', $request->input('answer', []));

        if (!is_array($rawAnswers)) {
            return [];
        }

        $isSequential = array_keys($rawAnswers) === range(0, count($rawAnswers) - 1);
        $normalized = [];

        foreach ($rawAnswers as $key => $value) {
            $questionId = $key;
            $answerValue = $value;

            if (is_array($value)) {
                $questionId = $value['question_id'] ?? $questionIds[$key] ?? $key;
                $answerValue = $value['answer'] ?? null;
            } elseif ($isSequential) {
                $questionId = $questionIds[$key] ?? $key;
            }

            if ($answerValue === null) {
                $normalized[(string) $questionId] = null;
                continue;
            }

            if (is_scalar($answerValue)) {
                $normalized[(string) $questionId] = (string) $answerValue;
            }
        }

        return $normalized;
    }
}
