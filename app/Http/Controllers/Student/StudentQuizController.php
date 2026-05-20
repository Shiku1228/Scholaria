<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class StudentQuizController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;
        
        $quizzes = Quiz::query()
            ->whereHas('course.enrollments', fn ($q) => $q->where('student_id', $studentId))
            ->where('is_published', true)
            ->with(['course'])
            ->orderBy('due_date', 'asc')
            ->paginate(20);
        
        return view('student.quizzes.index', [
            'quizzes' => $quizzes,
        ]);
    }
    
    public function show(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $studentId = (int) $request->user()->id;

        // Check if student is enrolled in this quiz's course
        $isEnrolled = $quiz->course->enrollments()
            ->where('student_id', $studentId)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->exists();
        
        if (!$isEnrolled) {
            abort(403, 'You are not enrolled in this course.');
        }

        // Check start date window
        if ($quiz->start_date && $quiz->start_date->isFuture()) {
            return back()->with('error', 'This quiz is not open yet. It starts on ' . $quiz->start_date->format('M j, Y g:i A'));
        }
        
        // Get student's attempts
        $attempts = $quiz->attempts()
            ->where('student_id', $studentId)
            ->orderBy('attempt_number')
            ->get();
            
        $activeAttempt = $attempts->where('status', 'in_progress')->first();
        
        // If in progress, go to take view directly
        if ($activeAttempt) {
            $questionIds = $activeAttempt->question_ids ?? [];
            if (!empty($questionIds)) {
                $questions = $quiz->questions()
                    ->whereIn('id', $questionIds)
                    ->get()
                    ->sortBy(function ($q) use ($questionIds) {
                        return array_search($q->id, $questionIds);
                    })->values();
            } else {
                $questions = $quiz->questions()->orderBy('order')->get();
            }

            return view('student.quizzes.take', [
                'quiz' => $quiz,
                'questions' => $questions,
                'attempt' => $activeAttempt,
            ]);
        }
        
        // If student wants to view a specific past attempt
        $selectedAttemptId = $request->query('attempt_id');
        $selectedAttempt = null;
        if ($selectedAttemptId) {
            $selectedAttempt = $quiz->attempts()
                ->where('student_id', $studentId)
                ->where('id', $selectedAttemptId)
                ->with('answers.question')
                ->first();
        } else {
            // Default to latest submitted attempt
            $selectedAttempt = $attempts->where('status', 'submitted')->last();
            if ($selectedAttempt) {
                $selectedAttempt->load('answers.question');
            }
        }
        
        return view('student.quizzes.show', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'selectedAttempt' => $selectedAttempt,
        ]);
    }
    
    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $studentId = (int) $request->user()->id;

        // Check enrollment
        $isEnrolled = $quiz->course->enrollments()
            ->where('student_id', $studentId)
            ->whereRaw('LOWER(status) = ?', ['active'])
            ->exists();
        
        if (!$isEnrolled) {
            abort(403);
        }
        
        // Check if quiz is published
        if (!$quiz->is_published) {
            return back()->with('error', 'This quiz is not available.');
        }

        // Check start date window
        if ($quiz->start_date && $quiz->start_date->isFuture()) {
            return back()->with('error', 'This quiz is not open yet.');
        }

        // Check due date window (if any)
        if ($quiz->due_date && $quiz->due_date->isPast()) {
            return back()->with('error', 'The due date for this quiz has passed.');
        }
        
        // Check if already has active attempt
        $activeAttempt = $quiz->attempts()
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->first();
        
        if ($activeAttempt) {
            return redirect()->route('student.quizzes.show', $quiz);
        }

        // Check attempts limit
        $pastAttemptsCount = $quiz->attempts()
            ->where('student_id', $studentId)
            ->where('status', 'submitted')
            ->count();

        if ($quiz->attempts_allowed && $pastAttemptsCount >= $quiz->attempts_allowed) {
            return back()->with('error', 'You have reached the maximum number of attempts allowed for this quiz.');
        }

        // Determine question order and subset
        $questionsQuery = $quiz->questions()->orderBy('order');
        if ($quiz->shuffle_questions) {
            $questions = $questionsQuery->inRandomOrder()->get();
        } else {
            $questions = $questionsQuery->get();
        }

        if ($quiz->random_subset_count && $quiz->random_subset_count > 0) {
            $questions = $questions->take($quiz->random_subset_count);
        }

        $questionIds = $questions->pluck('id')->toArray();
        
        // Create new attempt
        $quiz->attempts()->create([
            'student_id' => $studentId,
            'attempt_number' => $pastAttemptsCount + 1,
            'started_at' => now(),
            'status' => 'in_progress',
            'question_ids' => $questionIds,
        ]);
        
        return redirect()->route('student.quizzes.show', $quiz);
    }
    
    public function submit(Request $request, Quiz $quiz): RedirectResponse
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
        
        // Load questions for the attempt
        $questionIds = $attempt->question_ids ?? [];
        $questions = $quiz->questions()->whereIn('id', $questionIds)->get();

        foreach ($questions as $question) {
            $submittedAnswer = $answers[$question->id] ?? null;
            
            $isCorrect = false;
            $pointsEarned = 0;
            
            if ($question->question_type === 'short_answer') {
                $isCorrect = strtolower(trim($submittedAnswer ?? '')) === strtolower(trim($question->correct_answer ?? ''));
                $pointsEarned = $isCorrect ? $question->points : 0;
            } elseif (in_array($question->question_type, ['multiple_choice', 'true_false'])) {
                $isCorrect = $question->correct_answer === $submittedAnswer;
                $pointsEarned = $isCorrect ? $question->points : 0;
            } else {
                $isCorrect = false;
                $pointsEarned = 0;
            }

            $attempt->answers()->create([
                'question_id' => $question->id,
                'answer' => $submittedAnswer,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
            ]);

            $totalScore += $pointsEarned;
        }
        
        // Update attempt
        $attempt->update([
            'submitted_at' => now(),
            'score' => $totalScore,
            'status' => 'submitted',
        ]);
        
        return redirect()->route('student.quizzes.show', $quiz)
            ->with('success', 'Quiz submitted successfully! Score: ' . $totalScore);
    }
}
