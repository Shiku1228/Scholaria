<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentSubmissionApiController extends Controller
{
    public function create(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->isStudentEnrolled($studentId, (int) $assignment->course_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course.',
            ], 403);
        }

        $submission = null;
        if (Schema::hasTable('submissions')) {
            $submission = Submission::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => [
                    'id' => (int) $assignment->id,
                    'course_id' => (int) ($assignment->course_id ?? 0),
                    'title' => (string) ($assignment->title ?? ''),
                    'description' => (string) ($assignment->description ?? ''),
                    'due_date' => optional($assignment->due_date)->toDateTimeString(),
                    'max_score' => $assignment->max_score !== null ? (int) $assignment->max_score : null,
                    'type' => (string) ($assignment->type ?? 'assignment'),
                ],
                'submission' => $submission ? [
                    'id' => (int) $submission->id,
                    'assignment_id' => (int) $submission->assignment_id,
                    'student_id' => (int) $submission->student_id,
                    'submission_type' => (string) ($submission->submission_type ?? ''),
                    'content' => $submission->content ?? null,
                    'file_path' => $submission->file_path ?? null,
                    'submitted_at' => optional($submission->submitted_at)->toDateTimeString(),
                    'score' => $submission->score ?? null,
                    'feedback' => $submission->feedback ?? null,
                ] : null,
            ],
        ]);
    }

    public function store(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->isStudentEnrolled($studentId, (int) $assignment->course_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this course.',
            ], 403);
        }

        if (!Schema::hasTable('submissions')) {
            return response()->json([
                'success' => false,
                'message' => 'Submissions are not available yet.',
            ], 422);
        }

        $submissionType = (string) $request->input('submission_type', 'file');

        $rules = [
            'submission_type' => ['required', 'in:text,file,link'],
        ];

        if ($submissionType === 'text') {
            $rules['text_content'] = ['required', 'string', 'min:10'];
        } elseif ($submissionType === 'file') {
            $rules['file'] = ['required', 'file', 'max:10240'];
        } elseif ($submissionType === 'link') {
            $rules['link_content'] = ['required', 'url', 'max:500'];
        }

        $validated = $request->validate($rules);

        $submission = Submission::query()->firstOrNew([
            'assignment_id' => (int) $assignment->id,
            'student_id' => $studentId,
        ]);

        $submission->submission_type = $submissionType;
        $submission->submitted_at = now();

        switch ($submissionType) {
            case 'text':
                $submission->content = strip_tags($validated['text_content'], '<p><br><strong><em><u><ul><ol><li><a><code><pre><h1><h2><h3><h4><h5><h6>');
                $submission->file_path = null;
                break;
            case 'file':
                $file = $validated['file'];
                $path = $file->storeAs(
                    'submissions',
                    now()->format('Ymd_His') . '_' . Str::random(10) . '_' . $file->getClientOriginalName(),
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

        return response()->json([
            'success' => true,
            'message' => 'Assignment submitted successfully.',
            'data' => [
                'submission' => [
                    'id' => (int) $submission->id,
                    'assignment_id' => (int) $submission->assignment_id,
                    'student_id' => (int) $submission->student_id,
                    'submission_type' => (string) $submission->submission_type,
                    'content' => $submission->content ?? null,
                    'file_path' => $submission->file_path ?? null,
                    'file_url' => $submission->file_path ? Storage::disk('public')->url($submission->file_path) : null,
                    'submitted_at' => optional($submission->submitted_at)->toDateTimeString(),
                    'score' => $submission->score ?? null,
                    'feedback' => $submission->feedback ?? null,
                ],
            ],
        ]);
    }

    private function isStudentEnrolled(int $studentId, int $courseId): bool
    {
        try {
            if (!Schema::hasTable('enrollments') || !Schema::hasColumn('enrollments', 'student_id') || !Schema::hasColumn('enrollments', 'course_id')) {
                return false;
            }

            $query = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId);

            if (Schema::hasColumn('enrollments', 'status')) {
                $query->whereRaw('LOWER(status) = ?', ['active']);
            }

            return $query->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
