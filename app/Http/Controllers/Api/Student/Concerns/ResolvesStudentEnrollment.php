<?php

namespace App\Http\Controllers\Api\Student\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ResolvesStudentEnrollment
{
    private function enrollmentAccessDetails(int $studentId, int $courseId): array
    {
        \Illuminate\Support\Facades\Log::info("ResolvesStudentEnrollment check: student_id={$studentId}, course_id={$courseId}");
        $includeDebug = (bool) (config('app.debug') || (function_exists('request') && request()?->boolean('debug')));

        $details = [
            'allowed' => false,
            'reason' => 'enrollment_data_unavailable',
            'message' => 'Enrollment data is unavailable.',
            'meta' => [
                'student_id' => $studentId,
                'id' => $courseId,
                'has_enrollment_row' => false,
                'enrollment_status' => null,
            ],
            'debug' => null,
        ];

        try {
            if (
                !Schema::hasTable('enrollments')
                || !Schema::hasColumn('enrollments', 'student_id')
                || !Schema::hasColumn('enrollments', 'course_id')
            ) {
                return $details;
            }

            $baseQuery = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->where('course_id', $courseId);

            if (!(clone $baseQuery)->exists()) {
                $details['reason'] = 'enrollment_missing';
                $details['message'] = 'No enrollment record was found for this course.';

                if ($includeDebug) {
                    $details['debug'] = $this->buildEnrollmentDebug($studentId, $courseId, $baseQuery, false);
                }

                return $details;
            }

            $status = null;
            if (Schema::hasColumn('enrollments', 'status')) {
                $row = (clone $baseQuery)->select('status')->first();
                $status = strtolower((string) ($row->status ?? ''));
            }

            $details['meta']['has_enrollment_row'] = true;
            $details['meta']['enrollment_status'] = $status !== '' ? $status : null;

            if ($includeDebug) {
                $details['debug'] = $this->buildEnrollmentDebug($studentId, $courseId, $baseQuery, true);
            }

            if ($status === 'dropped') {
                $details['reason'] = 'enrollment_dropped';
                $details['message'] = 'Your enrollment for this course has been dropped.';

                return $details;
            }

            $details['allowed'] = true;
            $details['reason'] = null;
            $details['message'] = '';

            return $details;
        } catch (\Throwable $throwable) {
            $details['reason'] = 'enrollment_check_failed';
            $details['message'] = 'Unable to verify course enrollment right now.';
            $details['meta']['error'] = $throwable->getMessage();

            return $details;
        }
    }

    private function enrollmentDeniedResponse(int $studentId, int $courseId): JsonResponse
    {
        $details = $this->enrollmentAccessDetails($studentId, $courseId);

        $payload = [
            'success' => false,
            'message' => $details['message'],
            'reason' => $details['reason'],
            'meta' => $details['meta'],
        ];

        if (!empty($details['debug'])) {
            $payload['debug'] = $details['debug'];
        }

        return response()->json($payload, 403);
    }

    private function hasStudentEnrollmentAccess(int $studentId, int $courseId): bool
    {
        return $this->enrollmentAccessDetails($studentId, $courseId)['allowed'];
    }

    private function buildEnrollmentDebug(int $studentId, int $courseId, $baseQuery, bool $hasMatchingRow): array
    {
        $debug = [
            'student_id' => $studentId,
            'id' => $courseId,
            'course_exists' => Schema::hasTable('courses') ? DB::table('courses')->where('id', $courseId)->exists() : null,
            'student_enrollment_count' => null,
            'student_enrollment_course_ids' => [],
            'matching_enrollment_row' => null,
            'has_matching_row' => $hasMatchingRow,
        ];

        try {
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
                $studentEnrollments = DB::table('enrollments')
                    ->where('student_id', $studentId);

                $debug['student_enrollment_count'] = (int) $studentEnrollments->count();
                $debug['student_enrollment_course_ids'] = $studentEnrollments
                    ->pluck('course_id')
                    ->map(fn ($value) => (int) $value)
                    ->filter()
                    ->values()
                    ->all();

                if ($hasMatchingRow) {
                    $debug['matching_enrollment_row'] = (clone $baseQuery)
                        ->select([
                            'id',
                            'student_id',
                            'course_id',
                            Schema::hasColumn('enrollments', 'teacher_id') ? 'teacher_id' : DB::raw('NULL as teacher_id'),
                            Schema::hasColumn('enrollments', 'status') ? 'status' : DB::raw('NULL as status'),
                            Schema::hasColumn('enrollments', 'enrolled_at') ? 'enrolled_at' : DB::raw('NULL as enrolled_at'),
                            Schema::hasColumn('enrollments', 'created_at') ? 'created_at' : DB::raw('NULL as created_at'),
                            Schema::hasColumn('enrollments', 'updated_at') ? 'updated_at' : DB::raw('NULL as updated_at'),
                        ])
                        ->first();
                }
            }
        } catch (\Throwable $throwable) {
            $debug['error'] = $throwable->getMessage();
        }

        return $debug;
    }
}
