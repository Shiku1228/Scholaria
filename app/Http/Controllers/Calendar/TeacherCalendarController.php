<?php

namespace App\Http\Controllers\Calendar;

use App\Http\Controllers\Calendar\BaseCalendarController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TeacherCalendarController extends BaseCalendarController
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;
        $windowDays = (int) ($request->query('days', 30));
        $windowStart = now()->copy()->startOfDay();
        $windowEnd = now()->copy()->endOfDay()->addDays(max(1, $windowDays));

        $events = [];

        // Course schedule (days_pattern + start_time)
        if (
            Schema::hasTable('courses')
            && Schema::hasColumn('courses', 'teacher_id')
            && Schema::hasColumn('courses', 'days_pattern')
            && Schema::hasColumn('courses', 'start_time')
        ) {
            $courseRows = DB::table('courses')
                ->where('teacher_id', $teacherId)
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
                        'href' => route('teacher.courses.show', ['course' => (int) $c->id]) . '#overview',
                        'meta' => $dt->format('g:i A'),
                    ];
                }
            }
        }

        // Assignments (type=assignment OR quiz) with due_date
        if (
            $this->hasColumns('assignments', ['course_id', 'due_date', 'type'])
            && $this->hasColumns('courses', ['id', 'teacher_id'])
        ) {
            $rows = DB::table('assignments')
                ->join('courses', 'courses.id', '=', 'assignments.course_id')
                ->where('courses.teacher_id', $teacherId)
                ->whereNotNull('assignments.due_date')
                ->whereBetween('assignments.due_date', [$windowStart, $windowEnd])
                ->select([
                    'assignments.id',
                    'assignments.title',
                    'assignments.type',
                    'assignments.due_date',
                    'courses.id as course_id',
                    'courses.title as course_title',
                    'courses.course_number as course_number',
                ])
                ->get();

            foreach ($rows as $r) {
                $type = (string) ($r->type ?? 'assignment');
                $label = $type === 'quiz' ? 'Quiz' : 'Assignment';
                $courseName = (string) ($r->course_title ?? $r->course_number ?? '');
                $events[] = [
                    'date' => (string) \Carbon\Carbon::parse((string) $r->due_date)->format('Y-m-d'),
                    'datetime' => (string) $r->due_date,
                    'title' => $label . ': ' . (string) ($r->title ?? ''),
                    'type' => $type,
                    'href' => route('teacher.courses.show', ['course' => (int) $r->course_id]) . '#tasks',
                    'meta' => 'Due ' . \Carbon\Carbon::parse((string) $r->due_date)->format('M d, Y g:i A'),
                ];
            }
        }

        // Exams
        if (
            Schema::hasTable('exams')
            && Schema::hasColumn('exams', 'course_id')
            && Schema::hasColumn('exams', 'exam_date')
            && Schema::hasTable('courses')
            && Schema::hasColumn('courses', 'teacher_id')
        ) {
            $rows = DB::table('exams')
                ->join('courses', 'courses.id', '=', 'exams.course_id')
                ->where('courses.teacher_id', $teacherId)
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
                ->get();

            foreach ($rows as $r) {
                $courseName = (string) ($r->course_title ?? $r->course_number ?? '');
                $events[] = [
                    'date' => (string) \Carbon\Carbon::parse((string) $r->exam_date)->format('Y-m-d'),
                    'datetime' => (string) $r->exam_date,
                    'title' => 'Exam: ' . (string) ($r->title ?? ''),
                    'type' => 'exam',
                    'href' => route('teacher.courses.show', ['course' => (int) $r->course_id]) . '#tasks',
                    'meta' => 'Scheduled ' . \Carbon\Carbon::parse((string) $r->exam_date)->format('M d, Y g:i A'),
                ];
            }
        }

        // Announcements - show created_at items within window
        if (
            Schema::hasTable('announcements')
            && Schema::hasColumn('announcements', 'course_id')
            && Schema::hasColumn('announcements', 'created_at')
        ) {
            try {
                $rows = DB::table('announcements')
                    ->join('courses', 'courses.id', '=', 'announcements.course_id')
                    ->where('courses.teacher_id', $teacherId)
                    ->whereBetween('announcements.created_at', [$windowStart, $windowEnd])
                    ->select([
                        'announcements.id',
                        'announcements.title',
                        'announcements.message',
                        'announcements.created_at',
                        'announcements.course_id',
                        'courses.title as course_title',
                        'courses.course_number as course_number',
                    ])
                    ->limit(50)
                    ->get();

                foreach ($rows as $r) {
                    $events[] = [
                        'date' => (string) \Carbon\Carbon::parse((string) $r->created_at)->format('Y-m-d'),
                        'datetime' => (string) $r->created_at,
                        'title' => 'Announcement: ' . (string) ($r->title ?? ''),
                        'type' => 'announcement',
                        'href' => route('teacher.courses.show', ['course' => (int) $r->course_id]) . '#discussion',
                        'meta' => 'Posted ' . \Carbon\Carbon::parse((string) $r->created_at)->format('M d, Y'),
                    ];
                }
            } catch (\Throwable $e) {
                $this->logIf('teacher calendar announcements failed', $e);
            }
        }

        $agenda = $this->buildAgenda($events);

        return view('teacher.calendar', [
            'agenda' => $agenda,
            'windowDays' => $windowDays,
            'windowStart' => $windowStart->format('M d, Y'),
            'windowEnd' => $windowEnd->format('M d, Y'),
        ]);
    }
}

