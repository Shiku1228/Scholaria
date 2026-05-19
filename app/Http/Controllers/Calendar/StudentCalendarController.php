<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Calendar\BaseCalendarController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentCalendarController extends BaseCalendarController
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;
        $windowDays = (int) ($request->query('days', 30));
        $windowStart = now()->copy()->startOfDay();
        $windowEnd = now()->copy()->endOfDay()->addDays(max(1, $windowDays));

        $courseIds = [];
        if (
            Schema::hasTable('enrollments')
            && Schema::hasColumn('enrollments', 'student_id')
            && Schema::hasColumn('enrollments', 'course_id')
        ) {
            $q = DB::table('enrollments')->where('student_id', $studentId);
            if (Schema::hasColumn('enrollments', 'status')) {
                $q->whereRaw('LOWER(status) = ?', ['active']);
            }
            $courseIds = $q->pluck('course_id')->map(fn ($v) => (int) $v)->filter()->values()->all();
        }

        $events = [];

        // Course schedule
        if (
            !empty($courseIds)
            && Schema::hasTable('courses')
            && Schema::hasColumn('courses', 'id')
            && Schema::hasColumn('courses', 'days_pattern')
            && Schema::hasColumn('courses', 'start_time')
        ) {
            $courseRows = DB::table('courses')
                ->whereIn('id', $courseIds)
                ->select(['id', 'title', 'course_number', 'days_pattern', 'start_time'])
                ->get();

            $dayMap = [
                'Sun' => 0,
                'Mon' => 1,
                'Tue' => 2,
                'Wed' => 3,
                'Thu' => 4,
                'Fri' => 5,
                'Sat' => 6,
            ];

            $dates = [];
            $cursor = $windowStart->copy();
            while ($cursor->lte($windowEnd)) {
                $dates[] = $cursor->copy();
                $cursor->addDay();
            }

            foreach ($courseRows as $c) {
                $daysRaw = (string) ($c->days_pattern ?? '');
                $startTime = (string) ($c->start_time ?? '');
                if ($daysRaw === '' || $startTime === '') {
                    continue;
                }

                $days = array_values(array_filter(array_map('trim', explode(',', $daysRaw))));
                if (empty($days)) {
                    continue;
                }

                foreach ($dates as $d) {
                    $dow = (string) $d->format('D');
                    if (!in_array($dow, $days, true)) {
                        continue;
                    }

                    [$hh, $mm, $ss] = array_pad(explode(':', $startTime), 3, '00');
                    $dt = $d->copy()->setTime((int) $hh, (int) $mm, (int) $ss);

                    if ($dt->lt($windowStart) || $dt->gt($windowEnd)) {
                        continue;
                    }

                    $courseName = (string) ($c->title ?? $c->course_number ?? 'Course');
                    $events[] = [
                        'date' => $dt->format('Y-m-d'),
                        'datetime' => $dt->toDateTimeString(),
                        'title' => $courseName . ' class',
                        'type' => 'class',
                        'href' => route('student.courses.show', ['course' => (int) $c->id]) . '#tasks',
                        'meta' => $dt->format('g:i A'),
                    ];
                }
            }
        }

        // Assignments/quizzes (assignments.type = assignment/quiz) with due_date
        if (
            !empty($courseIds)
            && $this->hasColumns('assignments', ['course_id', 'due_date', 'type'])
        ) {
            $rows = DB::table('assignments')
                ->join('courses', 'courses.id', '=', 'assignments.course_id')
                ->whereIn('assignments.course_id', $courseIds)
                ->whereNotNull('assignments.due_date')
                ->whereBetween('assignments.due_date', [$windowStart, $windowEnd])
                ->select([
                    'assignments.id',
                    'assignments.title',
                    'assignments.type',
                    'assignments.due_date',
                    'courses.title as course_title',
                    'courses.course_number as course_number',
                    'courses.id as course_id',
                ])
                ->limit(200)
                ->get();

            foreach ($rows as $r) {
                $type = (string) ($r->type ?? 'assignment');
                $label = $type === 'quiz' ? 'Quiz' : 'Assignment';
                $courseName = (string) ($r->course_title ?? $r->course_number ?? '');

                $href = route('student.assignments.show', ['assignment' => (int) $r->id]);
                $events[] = [
                    'date' => (string) \Carbon\Carbon::parse((string) $r->due_date)->format('Y-m-d'),
                    'datetime' => (string) $r->due_date,
                    'title' => $label . ': ' . (string) ($r->title ?? ''),
                    'type' => $type,
                    'href' => $href,
                    'meta' => 'Due ' . \Carbon\Carbon::parse((string) $r->due_date)->format('M d, Y g:i A'),
                ];
            }
        }

        // Exams
        if (
            !empty($courseIds)
            && Schema::hasTable('exams')
            && Schema::hasColumn('exams', 'course_id')
            && Schema::hasColumn('exams', 'exam_date')
        ) {
            $rows = DB::table('exams')
                ->join('courses', 'courses.id', '=', 'exams.course_id')
                ->whereIn('exams.course_id', $courseIds)
                ->whereNotNull('exams.exam_date')
                ->whereBetween('exams.exam_date', [$windowStart, $windowEnd])
                ->select([
                    'exams.id',
                    'exams.title',
                    'exams.exam_date',
                    'exams.course_id',
                    'courses.title as course_title',
                    'courses.course_number as course_number',
                ])
                ->limit(200)
                ->get();

            foreach ($rows as $r) {
                $events[] = [
                    'date' => (string) \Carbon\Carbon::parse((string) $r->exam_date)->format('Y-m-d'),
                    'datetime' => (string) $r->exam_date,
                    'title' => 'Exam: ' . (string) ($r->title ?? ''),
                    'type' => 'exam',
                    'href' => route('student.exams.show', ['exam' => (int) $r->id]),
                    'meta' => 'Scheduled ' . \Carbon\Carbon::parse((string) $r->exam_date)->format('M d, Y g:i A'),
                ];
            }
        }

        // Announcements: show created_at
        if (
            !empty($courseIds)
            && Schema::hasTable('announcements')
            && Schema::hasColumn('announcements', 'course_id')
            && Schema::hasColumn('announcements', 'created_at')
        ) {
            $rows = DB::table('announcements')
                ->whereIn('course_id', $courseIds)
                ->whereBetween('created_at', [$windowStart, $windowEnd])
                ->select(['id', 'title', 'message', 'created_at', 'course_id'])
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();

            foreach ($rows as $r) {
                $events[] = [
                    'date' => (string) \Carbon\Carbon::parse((string) $r->created_at)->format('Y-m-d'),
                    'datetime' => (string) $r->created_at,
                    'title' => 'Announcement: ' . (string) ($r->title ?? ''),
                    'type' => 'announcement',
                    'href' => route('student.courses.index') . '?announcement=1',
                    'meta' => 'Posted ' . \Carbon\Carbon::parse((string) $r->created_at)->format('M d, Y'),
                ];
            }
        }

        $agenda = $this->buildAgenda($events);

        return view('student.calendar', [
            'agenda' => $agenda,
            'windowDays' => $windowDays,
            'windowStart' => $windowStart->format('M d, Y'),
            'windowEnd' => $windowEnd->format('M d, Y'),
        ]);
    }
}

