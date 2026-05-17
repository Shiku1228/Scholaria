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

        if (!Schema::hasTable('submissions')) {
            return back()->withErrors(['submission' => 'Submissions are not available yet.']);
        }

        $submissionType = $request->input('submission_type', 'file');
        
        // Validate based on submission type
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
            'student_id' => $studentId,
        ]);

        $submission->submission_type = $submissionType;
        $submission->submitted_at = now();

        // Handle different submission types
        switch ($submissionType) {
            case 'text':
                // Clean HTML content but preserve formatting
                $cleanContent = strip_tags($validated['text_content'], '<p><br><strong><em><u><ul><ol><li><a><code><pre><h1><h2><h3><h4><h5><h6>');
                $submission->content = $cleanContent;
                $submission->file_path = null;
                break;
            case 'file':
                $path = $validated['file']->storeAs(
                    'submissions',
                    now()->format('Ymd_His') . '_' . Str::random(10) . '_' . $validated['file']->getClientOriginalName(),
                    'public'
                );
                $submission->file_path = $path;
                $submission->content = null;
                break;
            case 'link':
                $submission->content = $validated['link_content'];
                $submission->file_path = null;
                break;
        }

        $submission->save();

        $message = 'Assignment submitted successfully.';
        if ($submissionType === 'text') {
            $message = 'Text submission saved successfully.';
        } elseif ($submissionType === 'file') {
            $message = 'File uploaded successfully.';
        } elseif ($submissionType === 'link') {
            $message = 'Link submitted successfully.';
        }

        return redirect()->route('student.assignments.show', $assignment)->with('success', $message);
    }
}
