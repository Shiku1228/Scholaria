<?php

namespace App\Http\Controllers\Teacher;

use App\Exports\QuizScoresExport;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class TeacherQuizController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);

        $quizzes = collect();
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

            // Get quizzes
            if (Schema::hasTable('quizzes')) {
                $quizzesQuery = Quiz::query()
                    ->whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->with(['course']);

                if ($courseId > 0) {
                    $quizzesQuery->where('course_id', $courseId);
                }

                $quizzes = $quizzesQuery
                    ->orderByDesc('id')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $quizzes = collect();
            $courses = collect();
        }

        return view('teacher.quizzes.index', [
            'quizzes' => $quizzes,
            'courses' => $courses,
            'filters' => ['course_id' => $courseId],
        ]);
    }

    public function create(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $questionBanks = \App\Models\QuestionBank::where('teacher_id', $request->user()->id)
            ->with('questions')
            ->get();

        return view('teacher.quizzes.create', [
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
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'time_limit' => ['nullable', 'integer', 'min:1', 'max:480'],
            'attempts_allowed' => ['nullable', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'random_subset_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'show_results' => ['nullable', 'boolean'],
            'feedback_type' => ['nullable', 'in:instant,delayed'],
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

        $quiz = Quiz::create([
            'course_id' => (int) $course->id,
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'max_score' => (int) ($validated['max_score'] ?? 100),
            'time_limit' => (int) ($validated['time_limit'] ?? 60),
            'attempts_allowed' => (int) ($validated['attempts_allowed'] ?? 1),
            'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? false),
            'random_subset_count' => isset($validated['random_subset_count']) ? (int) $validated['random_subset_count'] : null,
            'show_results' => (bool) ($validated['show_results'] ?? true),
            'feedback_type' => $validated['feedback_type'] ?? 'instant',
            'results_released' => ($validated['feedback_type'] ?? 'instant') === 'instant',
        ]);

        $order = 0;

        // 1. Copy questions from question bank if selected
        if (!empty($validated['question_bank_id']) && !empty($validated['question_ids'])) {
            $bankQuestions = \App\Models\BankQuestion::where('question_bank_id', $validated['question_bank_id'])
                ->whereIn('id', $validated['question_ids'])
                ->get();

            foreach ($bankQuestions as $bq) {
                $quiz->questions()->create([
                    'question_text' => $bq->question_text,
                    'question_type' => $bq->question_type,
                    'options' => $bq->options,
                    'correct_answer' => $bq->correct_answer,
                    'explanation' => $bq->explanation,
                    'points' => $bq->points,
                    'order' => ++$order,
                ]);
            }
        }

        // 2. Add direct inline questions if provided
        if (!empty($validated['questions'])) {
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

                } elseif ($qData['type'] === 'short_answer') {

                    $correctAnswer = $qData['correct_answer'] ?? null;
                } else {
                    $correctAnswer = $qData['correct_answer'] ?? null;
                }

                $quiz->questions()->create([
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

        // 3. Update total quiz points
        $this->updateQuizPoints($quiz);

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', 'Quiz created successfully.');
    }

    public function show(Request $request, Quiz $quiz): View
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $attempts = collect();
        
        try {
            if (Schema::hasTable('quiz_attempts')) {
                $attempts = $quiz->attempts()
                    ->with('student')
                    ->orderByDesc('started_at')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $attempts = collect();
        }

        return view('teacher.quizzes.show', [
            'quiz' => $quiz,
            'attempts' => $attempts,
        ]);
    }

    public function edit(Request $request, Quiz $quiz): View
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.quizzes.edit', [
            'quiz' => $quiz,
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'time_limit' => ['nullable', 'integer', 'min:1', 'max:480'],
            'attempts_allowed' => ['nullable', 'integer', 'min:1', 'max:10'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'random_subset_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'show_results' => ['nullable', 'boolean'],
            'feedback_type' => ['nullable', 'in:instant,delayed'],
        ]);

        $quiz->update([
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'max_score' => (int) ($validated['max_score'] ?? $quiz->max_score ?? 100),
            'time_limit' => (int) ($validated['time_limit'] ?? $quiz->time_limit ?? 60),
            'attempts_allowed' => (int) ($validated['attempts_allowed'] ?? $quiz->attempts_allowed ?? 1),
            'shuffle_questions' => (bool) ($validated['shuffle_questions'] ?? $quiz->shuffle_questions ?? false),
            'random_subset_count' => isset($validated['random_subset_count']) ? (int) $validated['random_subset_count'] : null,
            'show_results' => (bool) ($validated['show_results'] ?? $quiz->show_results ?? true),
            'feedback_type' => $validated['feedback_type'] ?? $quiz->feedback_type ?? 'instant',
        ]);

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        DB::transaction(function () use ($quiz): void {
            if (Schema::hasTable('quiz_attempts') && Schema::hasColumn('quiz_attempts', 'quiz_id')) {
                $attemptIds = DB::table('quiz_attempts')
                    ->where('quiz_id', $quiz->id)
                    ->pluck('id');

                if ($attemptIds->isNotEmpty() && Schema::hasTable('quiz_answers') && Schema::hasColumn('quiz_answers', 'attempt_id')) {
                    DB::table('quiz_answers')->whereIn('attempt_id', $attemptIds->all())->delete();
                }

                DB::table('quiz_attempts')->where('quiz_id', $quiz->id)->delete();
            }

            if (Schema::hasTable('quiz_questions') && Schema::hasColumn('quiz_questions', 'quiz_id')) {
                DB::table('quiz_questions')->where('quiz_id', $quiz->id)->delete();
            }

            $quiz->delete();
        });

        return redirect()->route('teacher.quizzes.index')->with('success', 'Quiz deleted successfully.');
    }

    /**
     * Show quiz questions management page.
     */
    public function questions(Request $request, Quiz $quiz): View
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $questions = $quiz->questions()->orderBy('order')->get();

        return view('teacher.quizzes.questions', [
            'quiz' => $quiz,
            'questions' => $questions,
        ]);
    }

    /**
     * Add a new question to the quiz.
     */
    public function addQuestion(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'question_type' => ['required', 'in:multiple_choice,true_false,short_answer,essay'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'explanation' => ['nullable', 'string'],
        ]);

        // Build options array for multiple choice
        $options = null;
        if ($validated['question_type'] === 'multiple_choice' && !empty($validated['options'])) {
            $optionArray = [];
            foreach ($validated['options'] as $index => $value) {
                if (!empty($value)) {
                    $letter = chr(65 + $index); // A, B, C, D
                    $optionArray[$letter] = $value;
                }
            }
            $options = !empty($optionArray) ? $optionArray : null;
        }

        // Get the next order
        $maxOrder = $quiz->questions()->max('order') ?? 0;

        $quiz->questions()->create([
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'options' => $options,
            'correct_answer' => $validated['correct_answer'] ?? null,
            'explanation' => $validated['explanation'] ?? null,
            'points' => $validated['points'],
            'order' => $maxOrder + 1,
        ]);

        // Update quiz total points
        $this->updateQuizPoints($quiz);

        return redirect()->route('teacher.quizzes.questions', $quiz)->with('success', 'Question added successfully.');
    }

    /**
     * Remove a question from the quiz.
     */
    public function removeQuestion(Request $request, Quiz $quiz, QuizQuestion $question)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        // Verify the question belongs to this quiz
        if ((int) $question->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $question->delete();

        // Reorder remaining questions
        $quiz->questions()->orderBy('order')->get()->each(function ($q, $index) {
            $q->update(['order' => $index + 1]);
        });

        // Update quiz total points
        $this->updateQuizPoints($quiz);

        return redirect()->route('teacher.quizzes.questions', $quiz)->with('success', 'Question removed successfully.');
    }

    /**
     * Publish the quiz.
     */
    public function publish(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        // Check if quiz has questions
        if ($quiz->questions()->count() === 0) {
            return redirect()->route('teacher.quizzes.questions', $quiz)->with('error', 'Cannot publish a quiz without questions.');
        }

        $quiz->update(['is_published' => true]);

        // Notify students
        $course = $quiz->course;
        $studentIdsQuery = DB::table('enrollments')->where('course_id', (int) $course->id);
        if (Schema::hasColumn('enrollments', 'status')) {
            $studentIdsQuery->whereRaw('LOWER(status) = ?', ['active']);
        }
        $studentIds = $studentIdsQuery->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        
        if (!empty($studentIds)) {
            $students = User::query()->whereIn('id', $studentIds)->get();
            foreach ($students as $student) {
                $student->notify(new CourseEventNotification(
                    'New Quiz Posted',
                    'New quiz "' . (string) $quiz->title . '" was posted in ' . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.',
                    route('student.quizzes.show', ['quiz' => (int) $quiz->id])
                ));
            }
        }

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', 'Quiz published successfully.');
    }

    /**
     * Update quiz total points based on questions.
     */
    private function updateQuizPoints(Quiz $quiz): void
    {
        $totalPoints = $quiz->questions()->sum('points');
        $quiz->update([
            'points' => $totalPoints,
            'max_score' => $totalPoints > 0 ? $totalPoints : $quiz->max_score,
        ]);
    }

    /**
     * Unpublish the quiz.
     */
    public function unpublish(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $quiz->update(['is_published' => false]);

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', 'Quiz unpublished successfully.');
    }

    /**
     * Release results of the quiz to students.
     */
    public function releaseResults(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $quiz->update(['results_released' => true]);

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', 'Quiz results released to students.');
    }

    /**
     * Import questions from a question bank.
     */
    public function importFromBank(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
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

        $maxOrder = $quiz->questions()->max('order') ?? 0;

        foreach ($bankQuestions as $bq) {
            $quiz->questions()->create([
                'question_text' => $bq->question_text,
                'question_type' => $bq->question_type,
                'options' => $bq->options,
                'correct_answer' => $bq->correct_answer,
                'explanation' => $bq->explanation,
                'points' => $bq->points,
                'order' => ++$maxOrder,
            ]);
        }

        $this->updateQuizPoints($quiz);

        return redirect()->route('teacher.quizzes.questions', $quiz)->with('success', 'Questions imported successfully.');
    }

    public function exportScores(Request $request, Quiz $quiz)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $quiz->load('course');

        $courseCode = Str::slug($quiz->course->course_number ?? 'course');
        $quizSlug   = Str::slug($quiz->title);
        $date       = now()->format('Y-m-d');
        $filename   = "quiz_scores_{$courseCode}_{$quizSlug}_{$date}.xlsx";

        return Excel::download(
            new QuizScoresExport($quiz, $request->user()->name),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    public function showAttempt(Request $request, Quiz $quiz, QuizAttempt $attempt): View
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $attempt->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $attempt->load(['student', 'answers.question']);

        return view('teacher.quizzes.attempt', [
            'quiz' => $quiz,
            'attempt' => $attempt,
        ]);
    }

    public function gradeAttempt(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        if ((int) $quiz->course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $attempt->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $validated = $request->validate([
            'scores'   => ['nullable', 'array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
            'feedback'   => ['nullable', 'array'],
            'feedback.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $attempt->load('answers.question');

        $totalScore = 0;

        foreach ($attempt->answers as $answer) {
            $qId    = $answer->question_id;
            $maxPts = (int) ($answer->question?->points ?? 0);

            if (array_key_exists($qId, $validated['scores'] ?? [])) {
                $adjusted = min(max(0, (int) round((float) $validated['scores'][$qId])), $maxPts);
            } else {
                $adjusted = (int) ($answer->points_earned ?? 0);
            }

            $autoScore   = $answer->auto_score ?? $answer->points_earned;
            $isOverridden = $adjusted !== (int) ($autoScore ?? $adjusted);
            $feedbackText = $validated['feedback'][$qId] ?? $answer->feedback;

            $answer->update([
                'points_earned' => $adjusted,
                'is_overridden' => $isOverridden,
                'feedback'      => $feedbackText ?: null,
                'is_correct'    => $maxPts > 0 ? $adjusted === $maxPts : $answer->is_correct,
            ]);

            $totalScore += $adjusted;
        }

        $attempt->update([
            'score'     => $totalScore,
            'status'    => 'graded',
            'graded_at' => now(),
            'graded_by' => (int) $request->user()->id,
        ]);

        return redirect()
            ->route('teacher.quizzes.attempts.show', [$quiz, $attempt])
            ->with('success', 'Quiz scores updated successfully.');
    }
}
