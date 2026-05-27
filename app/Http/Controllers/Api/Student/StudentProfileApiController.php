<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentProfileApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $studentProfile = null;
        $teacherProfile = null;

        try {
            $user->loadMissing(['student', 'teacher']);
        } catch (\Throwable) {
        }

        if ($user->relationLoaded('student') && $user->student) {
            $studentProfile = [
                'id' => (int) $user->student->id,
                'user_id' => (int) $user->student->user_id,
                'first_name' => (string) ($user->student->first_name ?? ''),
                'middle_name' => (string) ($user->student->middle_name ?? ''),
                'last_name' => (string) ($user->student->last_name ?? ''),
                'full_name' => (string) ($user->student->full_name ?? $user->name),
                'student_number' => (string) ($user->student->student_number ?? ''),
                'year_level' => (string) ($user->student->year_level ?? ''),
                'program' => (string) ($user->student->program ?? ''),
                'college' => (string) ($user->student->college ?? ''),
                'enrollment_date' => optional($user->student->enrollment_date)->toDateTimeString(),
            ];
        }

        if ($user->relationLoaded('teacher') && $user->teacher) {
            $teacherProfile = [
                'id' => (int) $user->teacher->id,
                'user_id' => (int) $user->teacher->user_id,
                'first_name' => (string) ($user->teacher->first_name ?? ''),
                'middle_name' => (string) ($user->teacher->middle_name ?? ''),
                'last_name' => (string) ($user->teacher->last_name ?? ''),
                'full_name' => (string) ($user->teacher->full_name ?? $user->name),
                'employee_id' => (string) ($user->teacher->employee_id ?? ''),
                'college' => (string) ($user->teacher->college ?? ''),
                'program' => (string) ($user->teacher->program ?? ''),
                'specialization' => (string) ($user->teacher->specialization ?? ''),
                'hire_date' => optional($user->teacher->hire_date)->toDateTimeString(),
            ];
        }

        $roles = [];
        try {
            $roles = $user->getRoleNames()->values()->all();
        } catch (\Throwable) {
        }

        $stats = [
            'enrolled_courses' => 0,
            'assignments_total' => 0,
            'assignments_submitted' => 0,
            'unread_notifications' => 0,
        ];

        try {
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id')) {
                $stats['enrolled_courses'] = (int) DB::table('enrollments')
                    ->where('student_id', $user->id)
                    ->when(Schema::hasColumn('enrollments', 'status'), function ($query): void {
                        $query->whereRaw('LOWER(status) = ?', ['active']);
                    })
                    ->count();
            }

            if (Schema::hasTable('assignments') && Schema::hasTable('submissions')) {
                $studentCourseIds = Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'course_id')
                    ? DB::table('enrollments')
                        ->where('student_id', $user->id)
                        ->pluck('course_id')
                        ->map(fn ($value) => (int) $value)
                        ->filter()
                        ->values()
                        ->all()
                    : [];

                if (!empty($studentCourseIds) && Schema::hasColumn('assignments', 'course_id')) {
                    $stats['assignments_total'] = (int) DB::table('assignments')
                        ->whereIn('course_id', $studentCourseIds)
                        ->count();

                    if (Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                        $stats['assignments_submitted'] = (int) DB::table('submissions')
                            ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                            ->where('submissions.student_id', $user->id)
                            ->whereIn('assignments.course_id', $studentCourseIds)
                            ->count();
                    }
                }
            }

            if (Schema::hasTable('notifications')) {
                $stats['unread_notifications'] = (int) $user->unreadNotifications()->count();
            }
        } catch (\Throwable) {
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?? ''),
                    'email' => (string) ($user->email ?? ''),
                    'full_name' => (string) ($user->full_name ?? $user->name ?? ''),
                    'first_name' => (string) ($user->first_name ?? ''),
                    'middle_name' => (string) ($user->middle_name ?? ''),
                    'last_name' => (string) ($user->last_name ?? ''),
                    'student_number' => (string) ($user->student_number ?? ''),
                    'profile_type' => (string) ($user->profile_type ?? ''),
                    'profile_id' => $user->profile_id !== null ? (int) $user->profile_id : null,
                    'roles' => $roles,
                    'primary_role' => $roles[0] ?? null,
                ],
                'student' => $studentProfile,
                'teacher' => $teacherProfile,
                'stats' => $stats,
            ],
        ]);
    }
}
