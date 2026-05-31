<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentTaskController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);
        $activeTab = $request->query('tab', 'assignments');

        $assignments = collect();
        $exams = collect();
        $quizzes = collect();
        $courses = collect();

        try {
            // Get enrolled courses
            if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
                $courses = DB::table('enrollments')
                    ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                    ->where('enrollments.student_id', $studentId)
                    ->select('courses.id', 'courses.title', 'courses.course_number')
                    ->orderBy('courses.title')
                    ->get();
            }

            // Get assignments
            if (Schema::hasTable('assignments')) {
                $assignmentsQuery = Assignment::query()
                    ->join('courses', 'assignments.course_id', '=', 'courses.id')
                    ->leftJoin('enrollments', function($join) use ($studentId) {
                        $join->on('enrollments.course_id', '=', 'assignments.course_id')
                             ->where('enrollments.student_id', '=', $studentId);
                    })
                    ->leftJoin('submissions', function($join) use ($studentId) {
                        $join->on('submissions.assignment_id', '=', 'assignments.id')
                             ->where('submissions.student_id', '=', $studentId);
                    })
                    ->where('assignments.type', 'assignment')
                    ->whereNotNull('enrollments.student_id')
                    ->select(
                        'assignments.*',
                        'courses.title as course_title',
                        'courses.course_number',
                        'submissions.id as submission_id',
                        'submissions.submitted_at',
                        'submissions.score'
                    );

                if ($courseId > 0) {
                    $assignmentsQuery->where('assignments.course_id', $courseId);
                }

                $assignments = $assignmentsQuery
                    ->orderBy('assignments.due_date', 'asc')
                    ->orderBy('assignments.title', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->status = $item->submission_id ? 'submitted' : 'pending';
                        $item->is_overdue = $item->due_date && now()->isAfter($item->due_date);
                        return $item;
                    });
            }

            // Get exams from exams table
            if (Schema::hasTable('exams')) {
                $examsQuery = Exam::query()
                    ->join('courses', 'exams.course_id', '=', 'courses.id')
                    ->join('enrollments', function($join) use ($studentId) {
                        $join->on('enrollments.course_id', '=', 'exams.course_id')
                             ->where('enrollments.student_id', '=', $studentId);
                    })
                    ->leftJoin('student_exam_attempts', function($join) use ($studentId) {
                        $join->on('student_exam_attempts.exam_id', '=', 'exams.id')
                             ->where('student_exam_attempts.student_id', '=', $studentId);
                    })
                    ->where('exams.is_published', true)
                    ->select(
                        'exams.*',
                        'courses.title as course_title',
                        'courses.course_number',
                        'student_exam_attempts.id as attempt_id',
                        'student_exam_attempts.submitted_at',
                        'student_exam_attempts.score'
                    );

                if ($courseId > 0) {
                    $examsQuery->where('exams.course_id', $courseId);
                }

                $exams = $examsQuery
                    ->orderBy('exams.exam_date', 'asc')
                    ->orderBy('exams.title', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->status = $item->attempt_id ? 'submitted' : 'pending';
                        $item->is_overdue = $item->exam_date && now()->isAfter($item->exam_date);
                        return $item;
                    });
            }

            // Get quizzes from quizzes table
            if (Schema::hasTable('quizzes')) {
                $quizzesQuery = Quiz::query()
                    ->join('courses', 'quizzes.course_id', '=', 'courses.id')
                    ->join('enrollments', function($join) use ($studentId) {
                        $join->on('enrollments.course_id', '=', 'quizzes.course_id')
                             ->where('enrollments.student_id', '=', $studentId);
                    })
                    ->leftJoin('quiz_attempts', function($join) use ($studentId) {
                        $join->on('quiz_attempts.quiz_id', '=', 'quizzes.id')
                             ->where('quiz_attempts.student_id', '=', $studentId);
                    })
                    ->where('quizzes.is_published', true)
                    ->select(
                        'quizzes.*',
                        'courses.title as course_title',
                        'courses.course_number',
                        'quiz_attempts.id as attempt_id',
                        'quiz_attempts.submitted_at',
                        'quiz_attempts.score'
                    );

                if ($courseId > 0) {
                    $quizzesQuery->where('quizzes.course_id', $courseId);
                }

                $quizzes = $quizzesQuery
                    ->orderBy('quizzes.due_date', 'asc')
                    ->orderBy('quizzes.title', 'asc')
                    ->get()
                    ->map(function ($item) {
                        $item->status = $item->attempt_id ? 'submitted' : 'pending';
                        $item->is_overdue = $item->due_date && now()->isAfter($item->due_date);
                        return $item;
                    });
            }

        } catch (\Throwable $e) {
            // Handle database errors gracefully
        }

        return view('student.tasks.index', [
            'assignments' => $assignments,
            'exams' => $exams,
            'quizzes' => $quizzes,
            'courses' => $courses,
            'activeTab' => $activeTab,
            'filters' => ['course_id' => $courseId],
        ]);
    }
}
