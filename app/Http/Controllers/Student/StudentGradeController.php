<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentGradeController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;

        $rows = [];

        try {
            if (!Schema::hasTable('enrollments') || !Schema::hasColumn('enrollments', 'student_id') || !Schema::hasColumn('enrollments', 'course_id')) {
                return view('student.grades.index', ['rows' => []]);
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

            if (empty($enrolledCourseIds) || !Schema::hasTable('assignments') || !Schema::hasColumn('assignments', 'course_id')) {
                return view('student.grades.index', ['rows' => []]);
            }

            $query = DB::table('assignments')
                ->whereIn('assignments.course_id', $enrolledCourseIds);

            if ($courseNameColumn && Schema::hasTable('courses')) {
                $query->join('courses', 'courses.id', '=', 'assignments.course_id');
            }

            if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'assignment_id') && Schema::hasColumn('grades', 'student_id')) {
                $query->leftJoin('grades', function ($join) use ($studentId) {
                    $join->on('grades.assignment_id', '=', 'assignments.id')
                        ->where('grades.student_id', '=', $studentId);
                });
            } elseif (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'assignment_id') && Schema::hasColumn('submissions', 'student_id')) {
                $query->leftJoin('submissions', function ($join) use ($studentId) {
                    $join->on('submissions.assignment_id', '=', 'assignments.id')
                        ->where('submissions.student_id', '=', $studentId);
                });
            }

            $select = [
                'assignments.id as assignment_id',
                'assignments.title as assignment_title',
                'assignments.course_id as course_id',
            ];

            if ($courseNameColumn) {
                $select[] = 'courses.' . $courseNameColumn . ' as course_name';
            }

            if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'score')) {
                $select[] = 'grades.score as score';
            } elseif (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'score')) {
                $select[] = 'submissions.score as score';
            }

            if (Schema::hasTable('grades') && Schema::hasColumn('grades', 'feedback')) {
                $select[] = 'grades.feedback as feedback';
            } elseif (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'feedback')) {
                $select[] = 'submissions.feedback as feedback';
            }

            $raw = $query
                ->select($select)
                ->orderByDesc('assignments.id')
                ->limit(500)
                ->get();

            $rows = $raw->map(fn ($r) => [
                'course_name' => (string) ($r->course_name ?? ''),
                'assignment_title' => (string) ($r->assignment_title ?? ''),
                'score' => $r->score ?? null,
                'feedback' => (string) ($r->feedback ?? ''),
            ])->values()->all();
        } catch (\Throwable) {
        }

        return view('student.grades.index', [
            'rows' => $rows,
        ]);
    }
}
