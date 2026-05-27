<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\Concerns\ResolvesStudentEnrollment;
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
    use ResolvesStudentEnrollment;

    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);
        $submissions = [];

        try {
            if (!Schema::hasTable('submissions') || !Schema::hasColumn('submissions', 'student_id')) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'submissions' => [],
                        'filters' => ['course_id' => $courseId],
                    ],
                ]);
            }

            $query = DB::table('submissions')
                ->where('submissions.student_id', $studentId);

            $hasAssignments = Schema::hasTable('assignments') && Schema::hasColumn('submissions', 'assignment_id');
            if ($hasAssignments) {
                $query->leftJoin('assignments', 'assignments.id', '=', 'submissions.assignment_id');
            }

            $courseNameColumn = $this->courseNameColumn();
            $hasCourseJoin = $courseNameColumn && $hasAssignments && Schema::hasTable('courses') && Schema::hasColumn('assignments', 'course_id');
            if ($hasCourseJoin) {
                $query->leftJoin('courses', 'courses.id', '=', 'assignments.course_id');
            }

            if ($courseId > 0 && $hasAssignments && Schema::hasColumn('assignments', 'course_id')) {
                $query->where('assignments.course_id', $courseId);
            } elseif ($courseId > 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'submissions' => [],
                        'filters' => ['course_id' => $courseId],
                    ],
                ]);
            }

            $select = [
                'submissions.id as submission_id',
                'submissions.assignment_id as assignment_id',
                'submissions.student_id as student_id',
            ];

            foreach (['submission_type', 'content', 'file_path', 'submitted_at', 'score', 'feedback', 'created_at', 'updated_at'] as $column) {
                if (Schema::hasColumn('submissions', $column)) {
                    $select[] = 'submissions.' . $column . ' as ' . $column;
                }
            }

            if ($hasAssignments && Schema::hasColumn('assignments', 'title')) {
                $select[] = 'assignments.title as assignment_title';
            }

            if ($hasAssignments && Schema::hasColumn('assignments', 'due_date')) {
                $select[] = 'assignments.due_date as due_date';
            }

            if ($hasAssignments && Schema::hasColumn('assignments', 'max_score')) {
                $select[] = 'assignments.max_score as max_score';
            }

            if ($hasCourseJoin) {
                $select[] = 'courses.' . $courseNameColumn . ' as course_name';
            }

            if ($hasAssignments && Schema::hasColumn('assignments', 'course_id')) {
                $select[] = 'assignments.course_id as course_id';
            }

            $rows = $query
                ->select($select)
                ->orderByDesc(Schema::hasColumn('submissions', 'submitted_at') ? 'submissions.submitted_at' : 'submissions.id')
                ->limit(300)
                ->get();

            $submissions = $rows->map(fn ($row) => $this->mapSubmissionRow($row))->values()->all();
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data' => [
                'submissions' => $submissions,
                'filters' => ['course_id' => $courseId],
            ],
        ]);
    }

    public function create(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $assignment->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $assignment->course_id);
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

        if (!$this->hasStudentEnrollmentAccess($studentId, (int) $assignment->course_id)) {
            return $this->enrollmentDeniedResponse($studentId, (int) $assignment->course_id);
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

    private function mapSubmissionRow(object $row): array
    {
        $score = $row->score ?? null;
        $feedback = (string) ($row->feedback ?? '');
        $submittedAt = (string) ($row->submitted_at ?? '');
        $filePath = (string) ($row->file_path ?? '');

        return [
            'submission_id' => (int) ($row->submission_id ?? 0),
            'assignment_id' => (int) ($row->assignment_id ?? 0),
            'student_id' => (int) ($row->student_id ?? 0),
            'course_id' => (int) ($row->course_id ?? 0),
            'course_name' => (string) ($row->course_name ?? ''),
            'assignment_title' => (string) ($row->assignment_title ?? ''),
            'submission_type' => (string) ($row->submission_type ?? ''),
            'content' => $row->content ?? null,
            'file_path' => $filePath !== '' ? $filePath : null,
            'file_url' => $filePath !== '' ? Storage::disk('public')->url($filePath) : null,
            'due_date' => (string) ($row->due_date ?? ''),
            'max_score' => $row->max_score !== null ? (int) $row->max_score : null,
            'submitted_at' => $submittedAt !== '' ? $submittedAt : null,
            'score' => $score !== null ? (int) $score : null,
            'feedback' => $feedback !== '' ? $feedback : null,
            'status' => $this->resolveStatus($row),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }

    private function resolveStatus(object $row): string
    {
        if (($row->score ?? null) !== null || !empty($row->feedback)) {
            return 'graded';
        }

        if (!empty($row->submitted_at)) {
            return 'submitted';
        }

        return 'draft';
    }

    private function courseNameColumn(): ?string
    {
        if (!Schema::hasTable('courses')) {
            return null;
        }

        foreach (['title', 'name', 'course_name'] as $column) {
            if (Schema::hasColumn('courses', $column)) {
                return $column;
            }
        }

        return null;
    }

}
