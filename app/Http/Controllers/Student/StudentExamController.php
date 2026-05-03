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
        
        // Get student's attempts
        $attempts = StudentExamAttempt::query()
            ->where('student_id', $studentId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->get()
            ->keyBy('exam_id');
        
        return view('student.exams.index', [
            'exams' => $exams,
            'attempts' => $attempts,
        ]);
    }
    
    public function show(Request $request, Exam $exam): View
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
        
        // Get or create attempt
        $attempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->first();
        
        // If exam hasn't started yet
        if ($exam->exam_date && $exam->exam_date->isFuture()) {
            return view('student.exams.upcoming', [
                'exam' => $exam,
            ]);
        }
        
        // If exam is past and no attempt
        if ($exam->exam_date && $exam->exam_date->isPast() && !$attempt) {
            return view('student.exams.missed', [
                'exam' => $exam,
            ]);
        }
        
        // If in progress
        if ($attempt && !$attempt->isSubmitted()) {
            return view('student.exams.take', [
                'exam' => $exam->load('questions'),
                'attempt' => $attempt,
            ]);
        }
        
        // Show results
        return view('student.exams.result', [
            'exam' => $exam->load('questions'),
            'attempt' => $attempt?->load('answers'),
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
        
        // Check if already has an attempt
        $existingAttempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->first();
        
        if ($existingAttempt) {
            return redirect()->route('student.exams.show', $exam);
        }
        
        // Create new attempt
        StudentExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $studentId,
            'started_at' => now(),
            'max_score' => $exam->max_score,
        ]);
        
        return redirect()->route('student.exams.show', $exam);
    }
    
    public function submit(Request $request, Exam $exam): RedirectResponse
    {
        $studentId = (int) $request->user()->id;
        
        $attempt = StudentExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $studentId)
            ->whereNull('submitted_at')
            ->firstOrFail();
        
        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string'],
        ]);
        
        $totalScore = 0;
        
        foreach ($validated['answers'] as $questionId => $answer) {
            $question = $exam->questions()->find($questionId);
            
            if (!$question) continue;
            
            // Calculate score for auto-gradable questions
            $score = null;
            if (in_array($question->question_type, ['multiple_choice', 'true_false'])) {
                $score = $question->correct_answer === $answer ? $question->points : 0;
                $totalScore += $score;
            }
            
            ExamAnswer::create([
                'attempt_id' => $attempt->id,
                'question_id' => $questionId,
                'answer' => $answer,
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
            ->with('success', 'Exam submitted successfully! Your score: ' . $totalScore . '/' . $exam->max_score);
    }
}
