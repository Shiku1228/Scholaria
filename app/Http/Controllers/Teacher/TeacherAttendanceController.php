<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Notifications\AttendanceMarkedNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = $request->user()->id;

        $courses = collect();
        if (Schema::hasTable('courses')) {
            $courses = DB::table('courses')
                ->where('teacher_id', $teacherId)
                ->orderBy('title')
                ->get(['id', 'title', 'course_number', 'course_code', 'days_pattern', 'start_time', 'end_time', 'semester', 'school_year']);
        }

        $selectedCourseId = $request->input('course_id', '');
        $selectedDate     = $request->input('date', now()->toDateString());
        $selectedStatus   = $request->input('status', '');
        $search           = $request->input('search', '');

        $allStudents    = collect();
        $students       = collect();
        $attendanceMap  = collect();
        $selectedCourse = null;
        $summary        = [
            'total'      => 0,
            'present'    => 0,
            'absent'     => 0,
            'excused'    => 0,
            'notMarked'  => 0,
            'markedPct'  => 0,
        ];

        if ($selectedCourseId && Schema::hasTable('courses')) {
            $selectedCourse = DB::table('courses')
                ->where('id', $selectedCourseId)
                ->where('teacher_id', $teacherId)
                ->first();

            if ($selectedCourse && Schema::hasTable('enrollments')) {
                $hasStudentNumber = Schema::hasColumn('users', 'student_number');

                $selectCols = ['users.id', 'users.name', 'users.email'];
                if ($hasStudentNumber) {
                    $selectCols[] = 'users.student_number';
                }

                $allStudents = DB::table('enrollments')
                    ->join('users', 'enrollments.student_id', '=', 'users.id')
                    ->where('enrollments.course_id', $selectedCourseId)
                    ->where('enrollments.status', 'active')
                    ->select($selectCols)
                    ->orderBy('users.name')
                    ->get();

                if (Schema::hasTable('attendances') && $allStudents->isNotEmpty()) {
                    $records = DB::table('attendances')
                        ->where('course_id', $selectedCourseId)
                        ->where('date', $selectedDate)
                        ->get(['id', 'student_id', 'status', 'remarks']);

                    foreach ($records as $record) {
                        $attendanceMap[$record->student_id] = $record;
                    }
                }

                $summary['total']     = $allStudents->count();
                $summary['present']   = collect($attendanceMap)->where('status', 'present')->count();
                $summary['absent']    = collect($attendanceMap)->where('status', 'absent')->count();
                $summary['excused']   = collect($attendanceMap)->where('status', 'excused')->count();
                $summary['notMarked'] = $summary['total'] - ($summary['present'] + $summary['absent'] + $summary['excused']);
                $marked               = $summary['present'] + $summary['absent'] + $summary['excused'];
                $summary['markedPct'] = $summary['total'] > 0
                    ? (int) round(($marked / $summary['total']) * 100)
                    : 0;

                // Apply search filter in PHP (collection is small)
                $students = $allStudents;

                if ($search) {
                    $lower = strtolower($search);
                    $students = $students->filter(function ($s) use ($lower, $hasStudentNumber) {
                        return str_contains(strtolower($s->name ?? ''), $lower)
                            || str_contains(strtolower($s->email ?? ''), $lower)
                            || ($hasStudentNumber && str_contains(strtolower($s->student_number ?? ''), $lower));
                    })->values();
                }

                // ConvertEmptyStringsToNull middleware turns '' into null, so guard with in_array
                if (in_array($selectedStatus, ['present', 'absent', 'excused', 'not_marked'], true)) {
                    if ($selectedStatus === 'not_marked') {
                        $students = $students->filter(function ($s) use ($attendanceMap) {
                            return ! isset($attendanceMap[$s->id]);
                        })->values();
                    } else {
                        $students = $students->filter(function ($s) use ($attendanceMap, $selectedStatus) {
                            $att = $attendanceMap[$s->id] ?? null;
                            return $att && $att->status === $selectedStatus;
                        })->values();
                    }
                }
            }
        }

        return view('teacher.attendance.index', compact(
            'courses',
            'allStudents',
            'students',
            'attendanceMap',
            'selectedCourseId',
            'selectedDate',
            'selectedStatus',
            'search',
            'selectedCourse',
            'summary'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacherId = $request->user()->id;

        $validated = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'course_id'  => ['required', 'exists:courses,id'],
            'date'       => ['required', 'date'],
            'status'     => ['required', 'in:present,absent,excused'],
            'remarks'    => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->teacherOwnsCourse((int) $validated['course_id'], $teacherId)) {
            return back()->with('error', 'You do not have permission to take attendance for this course.');
        }

        $enrolled = DB::table('enrollments')
            ->where('course_id', $validated['course_id'])
            ->where('student_id', $validated['student_id'])
            ->where('status', 'active')
            ->exists();

        if (! $enrolled) {
            return back()->with('error', 'Student is not actively enrolled in this course.');
        }

        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $validated['student_id'],
                'course_id'  => $validated['course_id'],
                'date'       => $validated['date'],
            ],
            [
                'teacher_id' => $teacherId,
                'status'     => $validated['status'],
                'remarks'    => $validated['remarks'] ?? null,
            ]
        );

        $notified = false;
        if ($attendance->wasRecentlyCreated || $attendance->wasChanged('status')) {
            $course = DB::table('courses')
                ->where('id', $validated['course_id'])
                ->first(['course_number', 'course_code', 'title']);

            if ($course) {
                $courseLabel = $course->course_number;
                if (!empty($course->course_code)) {
                    $courseLabel .= ' — ' . $course->course_code;
                }
                $courseLabel .= ' — ' . $course->title;

                $student = User::find($validated['student_id']);
                $student?->notify(new AttendanceMarkedNotification(
                    courseId:    (int) $validated['course_id'],
                    courseLabel: $courseLabel,
                    date:        Carbon::parse($validated['date'])->format('M j, Y'),
                    status:      ucfirst($validated['status']),
                    remarks:     $validated['remarks'] ?? null,
                ));
                $notified = true;
            }
        }

        $message = $notified ? 'Attendance saved and student notified.' : 'Attendance saved.';

        return redirect()->route('teacher.attendance.index', [
            'course_id' => $validated['course_id'],
            'date'      => $validated['date'],
            'status'    => $request->input('filter_status', ''),
            'search'    => $request->input('filter_search', ''),
        ])->with('success', $message);
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $teacherId = $request->user()->id;

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'date'      => ['required', 'date'],
            'status'    => ['required', 'in:present,absent,excused'],
        ]);

        if (! $this->teacherOwnsCourse((int) $validated['course_id'], $teacherId)) {
            return back()->with('error', 'You do not have permission to take attendance for this course.');
        }

        $studentIds = DB::table('enrollments')
            ->where('course_id', $validated['course_id'])
            ->where('status', 'active')
            ->pluck('student_id');

        $course = DB::table('courses')
            ->where('id', $validated['course_id'])
            ->first(['course_number', 'course_code', 'title']);

        $courseLabel = '';
        if ($course) {
            $courseLabel = $course->course_number;
            if (!empty($course->course_code)) {
                $courseLabel .= ' — ' . $course->course_code;
            }
            $courseLabel .= ' — ' . $course->title;
        }

        $dateLabel   = Carbon::parse($validated['date'])->format('M j, Y');
        $statusLabel = ucfirst($validated['status']);

        // Load all enrolled students at once to avoid N+1 on notify()
        $students = User::whereIn('id', $studentIds->all())->get()->keyBy('id');

        $notifiedCount = 0;

        foreach ($studentIds as $studentId) {
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'course_id'  => $validated['course_id'],
                    'date'       => $validated['date'],
                ],
                [
                    'teacher_id' => $teacherId,
                    'status'     => $validated['status'],
                    'remarks'    => null,
                ]
            );

            if ($course && ($attendance->wasRecentlyCreated || $attendance->wasChanged('status'))) {
                $students->get($studentId)?->notify(new AttendanceMarkedNotification(
                    courseId:    (int) $validated['course_id'],
                    courseLabel: $courseLabel,
                    date:        $dateLabel,
                    status:      $statusLabel,
                    remarks:     null,
                ));
                $notifiedCount++;
            }
        }

        $label = ucfirst($validated['status']);
        $suffix = $notifiedCount > 0
            ? " {$notifiedCount} student(s) notified."
            : '';

        return redirect()->route('teacher.attendance.index', [
            'course_id' => $validated['course_id'],
            'date'      => $validated['date'],
        ])->with('success', "All {$studentIds->count()} students marked as {$label}.{$suffix}");
    }

    private function teacherOwnsCourse(int $courseId, int $teacherId): bool
    {
        return DB::table('courses')
            ->where('id', $courseId)
            ->where('teacher_id', $teacherId)
            ->exists();
    }
}
