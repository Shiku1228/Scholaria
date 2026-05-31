<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentAnswer;
use App\Models\AssignmentChoice;
use App\Models\AssignmentQuestion;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use App\Services\CourseChatGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function overview(Request $request): View
    {
        $teacherId   = (int) $request->user()->id;
        $assignments = collect();

        try {
            if (Schema::hasTable('assignments') && Schema::hasTable('courses')) {
                $assignments = Assignment::query()
                    ->whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->with(['course'])
                    ->withCount('submissions')
                    ->orderByDesc('id')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $assignments = collect();
        }

        return view('teacher.assignments.overview', [
            'assignments' => $assignments,
        ]);
    }

    public function index(Request $request, Course $course): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return redirect()->route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']);
    }

    public function create(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.assignments.create', [
            'course' => $course,
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $isMC = $request->input('assignment_format') === 'multiple_choice';

        $rules = [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'due_date'          => ['nullable', 'date'],
            'type'              => ['nullable', 'in:assignment,quiz,exam'],
            'assignment_format' => ['required', 'in:essay,multiple_choice'],
            'questions'         => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string', 'max:5000'],
            'questions.*.points'        => ['required', 'integer', 'min:1', 'max:10000'],
        ];
        if ($isMC) {
            $rules['questions.*.choices']               = ['required', 'array', 'min:2', 'max:10'];
            $rules['questions.*.choices.*.choice_text'] = ['required', 'string', 'max:500'];
            $rules['questions.*.correct_choice']        = ['required', 'integer', 'min:0'];
        }

        $validated = $request->validate($rules);

        if ($isMC) {
            foreach ($validated['questions'] as $idx => $qData) {
                $choiceCount = count($qData['choices'] ?? []);
                $correctIdx  = (int) ($qData['correct_choice'] ?? -1);
                if ($correctIdx < 0 || $correctIdx >= $choiceCount) {
                    return back()
                        ->withErrors(["questions.{$idx}.correct_choice" => 'Select a correct answer for question ' . ($idx + 1) . '.'])
                        ->withInput();
                }
            }
        }

        $totalPoints = (int) collect($validated['questions'])->sum(fn ($q) => (int) ($q['points'] ?? 1));

        $assignment = null;
        DB::transaction(function () use ($validated, $course, $totalPoints, $isMC, &$assignment) {
            $assignment = Assignment::create([
                'course_id'         => (int) $course->id,
                'title'             => $validated['title'],
                'description'       => $validated['description'] ?? null,
                'due_date'          => $validated['due_date'] ?? null,
                'max_score'         => $totalPoints,
                'type'              => $validated['type'] ?? 'assignment',
                'assignment_format' => $validated['assignment_format'],
            ]);

            foreach ($validated['questions'] as $idx => $qData) {
                $question = AssignmentQuestion::create([
                    'assignment_id' => $assignment->id,
                    'question_text' => $qData['question_text'],
                    'points'        => (int) $qData['points'],
                    'order'         => $idx,
                ]);

                if ($isMC) {
                    $correctIdx = (int) $qData['correct_choice'];
                    foreach ($qData['choices'] as $cIdx => $cData) {
                        AssignmentChoice::create([
                            'question_id' => $question->id,
                            'choice_text' => $cData['choice_text'],
                            'is_correct'  => $cIdx === $correctIdx,
                            'order'       => $cIdx,
                        ]);
                    }
                }
            }
        });

        $this->notifyStudents($course, $assignment);

        return redirect()->route('teacher.assignments.show', [$course, $assignment])
            ->with('success', 'Assignment created successfully.');
    }

    public function show(Request $request, Course $course, Assignment $assignment): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        $questions = collect();
        if (Schema::hasTable('assignment_questions')) {
            $questions = $assignment->questions()->with('choices')->get();
        }

        $submissions = collect();
        try {
            if (Schema::hasTable('submissions')) {
                $query = Submission::query()
                    ->where('assignment_id', $assignment->id)
                    ->with('student');

                if (Schema::hasTable('assignment_answers')) {
                    $query->with(['answers.question', 'answers.selectedChoice']);
                }

                $submissions = $query->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20);
            }
        } catch (\Throwable) {
            $submissions = collect();
        }

        return view('teacher.assignments.show', [
            'course'      => $course,
            'assignment'  => $assignment,
            'questions'   => $questions,
            'submissions' => $submissions,
        ]);
    }

    public function edit(Request $request, Course $course, Assignment $assignment): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        $questions = collect();
        if (Schema::hasTable('assignment_questions')) {
            $questions = $assignment->questions()->with('choices')->get();
        }

        $hasSubmissions = Schema::hasTable('submissions')
            && Submission::where('assignment_id', $assignment->id)->exists();

        return view('teacher.assignments.edit', [
            'course'         => $course,
            'assignment'     => $assignment,
            'questions'      => $questions,
            'hasSubmissions' => $hasSubmissions,
        ]);
    }

    public function update(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        $isMC = $request->input('assignment_format') === 'multiple_choice';

        $hasSubmissions = Schema::hasTable('submissions')
            && Submission::where('assignment_id', $assignment->id)->exists();

        $rules = [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'due_date'          => ['nullable', 'date'],
            'type'              => ['nullable', 'in:assignment,quiz,exam'],
            'assignment_format' => ['required', 'in:essay,multiple_choice'],
            'questions'         => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string', 'max:5000'],
            'questions.*.points'        => ['required', 'integer', 'min:1', 'max:10000'],
        ];
        if ($isMC) {
            $rules['questions.*.choices']               = ['required', 'array', 'min:2', 'max:10'];
            $rules['questions.*.choices.*.choice_text'] = ['required', 'string', 'max:500'];
            $rules['questions.*.correct_choice']        = ['required', 'integer', 'min:0'];
        }

        $validated = $request->validate($rules);

        if ($isMC) {
            foreach ($validated['questions'] as $idx => $qData) {
                $choiceCount = count($qData['choices'] ?? []);
                $correctIdx  = (int) ($qData['correct_choice'] ?? -1);
                if ($correctIdx < 0 || $correctIdx >= $choiceCount) {
                    return back()
                        ->withErrors(["questions.{$idx}.correct_choice" => 'Select a correct answer for question ' . ($idx + 1) . '.'])
                        ->withInput();
                }
            }
        }

        $totalPoints = (int) collect($validated['questions'])->sum(fn ($q) => (int) ($q['points'] ?? 1));

        DB::transaction(function () use ($validated, $assignment, $totalPoints, $isMC, $hasSubmissions) {
            $assignment->update([
                'title'             => $validated['title'],
                'description'       => $validated['description'] ?? null,
                'due_date'          => $validated['due_date'] ?? null,
                'max_score'         => $totalPoints,
                'type'              => $validated['type'] ?? $assignment->type ?? 'assignment',
                'assignment_format' => $validated['assignment_format'],
            ]);

            if (!$hasSubmissions && Schema::hasTable('assignment_questions')) {
                // Cascade: questions → choices → answers are deleted
                $assignment->questions()->delete();

                foreach ($validated['questions'] as $idx => $qData) {
                    $question = AssignmentQuestion::create([
                        'assignment_id' => $assignment->id,
                        'question_text' => $qData['question_text'],
                        'points'        => (int) $qData['points'],
                        'order'         => $idx,
                    ]);

                    if ($isMC) {
                        $correctIdx = (int) $qData['correct_choice'];
                        foreach ($qData['choices'] as $cIdx => $cData) {
                            AssignmentChoice::create([
                                'question_id' => $question->id,
                                'choice_text' => $cData['choice_text'],
                                'is_correct'  => $cIdx === $correctIdx,
                                'order'       => $cIdx,
                            ]);
                        }
                    }
                }
            }
        });

        return redirect()->route('teacher.assignments.show', [$course, $assignment])
            ->with('success', 'Assignment updated successfully.');
    }

    public function destroy(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        DB::transaction(function () use ($assignment) {
            // Delete answers linked to this assignment's submissions
            if (Schema::hasTable('assignment_answers') && Schema::hasTable('submissions')) {
                $submissionIds = DB::table('submissions')
                    ->where('assignment_id', $assignment->id)
                    ->pluck('id');
                if ($submissionIds->isNotEmpty()) {
                    DB::table('assignment_answers')->whereIn('submission_id', $submissionIds)->delete();
                }
            }

            // Delete choices + questions (questions cascade to answers via FK, but we handle above)
            if (Schema::hasTable('assignment_choices') && Schema::hasTable('assignment_questions')) {
                $questionIds = DB::table('assignment_questions')
                    ->where('assignment_id', $assignment->id)
                    ->pluck('id');
                if ($questionIds->isNotEmpty()) {
                    DB::table('assignment_choices')->whereIn('question_id', $questionIds)->delete();
                }
            }
            if (Schema::hasTable('assignment_questions')) {
                DB::table('assignment_questions')->where('assignment_id', $assignment->id)->delete();
            }

            if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'assignment_id')) {
                DB::table('grades')->where('assignment_id', $assignment->id)->delete();
            }

            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id')) {
                DB::table('submissions')->where('assignment_id', $assignment->id)->delete();
            }

            $assignment->delete();
        });

        return redirect()->route('teacher.assignments.index', $course)
            ->with('success', 'Assignment deleted.');
    }

    private function notifyStudents(Course $course, ?Assignment $assignment): void
    {
        if (!$assignment) {
            return;
        }

        try {
            $studentIdsQuery = DB::table('enrollments')->where('course_id', (int) $course->id);
            if (Schema::hasColumn('enrollments', 'status')) {
                $studentIdsQuery->whereRaw('LOWER(status) = ?', ['active']);
            }
            $studentIds = $studentIdsQuery->pluck('student_id')
                ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

            if (!empty($studentIds)) {
                $students = User::query()->whereIn('id', $studentIds)->get();
                foreach ($students as $student) {
                    $student->notify(new CourseEventNotification(
                        'New Assignment Posted',
                        'New assignment "' . (string) $assignment->title . '" was posted in '
                            . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.',
                        route('student.assignments.index', ['course_id' => (int) $course->id])
                    ));
                }
            }
        } catch (\Throwable) {
        }
    }
}
