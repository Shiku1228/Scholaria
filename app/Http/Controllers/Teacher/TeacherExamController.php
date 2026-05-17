<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherExamController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);

        $exams = collect();
        $courses = collect();

        try {
            // Get teacher's courses
            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $courses = DB::table('courses')
                    ->where('teacher_id', $teacherId)
                    ->select('id', 'title', 'course_number')
                    ->orderBy('title')
                    ->get();
            }

            // Get exams
            if (Schema::hasTable('exams')) {
                $examsQuery = Exam::query()
                    ->whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->with(['course']);

                if ($courseId > 0) {
                    $examsQuery->where('course_id', $courseId);
                }

                $exams = $examsQuery
                    ->orderBy('exam_date', 'asc')
                    ->orderBy('title', 'asc')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $exams = collect();
            $courses = collect();
        }

        return view('teacher.exams.index', [
            'exams' => $exams,
            'courses' => $courses,
            'filters' => ['course_id' => $courseId],
        ]);
    }

    public function create(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.exams.create', [
            'course' => $course,
        ]);
    }

    public function store(Request $request, Course $course)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_type' => ['required', 'in:scheduled,online'],
            'exam_date' => ['required', 'date', 'after:now'],
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'questions' => ['nullable', 'array'],
            'questions.*.text' => ['required_with:questions', 'string'],
            'questions.*.type' => ['required_with:questions', 'in:multiple_choice,true_false,short_answer,essay'],
            'questions.*.points' => ['required_with:questions', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct' => ['nullable', 'string'],
        ]);

        $isOnline = $validated['exam_type'] === 'online';

        // Calculate total points for online exams
        $totalPoints = 0;
        if ($isOnline && !empty($validated['questions'])) {
            foreach ($validated['questions'] as $q) {
                $totalPoints += (int) ($q['points'] ?? 1);
            }
        }

        $exam = Exam::create([
            'course_id' => (int) $course->id,
            'exam_type' => $validated['exam_type'],
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'],
            'duration' => (int) $validated['duration'],
            'max_score' => $isOnline ? $totalPoints : (int) ($validated['max_score'] ?? 100),
            'location' => $isOnline ? null : ($validated['location'] ?? null),
            'instructions' => $validated['instructions'] ?? null,
            'is_published' => false,
        ]);

        // Create questions for online exams
        if ($isOnline && !empty($validated['questions'])) {
            $order = 0;
            foreach ($validated['questions'] as $qData) {
                $options = null;
                $correctAnswer = null;

                if ($qData['type'] === 'multiple_choice') {
                    $options = [
                        'A' => $qData['options']['A'] ?? '',
                        'B' => $qData['options']['B'] ?? '',
                        'C' => $qData['options']['C'] ?? '',
                        'D' => $qData['options']['D'] ?? '',
                    ];
                    $correctAnswer = $qData['correct'] ?? null;
                } elseif ($qData['type'] === 'true_false') {
                    $correctAnswer = $qData['correct'] ?? null;
                } else {
                    // Short answer and essay
                    $correctAnswer = $qData['correct_answer'] ?? null;
                }

                $exam->questions()->create([
                    'question_text' => $qData['text'],
                    'question_type' => $qData['type'],
                    'options' => $options,
                    'correct_answer' => $correctAnswer,
                    'points' => (int) $qData['points'],
                    'order' => $order++,
                ]);
            }
        }

        // Prepare notification message
        $examTypeLabel = $isOnline ? 'New Online Exam' : 'New Exam Scheduled';
        $message = $isOnline
            ? 'New online exam "' . (string) $exam->title . '" has been created. Take the exam on ' . $exam->exam_date->format('M j, Y \a\t g:i A') . ' in ' . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.'
            : 'New exam "' . (string) $exam->title . '" has been scheduled for ' . $exam->exam_date->format('M j, Y \a\t g:i A') . ' in ' . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.';

        // Notify students
        $studentIdsQuery = DB::table('enrollments')->where('course_id', (int) $course->id);
        if (Schema::hasColumn('enrollments', 'status')) {
            $studentIdsQuery->whereRaw('LOWER(status) = ?', ['active']);
        }
        $studentIds = $studentIdsQuery->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        
        if (!empty($studentIds)) {
            $students = User::query()->whereIn('id', $studentIds)->get();
            foreach ($students as $student) {
                $student->notify(new CourseEventNotification(
                    $examTypeLabel,
                    $message,
                    route('student.exams.show', $exam)
                ));
            }
        }

        $successMessage = $isOnline
            ? 'Online exam created successfully with ' . count($validated['questions'] ?? []) . ' questions.'
            : 'Exam scheduled successfully.';

        return redirect()->route('teacher.exams.show', $exam)->with('success', $successMessage);
    }

    public function show(Request $request, Exam $exam): View
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $attempts = collect();
        
        try {
            if (Schema::hasTable('student_exam_attempts')) {
                $attempts = $exam->attempts()
                    ->with('student')
                    ->orderByDesc('started_at')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $attempts = collect();
        }

        return view('teacher.exams.show', [
            'exam' => $exam,
            'attempts' => $attempts,
        ]);
    }

    public function edit(Request $request, Exam $exam): View
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.exams.edit', [
            'exam' => $exam,
        ]);
    }

    public function update(Request $request, Exam $exam)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exam_type' => ['required', 'in:scheduled,online'],
            'exam_date' => ['required', 'date', 'after:now'],
            'duration' => ['required', 'integer', 'min:15', 'max:480'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
        ]);

        $exam->update([
            'title' => (string) $validated['title'],
            'exam_type' => $validated['exam_type'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'],
            'duration' => (int) $validated['duration'],
            'max_score' => (int) ($validated['max_score'] ?? $exam->max_score ?? 100),
            'location' => $validated['location'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
        ]);

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam updated successfully.');
    }

    public function destroy(Request $request, Exam $exam)
    {
        \Log::debug('Destroy called for exam ID: ' . $exam->id);
        
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            \Log::warning('Unauthorized delete attempt for exam ' . $exam->id . ' by user ' . $request->user()->id);
            abort(403);
        }

        $exam->delete();
        \Log::debug('Exam ' . $exam->id . ' deleted successfully');

        return redirect()->route('teacher.exams.index')->with('success', 'Exam deleted successfully.');
    }

    // Online Exam Question Management
    public function questions(Request $request, Exam $exam): View
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($exam->isScheduled()) {
            return redirect()->route('teacher.exams.show', $exam)->with('error', 'Only online exams can have questions.');
        }

        $exam->load('questions');

        return view('teacher.exams.questions', [
            'exam' => $exam,
            'questions' => $exam->questions,
        ]);
    }

    public function addQuestion(Request $request, Exam $exam)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($exam->isScheduled()) {
            return redirect()->route('teacher.exams.show', $exam)->with('error', 'Only online exams can have questions.');
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        // Require correct_answer for multiple choice and true/false
        if (in_array($validated['question_type'], ['multiple_choice', 'true_false'])) {
            if (empty($validated['correct_answer'])) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Please select the correct answer for this question.');
            }
        }

        $order = $exam->questions()->count();

        // Format options with A,B,C,D keys for multiple choice
        $options = null;
        if ($validated['question_type'] === 'multiple_choice' && !empty($validated['options'])) {
            $optionKeys = ['A', 'B', 'C', 'D'];
            $formattedOptions = [];
            foreach ($validated['options'] as $index => $value) {
                if (isset($optionKeys[$index]) && !empty($value)) {
                    $formattedOptions[$optionKeys[$index]] = $value;
                }
            }
            $options = $formattedOptions;
        }

        $question = $exam->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'options' => $options,
            'correct_answer' => in_array($validated['question_type'], ['multiple_choice', 'true_false']) ? ($validated['correct_answer'] ?? null) : null,
            'points' => $validated['points'],
            'order' => $order,
        ]);

        // Update exam max_score based on total question points
        $totalPoints = $exam->getTotalPoints();
        $exam->update(['max_score' => $totalPoints]);

        return redirect()->route('teacher.exams.questions', $exam)->with('success', 'Question added successfully.');
    }

    public function removeQuestion(Request $request, Exam $exam, $questionId)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $question = $exam->questions()->findOrFail($questionId);
        $question->delete();

        // Reorder remaining questions
        $exam->questions()->orderBy('order')->each(function ($q, $index) {
            $q->update(['order' => $index]);
        });

        // Update exam max_score
        $totalPoints = $exam->getTotalPoints();
        $exam->update(['max_score' => $totalPoints]);

        return redirect()->route('teacher.exams.questions', $exam)->with('success', 'Question removed successfully.');
    }

    public function publish(Request $request, Exam $exam)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($exam->isOnline() && !$exam->hasQuestions()) {
            return redirect()->route('teacher.exams.questions', $exam)->with('error', 'Add at least one question before publishing.');
        }

        $exam->update(['is_published' => true]);

        // Notify students
        $studentIdsQuery = DB::table('enrollments')->where('course_id', (int) $exam->course_id);
        if (Schema::hasColumn('enrollments', 'status')) {
            $studentIdsQuery->whereRaw('LOWER(status) = ?', ['active']);
        }
        $studentIds = $studentIdsQuery->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        
        if (!empty($studentIds)) {
            $students = User::query()->whereIn('id', $studentIds)->get();
            $examType = $exam->isOnline() ? 'Online exam' : 'Exam';
            foreach ($students as $student) {
                $student->notify(new CourseEventNotification(
                    $examType . ' Published',
                    $examType . ' "' . $exam->title . '" is now available. Scheduled for ' . $exam->exam_date->format('M j, Y \a\t g:i A') . '.',
                    route('student.exams.show', $exam)
                ));
            }
        }

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam published successfully.');
    }

    public function unpublish(Request $request, Exam $exam)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $exam->update(['is_published' => false]);

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam unpublished.');
    }
}
