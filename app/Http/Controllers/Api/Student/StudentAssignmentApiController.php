<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Student\Concerns\ResolvesStudentEnrollment;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentAssignmentApiController extends Controller
{
    use ResolvesStudentEnrollment;

    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);
        $assignments = [];

        try {
            if (!Schema::hasTable('enrollments') || !Schema::hasColumn('enrollments', 'student_id') || !Schema::hasColumn('enrollments', 'course_id')) {
                return response()->json(['success' => true, 'data' => ['assignments' => []]]);
            }

            if (!Schema::hasTable('assignments')) {
                return response()->json(['success' => true, 'data' => ['assignments' => []]]);
            }

            $courseNameColumn = $this->courseNameColumn();

            $enrolledCourseIds = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->when(Schema::hasColumn('enrollments', 'status'), function ($query): void {
                    $query->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['dropped', 'dropped']);
                })
                ->pluck('course_id')
                ->map(fn ($value) => (int) $value)
                ->filter()
                ->values()
                ->all();

            if (empty($enrolledCourseIds)) {
                return response()->json(['success' => true, 'data' => ['assignments' => []]]);
            }

            if ($courseId > 0 && !in_array($courseId, $enrolledCourseIds, true)) {
                return $this->enrollmentDeniedResponse($studentId, $courseId);
            }

            $query = DB::table('assignments');

            if ($courseNameColumn && Schema::hasTable('courses') && Schema::hasColumn('assignments', 'course_id')) {
                $query->join('courses', 'courses.id', '=', 'assignments.course_id');
            }

            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                $query->leftJoin('submissions', function ($join) use ($studentId): void {
                    $join->on('submissions.assignment_id', '=', 'assignments.id')
                        ->where('submissions.student_id', '=', $studentId);
                });
            }

            $targetCourseIds = $courseId > 0 ? [$courseId] : $enrolledCourseIds;

            if (Schema::hasColumn('assignments', 'course_id')) {
                $query->whereIn('assignments.course_id', $targetCourseIds);
            }

            if (Schema::hasColumn('assignments', 'due_date')) {
                $query->orderBy('assignments.due_date');
            } else {
                $query->orderByDesc('assignments.id');
            }

            $select = [
                'assignments.id as assignment_id',
                'assignments.title as title',
            ];

            if (Schema::hasColumn('assignments', 'due_date')) {
                $select[] = 'assignments.due_date as due_date';
            }

            if ($courseNameColumn) {
                $select[] = 'courses.' . $courseNameColumn . ' as course_name';
                $select[] = 'courses.id as course_id';
            } elseif (Schema::hasColumn('assignments', 'course_id')) {
                $select[] = 'assignments.course_id as course_id';
            }

            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'id')) {
                $select[] = 'submissions.id as submission_id';
                if (Schema::hasColumn('submissions', 'submitted_at')) {
                    $select[] = 'submissions.submitted_at as submitted_at';
                }
                if (Schema::hasColumn('submissions', 'score')) {
                    $select[] = 'submissions.score as score';
                }
            }

            $rows = $query->select($select)->limit(200)->get();

            $assignments = $rows->map(function ($row) {
                return [
                    'assignment_id' => (int) ($row->assignment_id ?? 0),
                    'title' => (string) ($row->title ?? ''),
                    'course_id' => (int) ($row->course_id ?? 0),
                    'course_name' => (string) ($row->course_name ?? ''),
                    'due_date' => (string) ($row->due_date ?? ''),
                    'submission_id' => (int) ($row->submission_id ?? 0),
                    'submitted_at' => (string) ($row->submitted_at ?? ''),
                    'score' => $row->score ?? null,
                ];
            })->values()->all();
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data' => [
                'assignments' => $assignments,
            ],
        ]);
    }

    public function show(Request $request, Assignment $assignment): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $course = null;
        $submission = null;

        try {
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id') && Schema::hasColumn('assignments', 'course_id')) {
                $courseId = (int) ($assignment->course_id ?? 0);
                if ($courseId > 0) {
                    $isEnrolled = DB::table('enrollments')
                        ->where('student_id', $studentId)
                        ->where('course_id', $courseId)
                        ->when(Schema::hasColumn('enrollments', 'status'), function ($query): void {
                            $query->whereRaw('LOWER(COALESCE(status, ?)) <> ?', ['dropped', 'dropped']);
                        })
                        ->exists();

                    if (!$isEnrolled) {
                        return $this->enrollmentDeniedResponse($studentId, (int) $assignment->course_id);
                    }
                }
            }
        } catch (\Throwable) {
        }

        try {
            if (Schema::hasTable('courses')) {
                $course = DB::table('courses')->where('id', $assignment->course_id)->first();
            }
        } catch (\Throwable) {
        }

        try {
            if (Schema::hasTable('submissions')) {
                $query = DB::table('submissions')
                    ->where('assignment_id', $assignment->id)
                    ->where('student_id', $studentId);

                if (Schema::hasColumn('submissions', 'submitted_at')) {
                    $query->whereNotNull('submitted_at');
                }

                $query->where(function ($nested): void {
                    $nested->when(Schema::hasColumn('submissions', 'file_path'), function ($query): void {
                        $query->whereNotNull('file_path')
                            ->where('file_path', '!=', '');
                    })->orWhere(function ($query): void {
                        $query->when(Schema::hasColumn('submissions', 'content'), function ($contentQuery): void {
                            $contentQuery->whereNotNull('content')
                                ->where('content', '!=', '');
                        });

                        if (!Schema::hasColumn('submissions', 'content') && Schema::hasColumn('submissions', 'submission_type')) {
                            $query->whereNotNull('submission_type')
                                ->where('submission_type', '!=', '');
                        }
                    });
                });

                $submission = $query->first();
            }
        } catch (\Throwable) {
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
                    'type_label' => $assignment->getTypeLabel(),
                ],
                'course' => $course ? [
                    'id' => (int) $course->id,
                    'title' => (string) ($course->title ?? ''),
                    'course_number' => (string) ($course->course_number ?? ''),
                ] : null,
                'submission' => $submission ? [
                    'id' => (int) $submission->id,
                    'assignment_id' => (int) $submission->assignment_id,
                    'student_id' => (int) $submission->student_id,
                    'submission_type' => (string) ($submission->submission_type ?? ''),
                    'content' => $submission->content ?? null,
                    'file_path' => $submission->file_path ?? null,
                    'submitted_at' => isset($submission->submitted_at) ? (string) $submission->submitted_at : null,
                    'score' => $submission->score ?? null,
                    'feedback' => $submission->feedback ?? null,
                ] : null,
            ],
        ]);
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
