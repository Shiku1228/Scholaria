<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentSubmissionController extends Controller
{
    public function create(Request $request, Assignment $assignment): View
    {
        $studentId = (int) $request->user()->id;

        try {
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasColumn('assignments', 'course_id')) {
                $courseId = (int) ($assignment->course_id ?? 0);
                if ($courseId > 0) {
                    $isEnrolled = DB::table('enrollments')->where('student_id', $studentId)->where('course_id', $courseId)->exists();
                    if (!$isEnrolled) {
                        abort(403);
                    }
                }
            }
        } catch (\Throwable) {
        }

        return view('student.submissions.create', [
            'assignment' => $assignment,
        ]);
    }

    public function store(Request $request, Assignment $assignment)
    {
        $studentId = (int) $request->user()->id;

        try {
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasColumn('assignments', 'course_id')) {
                $courseId = (int) ($assignment->course_id ?? 0);
                if ($courseId > 0) {
                    $isEnrolled = DB::table('enrollments')->where('student_id', $studentId)->where('course_id', $courseId)->exists();
                    if (!$isEnrolled) {
                        abort(403);
                    }
                }
            }
        } catch (\Throwable) {
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ]);

        if (!Schema::hasTable('submissions')) {
            return back()->withErrors(['file' => 'Submissions are not available yet.']);
        }

        $path = $validated['file']->storeAs(
            'submissions',
            now()->format('Ymd_His') . '_' . Str::random(10) . '_' . $validated['file']->getClientOriginalName(),
            'public'
        );

        $submission = Submission::query()->firstOrNew([
            'assignment_id' => (int) $assignment->id,
            'student_id' => $studentId,
        ]);

        $submission->file_path = $path;
        $submission->submitted_at = now();
        $submission->save();

        return redirect()->route('student.dashboard')->with('success', 'Assignment submitted.');
    }
}
