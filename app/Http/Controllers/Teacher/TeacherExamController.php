<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\Course;
use App\Models\Exam;
use App\Models\QuestionBank;
use App\Models\StudentExamAttempt;
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

        $questionBanks = QuestionBank::where('teacher_id', $request->user()->id)
            ->with('questions')
            ->get();

        return view('teacher.exams.create', [
            'course' => $course,
            'questionBanks' => $questionBanks,
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
            'instructions' => ['nullable', 'string'],
            'exam_type' => ['required', 'in:face_to_face,online'],
            'exam_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:exam_date'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:480'],
            'attempts_allowed' => ['nullable', 'integer', 'min:1', 'max:10'],
            'max_score' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'location' => ['nullable', 'string', 'max:255'],
            'feedback_type' => ['nullable', 'in:instant,delayed'],
            'show_results' => ['nullable', 'boolean'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'random_subset_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'question_bank_id' => ['nullable', 'exists:question_banks,id'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['exists:bank_questions,id'],
            'questions' => ['nullable', 'array'],
            'questions.*.text' => ['required_with:questions', 'string'],
            'questions.*.type' => ['required_with:questions', 'in:multiple_choice,true_false,short_answer,essay'],
            'questions.*.points' => ['required_with:questions', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.correct' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.explanation' => ['nullable', 'string'],
        ]);

        $isOnline = $validated['exam_type'] === 'online';
        $questionIds = collect($validated['question_ids'] ?? [])->filter()->map(fn ($id) => (int) $id)->values();
        $inlineQuestions = collect($validated['questions'] ?? [])->filter(fn ($question) => filled($question['text'] ?? null))->values();

        if ($isOnline) {
            $request->validate([
                'duration' => ['required', 'integer', 'min:1', 'max:480'],
            ]);

            if ($questionIds->isEmpty() && $inlineQuestions->isEmpty()) {
                return back()
                    ->withErrors(['questions' => 'Please add at least one question with valid points.'])
                    ->withInput();
            }
        } else {
            $request->validate([
                'instructions' => ['required', 'string'],
            ]);
        }

        $totalPoints = 0;
        $selectedBankQuestions = collect();

        if ($isOnline && !empty($validated['question_bank_id']) && $questionIds->isNotEmpty()) {
            $selectedBankQuestions = BankQuestion::where('question_bank_id', $validated['question_bank_id'])
                ->whereIn('id', $questionIds)
                ->get();

            $totalPoints += (int) $selectedBankQuestions->sum('points');
        }

        if ($isOnline) {
            foreach ($inlineQuestions as $q) {
                $totalPoints += (int) ($q['points'] ?? 1);
            }

            if ($totalPoints <= 0) {
                return back()
                    ->withErrors(['questions' => 'Please add at least one question with valid points.'])
                    ->withInput();
            }
        }

        $exam = Exam::create([
            'course_id' => (int) $course->id,
            'exam_type' => $validated['exam_type'],
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'],
            'due_date' => $validated['due_date'] ?? null,
            'duration' => $isOnline ? (int) ($validated['duration'] ?? 0) : 0,
            'attempts_allowed' => (int) ($validated['attempts_allowed'] ?? 1),
            'max_score' => $isOnline ? $totalPoints : (int) ($validated['max_score'] ?? 0),
            'location' => $isOnline ? null : ($validated['location'] ?? null),
            'instructions' => $validated['instructions'] ?? null,
            'feedback_type' => $validated['feedback_type'] ?? 'instant',
            'results_released' => ($validated['feedback_type'] ?? 'instant') === 'instant',
            'show_results' => (bool) ($validated['show_results'] ?? true),
            'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
            'random_subset_count' => isset($validated['random_subset_count']) ? (int) $validated['random_subset_count'] : null,
            'is_published' => false,
        ]);

        if ($isOnline) {
            $order = 0;

            foreach ($selectedBankQuestions as $bankQuestion) {
                $exam->questions()->create([
                    'question_text' => $bankQuestion->question_text,
                    'question_type' => $bankQuestion->question_type,
                    'options' => $bankQuestion->options,
                    'correct_answer' => $bankQuestion->correct_answer,
                    'explanation' => $bankQuestion->explanation,
                    'points' => (int) $bankQuestion->points,
                    'order' => ++$order,
                ]);
            }

            foreach ($inlineQuestions as $qData) {
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
                    $correctAnswer = $qData['correct_answer'] ?? null;
                }

                $exam->questions()->create([
                    'question_text' => $qData['text'],
                    'question_type' => $qData['type'],
                    'options' => $options,
                    'correct_answer' => $correctAnswer,
                    'explanation' => $qData['explanation'] ?? null,
                    'points' => (int) $qData['points'],
                    'order' => ++$order,
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
            ? 'Online exam created successfully with ' . $exam->questions()->count() . ' questions.'
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
            'exam_type' => ['required', 'in:face_to_face,online,scheduled'],
            'exam_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'duration' => ['nullable', 'integer', 'min:1', 'max:480'],
            'attempts_allowed' => ['nullable', 'integer', 'min:1', 'max:10'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'location' => ['nullable', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'feedback_type' => ['nullable', 'in:instant,delayed'],
            'show_results' => ['nullable', 'boolean'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'random_subset_count' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $exam->update([
            'title' => (string) $validated['title'],
            'exam_type' => $validated['exam_type'],
            'description' => $validated['description'] ?? null,
            'exam_date' => $validated['exam_date'],
            'due_date' => $validated['due_date'] ?? null,
            'duration' => (int) $validated['duration'],
            'attempts_allowed' => (int) ($validated['attempts_allowed'] ?? 1),
            'max_score' => (int) ($validated['max_score'] ?? $exam->max_score ?? 100),
            'location' => $validated['location'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'feedback_type' => $validated['feedback_type'] ?? $exam->feedback_type ?? 'instant',
            'show_results' => (bool) ($validated['show_results'] ?? true),
            'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
            'random_subset_count' => isset($validated['random_subset_count']) ? (int) $validated['random_subset_count'] : null,
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

        DB::transaction(function () use ($exam): void {
            if (Schema::hasTable('student_exam_attempts') && Schema::hasColumn('student_exam_attempts', 'exam_id')) {
                $attemptIds = DB::table('student_exam_attempts')
                    ->where('exam_id', $exam->id)
                    ->pluck('id');

                if ($attemptIds->isNotEmpty() && Schema::hasTable('exam_answers') && Schema::hasColumn('exam_answers', 'attempt_id')) {
                    DB::table('exam_answers')->whereIn('attempt_id', $attemptIds->all())->delete();
                }

                DB::table('student_exam_attempts')->where('exam_id', $exam->id)->delete();
            }

            if (Schema::hasTable('exam_questions') && Schema::hasColumn('exam_questions', 'exam_id')) {
                DB::table('exam_questions')->where('exam_id', $exam->id)->delete();
            }

            $exam->delete();
        });
        \Log::debug('Exam ' . $exam->id . ' deleted successfully');

        return redirect()->route('teacher.exams.index')->with('success', 'Exam deleted successfully.');
    }

    // Online Exam Question Management
    public function questions(Request $request, Exam $exam): View
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ($exam->isFaceToFace()) {
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

        if ($exam->isFaceToFace()) {
            return redirect()->route('teacher.exams.show', $exam)->with('error', 'Only online exams can have questions.');
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string'],
            'correct_answer' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
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
            'correct_answer' => in_array($validated['question_type'], ['multiple_choice', 'true_false']) ? ($validated['correct_answer'] ?? null) : ($validated['correct_answer'] ?? null),
            'explanation' => $validated['explanation'] ?? null,
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

    /**
     * Release results of the exam to students.
     */
    public function releaseResults(Request $request, Exam $exam)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $exam->update(['results_released' => true]);

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam results released to students.');
    }

    /**
     * Import questions from a question bank.
     */
    public function importFromBank(Request $request, Exam $exam)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'question_bank_id' => ['required', 'exists:question_banks,id'],
            'question_ids' => ['required', 'array'],
            'question_ids.*' => ['exists:bank_questions,id'],
        ]);

        $bankQuestions = \App\Models\BankQuestion::where('question_bank_id', $validated['question_bank_id'])
            ->whereIn('id', $validated['question_ids'])
            ->get();

        $maxOrder = $exam->questions()->max('order') ?? 0;

        foreach ($bankQuestions as $bq) {
            $exam->questions()->create([
                'question_text' => $bq->question_text,
                'question_type' => $bq->question_type,
                'options' => $bq->options,
                'correct_answer' => $bq->correct_answer,
                'explanation' => $bq->explanation,
                'points' => $bq->points,
                'order' => ++$maxOrder,
            ]);
        }

        // Update exam max_score
        $totalPoints = $exam->getTotalPoints();
        $exam->update(['max_score' => $totalPoints]);

        return redirect()->route('teacher.exams.questions', $exam)->with('success', 'Questions imported successfully.');
    }

    public function showAttempt(Request $request, Exam $exam, StudentExamAttempt $attempt): View
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $attempt->exam_id !== (int) $exam->id) {
            abort(404);
        }

        $attempt->load(['student', 'answers.question']);

        return view('teacher.exams.attempt', [
            'exam' => $exam,
            'attempt' => $attempt,
        ]);
    }

    public function gradeAttempt(Request $request, Exam $exam, StudentExamAttempt $attempt)
    {
        if ((int) $exam->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $attempt->exam_id !== (int) $exam->id) {
            abort(404);
        }

        $validated = $request->validate([
            'scores' => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'array'],
            'feedback.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $attempt->load('answers.question');

        $totalScore = 0;

        foreach ($attempt->answers as $answer) {
            $questionId = (int) $answer->question_id;
            $maxPoints = (int) ($answer->question?->points ?? 0);

            if (array_key_exists($questionId, $validated['scores'] ?? [])) {
                $adjustedScore = min(max(0, (int) round((float) $validated['scores'][$questionId])), $maxPoints);
            } else {
                $adjustedScore = (int) ($answer->points_earned ?? 0);
            }

            $feedbackText = $validated['feedback'][$questionId] ?? $answer->feedback;
            $question = $answer->question;
            $isObjective = $question?->canAutoGrade() ?? false;

            $answer->update([
                'points_earned' => $adjustedScore,
                'feedback' => filled($feedbackText) ? $feedbackText : null,
                'is_correct' => $isObjective && $maxPoints > 0 ? $adjustedScore === $maxPoints : $answer->is_correct,
            ]);

            $totalScore += $adjustedScore;
        }

        $attemptUpdates = [
            'score' => $totalScore,
            'status' => 'graded',
        ];

        if (Schema::hasColumn('student_exam_attempts', 'graded_at')) {
            $attemptUpdates['graded_at'] = now();
        }

        if (Schema::hasColumn('student_exam_attempts', 'graded_by')) {
            $attemptUpdates['graded_by'] = (int) $request->user()->id;
        }

        $attempt->update($attemptUpdates);

        return redirect()
            ->route('teacher.exams.attempts.show', [$exam, $attempt])
            ->with('success', 'Exam attempt reviewed successfully.');
    }
}
