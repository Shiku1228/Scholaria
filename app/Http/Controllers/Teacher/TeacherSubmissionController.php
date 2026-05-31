<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherSubmissionController extends Controller
{
    public function show(Course $course, Assignment $assignment, Submission $submission)
    {
        if ((int) $course->teacher_id !== (int) auth()->id()) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        if ((int) $submission->assignment_id !== (int) $assignment->id) {
            abort(404);
        }

        $questions = collect();
        $hasQuestions = false;
        if (Schema::hasTable('assignment_questions') && Schema::hasTable('assignment_answers')) {
            $questions = $assignment->questions()->with('choices')->orderBy('order')->get();
            $hasQuestions = $questions->isNotEmpty();
        }

        $answers = collect();
        if ($hasQuestions) {
            $answers = $submission->answers()->with('selectedChoice')->get()->keyBy('question_id');
        }

        $isMC = ($assignment->assignment_format ?? 'essay') === 'multiple_choice';

        $submission->load(['student', 'gradedBy']);

        return view('teacher.assignments.submissions.show', compact(
            'course', 'assignment', 'submission', 'questions', 'answers', 'hasQuestions', 'isMC'
        ));
    }

    public function update(Request $request, Course $course, Assignment $assignment, Submission $submission)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        if ((int) $submission->assignment_id !== (int) $assignment->id) {
            abort(404);
        }

        $hasQuestions = Schema::hasTable('assignment_questions')
            && Schema::hasTable('assignment_answers')
            && $assignment->questions()->exists();

        $isEssay = ($assignment->assignment_format ?? 'essay') === 'essay';

        // Per-question grading path (essay with questions only)
        if ($hasQuestions && $isEssay) {
            $questions = $assignment->questions()->orderBy('order')->get();

            $rules = ['feedback' => ['nullable', 'string', 'max:5000']];
            foreach ($questions as $q) {
                $rules["answer_scores.{$q->id}"]    = ['nullable', 'integer', 'min:0', 'max:' . (int) $q->points];
                $rules["answer_feedback.{$q->id}"]  = ['nullable', 'string', 'max:2000'];
            }
            $validated = $request->validate($rules);

            $totalScore = 0;
            $allGraded  = true;

            foreach ($questions as $q) {
                $rawScore = $validated['answer_scores'][$q->id] ?? null;
                $score    = ($rawScore !== null && $rawScore !== '') ? (int) $rawScore : null;
                $answerFeedback = $validated['answer_feedback'][$q->id] ?? null;

                DB::table('assignment_answers')
                    ->where('submission_id', $submission->id)
                    ->where('question_id', $q->id)
                    ->update(['score' => $score, 'feedback' => $answerFeedback, 'updated_at' => now()]);

                if ($score !== null) {
                    $totalScore += $score;
                } else {
                    $allGraded = false;
                }
            }

            $updateData = [
                'score'    => $allGraded ? $totalScore : null,
                'feedback' => $validated['feedback'] ?? null,
            ];

            if ($allGraded) {
                $updateData['graded_by'] = auth()->id();
                $updateData['graded_at'] = now();
            }

            $submission->update($updateData);

            return redirect()
                ->route('teacher.submissions.show', [$course, $assignment, $submission])
                ->with('success', 'Submission graded successfully.');
        }

        // Legacy path: grade the whole submission directly
        $maxScore = Schema::hasColumn('assignments', 'max_score')
            ? (int) ($assignment->max_score ?? 100)
            : 100;

        $validated = $request->validate([
            'score'    => ['nullable', 'integer', 'min:0', 'max:' . max(1, $maxScore)],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $updateData = [
            'score'    => $validated['score'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
        ];

        if (($validated['score'] ?? null) !== null) {
            $updateData['graded_by'] = auth()->id();
            $updateData['graded_at'] = now();
        }

        $submission->update($updateData);

        return redirect()
            ->route('teacher.submissions.show', [$course, $assignment, $submission])
            ->with('success', 'Submission graded.');
    }
}
