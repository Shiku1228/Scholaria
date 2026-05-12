<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);

        $assignments = [];

        try {
            if (!Schema::hasTable('enrollments') || !Schema::hasColumn('enrollments', 'student_id') || !Schema::hasColumn('enrollments', 'course_id')) {
                return view('student.assignments.index', [
                    'assignments' => [],
                    'filters' => ['course_id' => $courseId],
                ]);
            }

            if (!Schema::hasTable('assignments')) {
                return view('student.assignments.index', [
                    'assignments' => [],
                    'filters' => ['course_id' => $courseId],
                ]);
            }

            $courseNameColumn = null;
            if (Schema::hasTable('courses')) {
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameColumn = $candidate;
                        break;
                    }
                }
            }

            $enrolledCourseIds = DB::table('enrollments')->where('student_id', $studentId)->pluck('course_id')->map(fn ($v) => (int) $v)->filter()->values()->all();

            if (empty($enrolledCourseIds)) {
                return view('student.assignments.index', [
                    'assignments' => [],
                    'filters' => ['course_id' => $courseId],
                ]);
            }

            if ($courseId > 0 && !in_array($courseId, $enrolledCourseIds, true)) {
                abort(403);
            }

            $query = DB::table('assignments');

            if ($courseNameColumn && Schema::hasTable('courses') && Schema::hasColumn('assignments', 'course_id')) {
                $query->join('courses', 'courses.id', '=', 'assignments.course_id');
            }

            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                $query->leftJoin('submissions', function ($join) use ($studentId) {
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

            $assignments = $rows->map(function ($r) {
                return [
                    'assignment_id' => (int) ($r->assignment_id ?? 0),
                    'title' => (string) ($r->title ?? ''),
                    'course_id' => (int) ($r->course_id ?? 0),
                    'course_name' => (string) ($r->course_name ?? ''),
                    'due_date' => (string) ($r->due_date ?? ''),
                    'submission_id' => (int) ($r->submission_id ?? 0),
                    'submitted_at' => (string) ($r->submitted_at ?? ''),
                    'score' => $r->score ?? null,
                ];
            })->values()->all();
        } catch (\Throwable) {
        }

        return view('student.assignments.index', [
            'assignments' => $assignments,
            'filters' => [
                'course_id' => $courseId,
            ],
        ]);
    }
}
