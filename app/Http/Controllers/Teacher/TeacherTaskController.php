<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherTaskController extends Controller
{
    public function overview(Request $request): View
    {
        $teacherId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);
        $activeTab = $request->query('tab', 'assignments');

        $assignments = collect();
        $exams = collect();
        $quizzes = collect();
        $courses = collect();

        try {
            // Get teacher's courses
            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $courses = DB::table('courses')
                    ->where('teacher_id', $teacherId)
                    ->select('id', 'title', 'course_number')
                    ->orderBy('title')
                    ->get();
            }

            // Get assignments
            if (Schema::hasTable('assignments')) {
                $assignmentsQuery = Assignment::query()
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->where('courses.teacher_id', $teacherId)
                    ->where('assignments.type', 'assignment')
                    ->leftJoin('submissions', 'assignments.id', '=', 'submissions.assignment_id')
                    ->leftJoin('users', 'submissions.student_id', '=', 'users.id')
                    ->select(
                        'assignments.*',
                        'courses.title as course_title',
                        'courses.course_number',
                        DB::raw('COUNT(DISTINCT submissions.id) as submission_count'),
                        DB::raw('COUNT(DISTINCT CASE WHEN submissions.submitted_at IS NOT NULL THEN submissions.id END) as completed_count')
                    )
                    ->groupBy('assignments.id', 'courses.title', 'courses.course_number');

                if ($courseId > 0) {
                    $assignmentsQuery->where('assignments.course_id', $courseId);
                }

                $assignments = $assignmentsQuery
                    ->orderBy('assignments.due_date', 'asc')
                    ->orderBy('assignments.title', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->completion_rate = $item->submission_count > 0 
                            ? round(($item->completed_count / $item->submission_count) * 100, 1) 
                            : 0;
                        $item->is_overdue = $item->due_date && now()->isAfter($item->due_date);
                        return $item;
                    });
            }

            // Get exams
            if (Schema::hasTable('exams')) {
                $examsQuery = Exam::query()
                    ->join('courses', 'exams.course_id', '=', 'courses.id')
                    ->where('courses.teacher_id', $teacherId)
                    ->leftJoin('student_exam_attempts', 'exams.id', '=', 'student_exam_attempts.exam_id')
                    ->select(
                        'exams.*',
                        'courses.title as course_title',
                        'courses.course_number',
                        DB::raw('COUNT(DISTINCT student_exam_attempts.id) as submission_count'),
                        DB::raw('COUNT(DISTINCT CASE WHEN student_exam_attempts.status IN (\'submitted\', \'graded\') THEN student_exam_attempts.id END) as completed_count')
                    )
                    ->groupBy('exams.id', 'courses.title', 'courses.course_number');

                if ($courseId > 0) {
                    $examsQuery->where('exams.course_id', $courseId);
                }

                $exams = $examsQuery
                    ->orderBy('exams.exam_date', 'asc')
                    ->orderBy('exams.title', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->completion_rate = $item->submission_count > 0
                            ? round(($item->completed_count / $item->submission_count) * 100, 1)
                            : 0;
                        $item->is_overdue = $item->exam_date && now()->isAfter($item->exam_date);
                        return $item;
                    });
            }

            // Get quizzes
            if (Schema::hasTable('assignments')) {
                $quizzesQuery = Assignment::query()
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->where('courses.teacher_id', $teacherId)
                    ->where('assignments.type', 'quiz')
                    ->leftJoin('submissions', 'assignments.id', '=', 'submissions.assignment_id')
                    ->leftJoin('users', 'submissions.student_id', '=', 'users.id')
                    ->select(
                        'assignments.*',
                        'courses.title as course_title',
                        'courses.course_number',
                        DB::raw('COUNT(DISTINCT submissions.id) as submission_count'),
                        DB::raw('COUNT(DISTINCT CASE WHEN submissions.submitted_at IS NOT NULL THEN submissions.id END) as completed_count')
                    )
                    ->groupBy('assignments.id', 'courses.title', 'courses.course_number');

                if ($courseId > 0) {
                    $quizzesQuery->where('assignments.course_id', $courseId);
                }

                $quizzes = $quizzesQuery
                    ->orderBy('assignments.due_date', 'asc')
                    ->orderBy('assignments.title', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->completion_rate = $item->submission_count > 0 
                            ? round(($item->completed_count / $item->submission_count) * 100, 1) 
                            : 0;
                        $item->is_overdue = $item->due_date && now()->isAfter($item->due_date);
                        return $item;
                    });
            }

        } catch (\Throwable $e) {
            // Handle database errors gracefully
        }

        return view('teacher.tasks.overview', [
            'assignments' => $assignments,
            'exams' => $exams,
            'quizzes' => $quizzes,
            'courses' => $courses,
            'activeTab' => $activeTab,
            'filters' => ['course_id' => $courseId],
        ]);
    }
}
