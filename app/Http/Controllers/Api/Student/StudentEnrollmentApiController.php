<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentEnrollmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $studentId = (int) $request->user()->id;
        $status = trim((string) $request->query('status', ''));
        $enrollments = [];

        try {
            if (
                !Schema::hasTable('enrollments')
                || !Schema::hasTable('courses')
                || !Schema::hasColumn('enrollments', 'student_id')
                || !Schema::hasColumn('enrollments', 'course_id')
            ) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'enrollments' => [],
                        'filters' => [
                            'status' => $status ?: null,
                        ],
                    ],
                ]);
            }

            $courseNameColumn = $this->courseNameColumn();
            if (!$courseNameColumn) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'enrollments' => [],
                        'filters' => [
                            'status' => $status ?: null,
                        ],
                    ],
                ]);
            }

            $query = DB::table('enrollments')
                ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                ->where('enrollments.student_id', $studentId);

            if ($status !== '' && Schema::hasColumn('enrollments', 'status')) {
                $query->whereRaw('LOWER(enrollments.status) = ?', [strtolower($status)]);
            } elseif (Schema::hasColumn('enrollments', 'status')) {
                $query->whereRaw('LOWER(COALESCE(enrollments.status, ?)) <> ?', ['dropped', 'dropped']);
            }

            $hasTeacherJoin = Schema::hasColumn('courses', 'teacher_id')
                && Schema::hasTable('users')
                && Schema::hasColumn('users', 'id')
                && Schema::hasColumn('users', 'name');

            if ($hasTeacherJoin) {
                $query->leftJoin('users', 'users.id', '=', 'courses.teacher_id');
            }

            $select = [
                'enrollments.id as enrollment_id',
                'enrollments.student_id as student_id',
                'enrollments.course_id as course_id',
            ];

            foreach (['status', 'enrolled_at'] as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $select[] = 'enrollments.' . $column . ' as ' . $column;
                }
            }

            $select[] = 'courses.' . $courseNameColumn . ' as course_name';

            foreach (['course_number', 'semester', 'school_year', 'cover_image', 'overview'] as $column) {
                if (Schema::hasColumn('courses', $column)) {
                    $select[] = 'courses.' . $column . ' as ' . $column;
                }
            }

            if ($hasTeacherJoin) {
                $select[] = 'users.name as teacher_name';
            }

            $rows = $query
                ->select($select)
                ->orderByDesc('enrollments.id')
                ->limit(200)
                ->get();

            $courseIds = $rows->pluck('course_id')->map(fn ($value) => (int) $value)->filter()->values()->all();
            $assignmentTotals = [];
            $submissionTotals = [];

            if (!empty($courseIds) && Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                $totals = DB::table('assignments')
                    ->whereIn('course_id', $courseIds)
                    ->select(['course_id', DB::raw('COUNT(*) as c')])
                    ->groupBy('course_id')
                    ->get();

                foreach ($totals as $total) {
                    $assignmentTotals[(int) $total->course_id] = (int) ($total->c ?? 0);
                }

                if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                    $submitted = DB::table('submissions')
                        ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                        ->where('submissions.student_id', $studentId)
                        ->whereIn('assignments.course_id', $courseIds)
                        ->select(['assignments.course_id as course_id', DB::raw('COUNT(*) as c')])
                        ->groupBy('assignments.course_id')
                        ->get();

                    foreach ($submitted as $item) {
                        $submissionTotals[(int) $item->course_id] = (int) ($item->c ?? 0);
                    }
                }
            }

            $enrollments = $rows->map(function ($row) use ($assignmentTotals, $submissionTotals): array {
                $courseId = (int) ($row->course_id ?? 0);
                $total = (int) ($assignmentTotals[$courseId] ?? 0);
                $done = (int) ($submissionTotals[$courseId] ?? 0);
                $progress = $total > 0 ? (int) round(min(100, max(0, ($done / $total) * 100))) : 0;

                return [
                    'enrollment_id' => (int) ($row->enrollment_id ?? 0),
                    'student_id' => (int) ($row->student_id ?? 0),
                    'course_id' => $courseId,
                    'status' => (string) ($row->status ?? ''),
                    'enrolled_at' => (string) ($row->enrolled_at ?? ''),
                    'course' => [
                        'id' => $courseId,
                        'course_number' => (string) ($row->course_number ?? ''),
                        'title' => (string) ($row->course_name ?? ''),
                        'semester' => (string) ($row->semester ?? ''),
                        'school_year' => (string) ($row->school_year ?? ''),
                        'cover_image' => (string) ($row->cover_image ?? ''),
                        'overview' => (string) ($row->overview ?? ''),
                        'teacher_name' => (string) ($row->teacher_name ?? ''),
                    ],
                    'progress' => $progress,
                    'assignments_total' => $total,
                    'assignments_submitted' => $done,
                ];
            })->values()->all();
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enrollments' => $enrollments,
                'filters' => [
                    'status' => $status ?: null,
                ],
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
