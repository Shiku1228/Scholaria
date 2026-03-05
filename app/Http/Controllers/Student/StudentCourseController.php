<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentCourseController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;

        $courses = [];

        try {
            if (Schema::hasTable('enrollments') && Schema::hasTable('courses') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
                $courseNameColumn = null;
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameColumn = $candidate;
                        break;
                    }
                }

                if ($courseNameColumn) {
                    $query = DB::table('enrollments')
                        ->join('courses', 'courses.id', '=', 'enrollments.course_id')
                        ->where('enrollments.student_id', $studentId);

                    $hasTeacherJoin = Schema::hasColumn('courses', 'teacher_id')
                        && Schema::hasTable('users')
                        && Schema::hasColumn('users', 'id')
                        && Schema::hasColumn('users', 'name');

                    if ($hasTeacherJoin) {
                        $query->leftJoin('users', 'users.id', '=', 'courses.teacher_id');
                    }

                    $select = [
                        'courses.id as course_id',
                        'courses.' . $courseNameColumn . ' as course_name',
                    ];

                    if ($hasTeacherJoin) {
                        $select[] = 'users.name as teacher_name';
                    }

                    if (Schema::hasColumn('enrollments', 'status')) {
                        $select[] = 'enrollments.status as enrollment_status';
                    }

                    $rows = $query
                        ->select($select)
                        ->orderBy('courses.id', 'desc')
                        ->get();

                    $courseIds = $rows->pluck('course_id')->map(fn ($v) => (int) $v)->filter()->values()->all();

                    $assignmentTotals = [];
                    $submissionTotals = [];

                    if (!empty($courseIds) && Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id')) {
                        $totals = DB::table('assignments')
                            ->whereIn('course_id', $courseIds)
                            ->select(['course_id', DB::raw('COUNT(*) as c')])
                            ->groupBy('course_id')
                            ->get();

                        foreach ($totals as $t) {
                            $assignmentTotals[(int) $t->course_id] = (int) ($t->c ?? 0);
                        }

                        if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                            $submitted = DB::table('submissions')
                                ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                                ->where('submissions.student_id', $studentId)
                                ->whereIn('assignments.course_id', $courseIds)
                                ->select(['assignments.course_id as course_id', DB::raw('COUNT(*) as c')])
                                ->groupBy('assignments.course_id')
                                ->get();

                            foreach ($submitted as $s) {
                                $submissionTotals[(int) $s->course_id] = (int) ($s->c ?? 0);
                            }
                        }
                    }

                    $courses = $rows->map(function ($r) use ($assignmentTotals, $submissionTotals) {
                        $courseId = (int) ($r->course_id ?? 0);
                        $total = (int) ($assignmentTotals[$courseId] ?? 0);
                        $done = (int) ($submissionTotals[$courseId] ?? 0);
                        $progress = $total > 0 ? (int) round(min(100, max(0, ($done / $total) * 100))) : 0;

                        return [
                            'course_id' => $courseId,
                            'course_name' => (string) ($r->course_name ?? ''),
                            'teacher_name' => (string) ($r->teacher_name ?? ''),
                            'enrollment_status' => (string) ($r->enrollment_status ?? ''),
                            'progress' => $progress,
                            'assignments_total' => $total,
                            'assignments_submitted' => $done,
                        ];
                    })->values()->all();
                }
            }
        } catch (\Throwable) {
        }

        return view('student.courses.index', [
            'courses' => $courses,
        ]);
    }
}
