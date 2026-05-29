<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentAttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $filterCourse   = $request->input('course_id', '');
        $filterStatus   = $request->input('status', '');
        $filterDateFrom = $request->input('date_from', '');
        $filterDateTo   = $request->input('date_to', '');
        $search         = $request->input('search', '');

        $rows    = collect();
        $courses = collect();
        $summary = [
            'total'      => 0,
            'present'    => 0,
            'absent'     => 0,
            'excused'    => 0,
            'percentage' => 0,
        ];

        // Courses dropdown: from enrollments, not attendances
        if (Schema::hasTable('enrollments') && Schema::hasTable('courses')) {
            $courses = DB::table('enrollments')
                ->join('courses', 'enrollments.course_id', '=', 'courses.id')
                ->where('enrollments.student_id', $userId)
                ->where('enrollments.status', 'active')
                ->orderBy('courses.title')
                ->get(['courses.id', 'courses.title', 'courses.course_number', 'courses.course_code']);
        }

        if (Schema::hasTable('attendances') && Schema::hasTable('courses')) {
            // Summary respects the course filter
            $summaryQuery = DB::table('attendances')->where('student_id', $userId);
            if ($filterCourse !== '') {
                $summaryQuery->where('course_id', $filterCourse);
            }
            $summaryRecords = $summaryQuery->get(['status']);

            $summary['total']   = $summaryRecords->count();
            $summary['present'] = $summaryRecords->where('status', 'present')->count();
            $summary['absent']  = $summaryRecords->where('status', 'absent')->count();
            $summary['excused'] = $summaryRecords->where('status', 'excused')->count();

            if ($summary['total'] > 0) {
                $attended = $summary['present'] + $summary['excused'];
                $summary['percentage'] = round(($attended / $summary['total']) * 100, 1);
            }

            $hasDaysPattern = Schema::hasColumn('courses', 'days_pattern');
            $hasStartTime   = Schema::hasColumn('courses', 'start_time');
            $hasEndTime     = Schema::hasColumn('courses', 'end_time');

            $courseSelectCols = [
                'courses.title as course_name',
                'courses.course_number',
                'courses.course_code',
            ];
            if ($hasDaysPattern) $courseSelectCols[] = 'courses.days_pattern';
            if ($hasStartTime)   $courseSelectCols[] = 'courses.start_time';
            if ($hasEndTime)     $courseSelectCols[] = 'courses.end_time';

            $query = DB::table('attendances')
                ->join('courses', 'attendances.course_id', '=', 'courses.id')
                ->join('users as teachers', 'attendances.teacher_id', '=', 'teachers.id')
                ->where('attendances.student_id', $userId)
                ->select(array_merge(
                    [
                        'attendances.id',
                        'attendances.date',
                        'attendances.status',
                        'attendances.remarks',
                        'teachers.name as teacher_name',
                    ],
                    $courseSelectCols
                ));

            if ($filterCourse !== '') {
                $query->where('attendances.course_id', $filterCourse);
            }

            if ($filterStatus !== '') {
                $query->where('attendances.status', $filterStatus);
            }

            if ($filterDateFrom) {
                $query->where('attendances.date', '>=', $filterDateFrom);
            }

            if ($filterDateTo) {
                $query->where('attendances.date', '<=', $filterDateTo);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('courses.title', 'like', '%' . $search . '%')
                      ->orWhere('teachers.name', 'like', '%' . $search . '%')
                      ->orWhere('courses.course_number', 'like', '%' . $search . '%')
                      ->orWhere('courses.course_code', 'like', '%' . $search . '%');
                });
            }

            $rows = $query->orderByDesc('attendances.date')->get();
        }

        return view('student.attendance.index', compact(
            'rows',
            'summary',
            'courses',
            'filterCourse',
            'filterStatus',
            'filterDateFrom',
            'filterDateTo',
            'search'
        ));
    }
}
