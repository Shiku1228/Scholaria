<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherCourseController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $courses = collect();

        try {
            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $courseNameCol = null;
                foreach (['course_number', 'title', 'name', 'course_name'] as $c) {
                    if (Schema::hasColumn('courses', $c)) {
                        $courseNameCol = $c;
                        break;
                    }
                }

                $select = ['id'];
                if ($courseNameCol) {
                    $select[] = $courseNameCol;
                }
                if (Schema::hasColumn('courses', 'semester')) {
                    $select[] = 'semester';
                }
                if (Schema::hasColumn('courses', 'school_year')) {
                    $select[] = 'school_year';
                }

                $courses = DB::table('courses')
                    ->where('teacher_id', $teacherId)
                    ->select($select)
                    ->orderByDesc('id')
                    ->get();
            }
        } catch (\Throwable) {
            $courses = collect();
        }

        return view('teacher.courses.index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $students = collect();
        $assignments = collect();
        $announcements = collect();

        try {
            if (Schema::hasTable('enrollments')) {
                $students = $course->enrollments()
                    ->with(['student'])
                    ->orderByDesc('enrolled_at')
                    ->limit(200)
                    ->get();
            }

            if (Schema::hasTable('assignments')) {
                $assignments = $course->assignments()
                    ->withCount('submissions')
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();
            }

            if (Schema::hasTable('announcements')) {
                $announcements = $course->announcements()
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get();
            }
        } catch (\Throwable) {
            $students = collect();
            $assignments = collect();
            $announcements = collect();
        }

        return view('teacher.courses.show', [
            'course' => $course,
            'students' => $students,
            'assignments' => $assignments,
            'announcements' => $announcements,
        ]);
    }
}
