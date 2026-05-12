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
        
        // Get student's attempt
        $attempt = $quiz->attempts()
            ->where('student_id', $studentId)
            ->first();
        
        // If quiz has past due and no attempt
        if ($quiz->due_date && $quiz->due_date->isPast() && !$attempt) {
            return view('student.quizzes.missed', [
                'quiz' => $quiz,
            ]);
        }
        
        // If in progress
        if ($attempt && $attempt->status !== 'submitted') {
            return view('student.quizzes.take', [
                'quiz' => $quiz->load('questions'),
                'attempt' => $attempt,
            ]);
        }
        
        // Show results or take page
        return view('student.quizzes.show', [
            'quiz' => $quiz->load('questions'),
            'attempt' => $attempt,
        ]);
    }
    
    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        $studentId = (int) $request->user()->id;

        // Check enrollment
        $isEnrolled = $quiz->course->enrollments()
            ->where('student_id', $studentId)
            ->exists();
        
        if (!$isEnrolled) {
            abort(403);
        }
        
        // Check if quiz is published
        if (!$quiz->is_published) {
            return back()->with('error', 'This quiz is not available.');
        }
        
        // Check if already has an attempt
        $existingAttempt = $quiz->attempts()
            ->where('student_id', $studentId)
            ->first();
        
        if ($existingAttempt) {
            return redirect()->route('student.quizzes.show', $quiz);
        }
        
        // Create new attempt
        $quiz->attempts()->create([
            'student_id' => $studentId,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);
        
        return redirect()->route('student.quizzes.show', $quiz);
    }
    
    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        $studentId = (int) $request->user()->id;

        $attempt = $quiz->attempts()
            ->where('student_id', $studentId)
            ->where('status', '!=', 'submitted')
            ->firstOrFail();
        
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);
        
        $totalScore = 0;
        $maxScore = 0;
        
        foreach ($validated['answers'] as $questionId => $answer) {
            $question = $quiz->questions()->find($questionId);
            
            if (!$question) continue;
            
            $maxScore += $question->points;
            
            // Calculate score for auto-gradable questions
            $score = null;
            if (in_array($question->question_type, ['multiple_choice', 'true_false'])) {
                $score = $question->correct_answer === $answer ? $question->points : 0;
                $totalScore += $score;
            }
        }
        
        // Update attempt
        $attempt->update([
            'submitted_at' => now(),
            'score' => $totalScore,
            'status' => 'submitted',
        ]);
        
        return redirect()->route('student.quizzes.show', $quiz)
            ->with('success', 'Quiz submitted successfully! Your score: ' . $totalScore . '/' . $maxScore);
    }
}
