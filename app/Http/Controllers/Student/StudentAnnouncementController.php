<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class StudentAnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $studentId = (int) $request->user()->id;
        $courseId = (int) $request->query('course_id', 0);

        $announcements = [];

        try {
            if (
                !Schema::hasTable('enrollments')
                || !Schema::hasTable('announcements')
                || !Schema::hasColumn('enrollments', 'student_id')
                || !Schema::hasColumn('enrollments', 'course_id')
                || !Schema::hasColumn('announcements', 'course_id')
            ) {
                return view('student.announcements.index', [
                    'announcements' => [],
                    'filters' => ['course_id' => $courseId],
                ]);
            }

            $enrollmentQuery = DB::table('enrollments')->where('student_id', $studentId);
            if (Schema::hasColumn('enrollments', 'status')) {
                $enrollmentQuery->whereRaw('LOWER(status) = ?', ['active']);
            }

            $enrolledCourseIds = $enrollmentQuery
                ->pluck('course_id')
                ->map(fn ($v) => (int) $v)
                ->filter()
                ->values()
                ->all();

            if (empty($enrolledCourseIds)) {
                return view('student.announcements.index', [
                    'announcements' => [],
                    'filters' => ['course_id' => $courseId],
                ]);
            }

            if ($courseId > 0 && !in_array($courseId, $enrolledCourseIds, true)) {
                abort(403);
            }

            $query = DB::table('announcements');
            $targetCourseIds = $courseId > 0 ? [$courseId] : $enrolledCourseIds;
            $query->whereIn('announcements.course_id', $targetCourseIds);

            $hasCourses = Schema::hasTable('courses') && Schema::hasColumn('courses', 'id');
            $courseNameCol = null;
            if ($hasCourses) {
                foreach (['title', 'name', 'course_name'] as $candidate) {
                    if (Schema::hasColumn('courses', $candidate)) {
                        $courseNameCol = $candidate;
                        break;
                    }
                }
            }

            if ($hasCourses && $courseNameCol) {
                $query->join('courses', 'courses.id', '=', 'announcements.course_id');
            }

            if (Schema::hasColumn('announcements', 'created_at')) {
                $query->orderByDesc('announcements.created_at');
            } else {
                $query->orderByDesc('announcements.id');
            }

            $select = [
                'announcements.id as announcement_id',
                Schema::hasColumn('announcements', 'title') ? 'announcements.title as title' : DB::raw("'Announcement' as title"),
                Schema::hasColumn('announcements', 'content') ? 'announcements.content as content' : DB::raw("'' as content"),
                Schema::hasColumn('announcements', 'created_at') ? 'announcements.created_at as created_at' : DB::raw("'' as created_at"),
                'announcements.course_id as course_id',
            ];

            if ($hasCourses && $courseNameCol) {
                $select[] = 'courses.' . $courseNameCol . ' as course_name';
            } else {
                $select[] = DB::raw("'Course' as course_name");
            }

            $announcements = $query
                ->select($select)
                ->limit(200)
                ->get()
                ->map(fn ($r) => [
                    'announcement_id' => (int) ($r->announcement_id ?? 0),
                    'title' => (string) ($r->title ?? ''),
                    'content' => (string) ($r->content ?? ''),
                    'created_at' => (string) ($r->created_at ?? ''),
                    'course_id' => (int) ($r->course_id ?? 0),
                    'course_name' => (string) ($r->course_name ?? ''),
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
        }

        return view('student.announcements.index', [
            'announcements' => $announcements,
            'filters' => ['course_id' => $courseId],
        ]);
    }
}

