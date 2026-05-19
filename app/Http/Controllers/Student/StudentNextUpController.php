<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Exam;
use App\Models\Quiz;
use App\Models\CourseDiscussion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentNextUpController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;

        // Due soon window (days)
        $windowDays = (int) ($request->query('days', 7));
        $windowStart = now();
        $windowEnd = now()->addDays(max(1, $windowDays));

        $dueSoon = [];
        $notStarted = [];
        $needsReview = [];
        $recommended = [];

        // We treat "graded but not reviewed" as a placeholder for Phase 1.
        // Since rubric workflow doesn't exist yet, we only populate needsReview when feedback exists.
        // This is best-effort and uses existing Submission.feedback / Grade.feedback.
        $hasAnyFeedback = function () use ($studentId) {
            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'student_id') && Schema::hasColumn('submissions', 'feedback')) {
                return (int) DB::table('submissions')
                    ->where('student_id', $studentId)
                    ->whereNotNull('feedback')
                    ->where(DB::raw('LENGTH(feedback)'), '>', 0)
                    ->limit(1)
                    ->count() > 0;
            }
            return false;
        };

        // Fetch enrolled course ids
        $courseIds = collect();
        if (Schema::hasTable('enrollments') && Schema::hasColumn('enrollments', 'student_id') && Schema::hasColumn('enrollments', 'course_id')) {
            $courseIds = DB::table('enrollments')
                ->where('student_id', $studentId)
                ->pluck('course_id')
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->values();
        }

        $courseIdList = $courseIds->all();

        // Build student submission state for assignments/quizzes (assignment table stores type=quiz/assignment/exam)
        $assignmentAttemptedAssignmentIds = [];
        if (!empty($courseIdList) && Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'student_id') && Schema::hasColumn('submissions', 'assignment_id')) {
            $assignmentAttemptedAssignmentIds = DB::table('submissions')
                ->where('student_id', $studentId)
                ->pluck('assignment_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // Exam attempts tracked in student_exam_attempts
        $examAttemptedExamIds = [];
        if (!empty($courseIdList) && Schema::hasTable('student_exam_attempts') && Schema::hasColumn('student_exam_attempts', 'student_id') && Schema::hasColumn('student_exam_attempts', 'exam_id')) {
            $examAttemptedExamIds = DB::table('student_exam_attempts')
                ->where('student_id', $studentId)
                ->pluck('exam_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // Quizzes attempts (quiz_attempts table)
        $quizAttemptedQuizIds = [];
        if (!empty($courseIdList) && Schema::hasTable('quiz_attempts') && Schema::hasColumn('quiz_attempts', 'student_id') && Schema::hasColumn('quiz_attempts', 'quiz_id')) {
            $quizAttemptedQuizIds = DB::table('quiz_attempts')
                ->where('student_id', $studentId)
                ->pluck('quiz_id')
                ->map(fn ($v) => (int) $v)
                ->all();
        }

        // 1) Due soon: any assignments/quizzes/exams with due/exam_date within window
        if (!empty($courseIdList)) {
            // Assignments
            if (Schema::hasTable('assignments') && Schema::hasColumn('assignments', 'course_id') && Schema::hasColumn('assignments', 'type') && Schema::hasColumn('assignments', 'due_date')) {
                $assignmentsQuery = DB::table('assignments')
                    ->whereIn('course_id', $courseIdList)
                    ->whereIn('type', ['assignment', 'quiz'])
                    ->whereNotNull('due_date')
                    ->whereBetween('due_date', [$windowStart, $windowEnd])
                    ->select(['id as task_id', 'title', 'course_id', 'due_date', 'type']);

                $dueSoonRows = $assignmentsQuery->limit(15)->get();
                foreach ($dueSoonRows as $r) {
                    $dueSoon[] = [
                        'id' => (int) $r->task_id,
                        'title' => (string) $r->title,
                        'course_id' => (int) $r->course_id,
                        'due' => (string) $r->due_date,
                        'type' => (string) $r->type,
                    ];
                }
            }

            // Quizzes table (if separate)
            if (Schema::hasTable('quizzes') && Schema::hasColumn('quizzes', 'course_id') && Schema::hasColumn('quizzes', 'due_date')) {
                $quizRows = DB::table('quizzes')
                    ->whereIn('course_id', $courseIdList)
                    ->whereNotNull('due_date')
                    ->whereBetween('due_date', [$windowStart, $windowEnd])
                    ->select(['id as task_id', 'title', 'course_id', 'due_date']);

                $quizRows = $quizRows->limit(15)->get();
                foreach ($quizRows as $r) {
                    $dueSoon[] = [
                        'id' => (int) $r->task_id,
                        'title' => (string) $r->title,
                        'course_id' => (int) $r->course_id,
                        'due' => (string) $r->due_date,
                        'type' => 'quiz',
                    ];
                }
            }

            // Exams
            if (Schema::hasTable('exams') && Schema::hasColumn('exams', 'course_id') && Schema::hasColumn('exams', 'exam_date')) {
                $examRows = DB::table('exams')
                    ->whereIn('course_id', $courseIdList)
                    ->whereNotNull('exam_date')
                    ->whereBetween('exam_date', [$windowStart, $windowEnd])
                    ->select(['id as task_id', 'title', 'course_id', 'exam_date']);

                $examRows = $examRows->limit(15)->get();
                foreach ($examRows as $r) {
                    $dueSoon[] = [
                        'id' => (int) $r->task_id,
                        'title' => (string) $r->title,
                        'course_id' => (int) $r->course_id,
                        'due' => (string) $r->exam_date,
                        'type' => 'exam',
                    ];
                }
            }
        }

        // 2) Not started: tasks due soon where student has no attempted/submitted record
        $dueSoonIds = collect($dueSoon)->map(fn ($x) => [$x['type'], (int) $x['id']])->values();

        foreach ($dueSoon as $item) {
            $type = (string) ($item['type'] ?? 'assignment');
            $id = (int) ($item['id'] ?? 0);

            $started = false;
            if ($type === 'exam') {
                $started = in_array($id, $examAttemptedExamIds, true);
            } elseif ($type === 'quiz') {
                // Could be assignments type=quiz OR quizzes table
                $started = in_array($id, $quizAttemptedQuizIds, true) || in_array($id, $assignmentAttemptedAssignmentIds, true);
            } else {
                // assignment
                $started = in_array($id, $assignmentAttemptedAssignmentIds, true);
            }

            if (!$started) {
                $notStarted[] = $item;
            }
        }

        // 3) Needs review (Phase 1 placeholder): if student has any Submission/Grade feedback not empty.
        if ($hasAnyFeedback()) {
            // Best-effort: grab latest graded submissions/grades.
            if (Schema::hasTable('submissions') && Schema::hasColumn('submissions', 'student_id') && Schema::hasColumn('submissions', 'feedback')) {
                $needsReview = DB::table('submissions')
                    ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                    ->where('submissions.student_id', $studentId)
                    ->whereNotNull('submissions.feedback')
                    ->where(DB::raw('LENGTH(submissions.feedback)'), '>', 0)
                    ->orderByDesc('submissions.submitted_at')
                    ->limit(6)
                    ->get()
                    ->map(function ($r) {
                        return [
                            'id' => (int) $r->assignment_id,
                            'title' => (string) $r->assignments.title,
                            'course_id' => (int) $r->assignments.course_id,
                            'due' => (string) ($r->submitted_at ?? ''),
                            'type' => (string) ($r->assignments.type ?? 'assignment'),
                            'feedback' => (string) $r->feedback,
                        ];
                    })
                    ->values()
                    ->all();
            }
        }

        // 4) Recommended (Phase 1 heuristic): not started first, then due soon.
        $recommended = array_slice($notStarted, 0, 6);
        if (count($recommended) < 6) {
            $recommended = array_merge($recommended, array_slice($dueSoon, 0, 6 - count($recommended)));
        }

        // Reduce duplicates by (type,id)
        $dedupe = function (array $items) {
            $seen = [];
            $out = [];
            foreach ($items as $i) {
                $key = ($i['type'] ?? 'x') . ':' . (int) ($i['id'] ?? 0);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $out[] = $i;
            }
            return $out;
        };

        $dueSoon = $dedupe($dueSoon);
        $notStarted = $dedupe($notStarted);
        $needsReview = $dedupe($needsReview);
        $recommended = $dedupe($recommended);

        return view('student.next-up.index', [
            'dueSoon' => $dueSoon,
            'notStarted' => $notStarted,
            'needsReview' => $needsReview,
            'recommended' => $recommended,
            'days' => $windowDays,
        ]);
    }
}

