<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentSubmissionController extends Controller
{
    public function create(Request $request, Assignment $assignment): View
    {
        $studentId = (int) $request->user()->id;

        try {
            if (Schema::hasTable('enrollments')
                && Schema::hasColumn('enrollments', 'student_id')
                && Schema::hasColumn('enrollments', 'course_id')
                && Schema::hasColumn('assignments', 'course_id')) {
                $courseId = (int) ($assignment->course_id ?? 0);
                if ($courseId > 0) {
                    $isEnrolled = DB::table('enrollments')
                        ->where('student_id', $studentId)
                        ->where('course_id', $courseId)
                        ->exists();
                    if (!$isEnrolled) {
                        abort(403);
                    }
                }
            }
        } catch (\Throwable) {
        }

        $questions = collect();
        if (Schema::hasTable('assignment_questions')) {
            try {
                $questions = $assignment->questions()->with('choices')->get();
            } catch (\Throwable) {
            }
        }

        return view('student.submissions.create', [
            'assignment' => $assignment,
            'questions'  => $questions,
        ]);
    }

    public function store(Request $request, Assignment $assignment)
    {
        $studentId = (int) $request->user()->id;

        try {
            if (Schema::hasTable('enrollments')
                && Schema::hasColumn('enrollments', 'student_id')
                && Schema::hasColumn('enrollments', 'course_id')
                && Schema::hasColumn('assignments', 'course_id')) {
                $courseId = (int) ($assignment->course_id ?? 0);
                if ($courseId > 0) {
                    $isEnrolled = DB::table('enrollments')
                        ->where('student_id', $studentId)
                        ->where('course_id', $courseId)
                        ->exists();
                    if (!$isEnrolled) {
                        abort(403);
                    }
                }
            }
        } catch (\Throwable) {
        }

        if (!Schema::hasTable('submissions')) {
            return back()->withErrors(['submission' => 'Submissions are not available yet.']);
        }

        // ── Question-based submission path ────────────────────────────────
        $hasQuestions = Schema::hasTable('assignment_questions')
            && Schema::hasTable('assignment_answers')
            && $assignment->questions()->exists();

        if ($hasQuestions) {
            $questions = $assignment->questions()->with('choices')->get();
            $isEssay   = ($assignment->assignment_format ?? 'essay') === 'essay';

            $rules = [];
            foreach ($questions as $q) {
                if ($isEssay) {
                    $rules["answers.{$q->id}"] = ['required', 'string', 'min:1', 'max:10000'];
                } else {
                    $choiceIds = $q->choices->pluck('id')->toArray();
                    $rules["answers.{$q->id}"] = ['required', 'integer', Rule::in($choiceIds)];
                }
            }
            $validated = $request->validate($rules);

            $successMessage = $isEssay
                ? 'Assignment submitted. Your teacher will review and grade your answers.'
                : 'Assignment submitted! Your score has been calculated automatically.';

            DB::transaction(function () use ($validated, $assignment, $studentId, $questions, $isEssay, $successMessage) {
                $qCount     = $questions->count();
                $submission = Submission::firstOrNew([
                    'assignment_id' => (int) $assignment->id,
                    'student_id'    => $studentId,
                ]);
                $submission->submission_type = 'text';
                $submission->submitted_at    = now();
                $submission->content         = $qCount . ' question' . ($qCount !== 1 ? 's' : '') . ' answered';
                $submission->file_path       = null;
                $submission->save();

                $totalAutoScore = 0;

                foreach ($questions as $q) {
                    $answerValue = $validated['answers'][$q->id] ?? null;

                    if ($isEssay) {
                        DB::table('assignment_answers')->updateOrInsert(
                            ['submission_id' => $submission->id, 'question_id' => $q->id],
                            [
                                'essay_answer'       => (string) $answerValue,
                                'selected_choice_id' => null,
                                'score'              => null,
                                'updated_at'         => now(),
                                'created_at'         => now(),
                            ]
                        );
                    } else {
                        $selectedChoice = $q->choices->firstWhere('id', (int) $answerValue);
                        $autoScore      = ($selectedChoice && $selectedChoice->is_correct) ? (int) $q->points : 0;
                        $totalAutoScore += $autoScore;

                        DB::table('assignment_answers')->updateOrInsert(
                            ['submission_id' => $submission->id, 'question_id' => $q->id],
                            [
                                'selected_choice_id' => (int) $answerValue,
                                'essay_answer'       => null,
                                'score'              => $autoScore,
                                'updated_at'         => now(),
                                'created_at'         => now(),
                            ]
                        );
                    }
                }

                if (!$isEssay) {
                    $submission->update(['score' => $totalAutoScore]);
                }
            });

            return redirect()->route('student.assignments.show', $assignment)
                ->with('success', $successMessage);
        }

        // ── Legacy text / file / link submission path ─────────────────────
        $submissionType = $request->input('submission_type', 'file');

        $rules = [
            'submission_type' => ['required', 'in:text,file,link'],
        ];
        switch ($submissionType) {
            case 'text':
                $rules['text_content'] = ['required', 'string', 'min:10'];
                break;
            case 'file':
                $rules['file'] = ['required', 'file', 'max:10240'];
                break;
            case 'link':
                $rules['link_content'] = ['required', 'url', 'max:500'];
                break;
        }

        $validated = $request->validate($rules);

        $submission = Submission::query()->firstOrNew([
            'assignment_id' => (int) $assignment->id,
            'student_id'    => $studentId,
        ]);

        $submission->submission_type = $submissionType;
        $submission->submitted_at    = now();

        switch ($submissionType) {
            case 'text':
                $cleanContent      = strip_tags($validated['text_content'], '<p><br><strong><em><u><ul><ol><li><a><code><pre><h1><h2><h3><h4><h5><h6>');
                $submission->content   = $cleanContent;
                $submission->file_path = null;
                break;
            case 'file':
                $path = $validated['file']->storeAs(
                    'submissions',
                    now()->format('Ymd_His') . '_' . Str::random(10) . '_' . $validated['file']->getClientOriginalName(),
                    'public'
                );
                $submission->file_path = $path;
                $submission->content   = null;
                break;
            case 'link':
                $submission->content   = $validated['link_content'];
                $submission->file_path = null;
                break;
        }

        $submission->save();

        $message = match ($submissionType) {
            'text'  => 'Text submission saved successfully.',
            'file'  => 'File uploaded successfully.',
            'link'  => 'Link submitted successfully.',
            default => 'Assignment submitted successfully.',
        };

        return redirect()->route('student.assignments.show', $assignment)->with('success', $message);
    }
}
