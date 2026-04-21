<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TeacherSubmissionController extends Controller
{
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

        $maxScore = Schema::hasColumn('assignments', 'max_score') ? (int) ($assignment->max_score ?? 100) : 100;

        $validated = $request->validate([
            'score' => ['nullable', 'integer', 'min:0', 'max:' . max(1, $maxScore)],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'score' => $validated['score'] ?? null,
            'feedback' => $validated['feedback'] ?? null,
        ]);

        return back()->with('success', 'Submission graded.');
    }
}
