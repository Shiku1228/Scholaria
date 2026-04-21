<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseDiscussion;
use App\Models\CourseResource;
use App\Models\User;
use App\Notifications\CourseEventNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TeacherCourseController extends Controller
{
    public function index(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $courses = collect();

        try {
            if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'teacher_id')) {
                $select = ['id'];
                foreach (['course_number', 'title', 'semester', 'school_year'] as $column) {
                    if (Schema::hasColumn('courses', $column)) {
                        $select[] = $column;
                    }
                }
                if (Schema::hasColumn('courses', 'cover_image')) {
                    $select[] = 'cover_image';
                }

                $baseCourses = DB::table('courses')
                    ->where('teacher_id', $teacherId)
                    ->select($select)
                    ->orderByDesc('id')
                    ->get();

                $courseIds = $baseCourses->pluck('id')->map(fn ($id) => (int) $id)->all();

                $enrolledByCourse = [];
                if (
                    !empty($courseIds)
                    && Schema::hasTable('enrollments')
                    && Schema::hasColumn('enrollments', 'course_id')
                    && Schema::hasColumn('enrollments', 'student_id')
                ) {
                    $enrollmentQuery = DB::table('enrollments')->whereIn('course_id', $courseIds);
                    if (Schema::hasColumn('enrollments', 'status')) {
                        $enrollmentQuery->whereRaw('LOWER(status) = ?', ['active']);
                    }

                    $rows = $enrollmentQuery
                        ->select(['course_id', DB::raw('COUNT(DISTINCT student_id) as enrolled_students')])
                        ->groupBy('course_id')
                        ->get();

                    foreach ($rows as $row) {
                        $enrolledByCourse[(int) $row->course_id] = (int) ($row->enrolled_students ?? 0);
                    }
                }

                $submittedByCourse = [];
                if (
                    !empty($courseIds)
                    && Schema::hasTable('submissions')
                    && Schema::hasTable('assignments')
                    && Schema::hasColumn('submissions', 'assignment_id')
                    && Schema::hasColumn('submissions', 'student_id')
                    && Schema::hasColumn('assignments', 'id')
                    && Schema::hasColumn('assignments', 'course_id')
                ) {
                    $rows = DB::table('submissions')
                        ->join('assignments', 'assignments.id', '=', 'submissions.assignment_id')
                        ->whereIn('assignments.course_id', $courseIds)
                        ->select(['assignments.course_id as course_id', DB::raw('COUNT(DISTINCT submissions.student_id) as submitted_students')])
                        ->groupBy('assignments.course_id')
                        ->get();

                    foreach ($rows as $row) {
                        $submittedByCourse[(int) $row->course_id] = (int) ($row->submitted_students ?? 0);
                    }
                }

                $courses = $baseCourses->map(function ($course) use ($enrolledByCourse, $submittedByCourse) {
                    $courseId = (int) ($course->id ?? 0);
                    $enrolled = (int) ($enrolledByCourse[$courseId] ?? 0);
                    $submitted = (int) ($submittedByCourse[$courseId] ?? 0);
                    $completion = $enrolled > 0 ? (int) round(($submitted / $enrolled) * 100) : 0;

                    return (object) [
                        'id' => $courseId,
                        'course_number' => (string) ($course->course_number ?? ''),
                        'title' => (string) ($course->title ?? ''),
                        'semester' => (string) ($course->semester ?? ''),
                        'school_year' => (string) ($course->school_year ?? ''),
                        'cover_image' => (string) ($course->cover_image ?? ''),
                        'enrolled_students' => $enrolled,
                        'submitted_students' => $submitted,
                        'completion_rate' => max(0, min(100, $completion)),
                    ];
                })->values();
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
        $resources = collect();
        $discussions = collect();

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

            if (Schema::hasTable('course_resources')) {
                $resources = CourseResource::query()
                    ->where('course_id', (int) $course->id)
                    ->latest('id')
                    ->limit(200)
                    ->get();
            }

            if (Schema::hasTable('course_discussions')) {
                $discussions = CourseDiscussion::query()
                    ->with([
                        'user:id,name',
                        'replies.user:id,name',
                    ])
                    ->where('course_id', (int) $course->id)
                    ->whereNull('parent_id')
                    ->latest('created_at')
                    ->limit(200)
                    ->get();
            }
        } catch (\Throwable) {
            $students = collect();
            $assignments = collect();
            $announcements = collect();
            $resources = collect();
            $discussions = collect();
        }

        return view('teacher.courses.show', [
            'course' => $course,
            'students' => $students,
            'assignments' => $assignments,
            'announcements' => $announcements,
            'resources' => $resources,
            'discussions' => $discussions,
            'canEditCover' => Schema::hasColumn('courses', 'cover_image'),
            'canEditOverview' => Schema::hasColumn('courses', 'overview'),
        ]);
    }

    public function updateCover(Request $request, Course $course): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if (!Schema::hasColumn('courses', 'cover_image')) {
            return redirect()
                ->route('teacher.courses.show', $course)
                ->with('error', 'Course cover field is not available yet. Run migrations first.');
        }

        $validated = $request->validate([
            'cover_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $newPath = $request->file('cover_image')->store('course-covers', 'public');

        if (!empty($course->cover_image) && Storage::disk('public')->exists($course->cover_image)) {
            Storage::disk('public')->delete($course->cover_image);
        }

        $course->cover_image = $newPath;
        $course->save();

        return redirect()
            ->back()
            ->with('success', 'Course card background updated.');
    }

    public function updateOverview(Request $request, Course $course): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if (!Schema::hasColumn('courses', 'overview')) {
            return redirect()->back()->with('error', 'Course overview field is not available yet. Run migrations first.');
        }

        $validated = $request->validate([
            'overview' => ['nullable', 'string', 'max:10000'],
        ]);

        $course->overview = trim((string) ($validated['overview'] ?? '')) ?: null;
        $course->save();

        return redirect()->back()->with('success', 'Course overview updated.');
    }

    public function uploadResource(Request $request, Course $course): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if (!Schema::hasTable('course_resources')) {
            return redirect()->back()->with('error', 'Course resources table is not available yet. Run migrations first.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'resource_file' => ['required', 'file', 'mimes:pdf,docx', 'max:10240'],
        ]);

        $file = $request->file('resource_file');
        $path = $file->store('course-resources', 'public');

        CourseResource::query()->create([
            'course_id' => (int) $course->id,
            'uploaded_by' => (int) $request->user()->id,
            'title' => (string) $validated['title'],
            'file_path' => $path,
            'file_name' => (string) $file->getClientOriginalName(),
            'mime_type' => (string) $file->getClientMimeType(),
            'file_size' => (int) $file->getSize(),
        ]);

        $studentIds = $this->enrolledStudentIds((int) $course->id);
        if (!empty($studentIds)) {
            $students = User::query()->whereIn('id', $studentIds)->get();
            foreach ($students as $student) {
                $student->notify(new CourseEventNotification(
                    'New Resource Added',
                    'A new resource was added to ' . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.',
                    route('student.courses.show', $course) . '#resources'
                ));
            }
        }

        return redirect()->back()->with('success', 'Resource uploaded successfully.');
    }

    public function storeDiscussion(Request $request, Course $course): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if (!Schema::hasTable('course_discussions')) {
            return redirect()->back()->with('error', 'Course discussion table is not available yet. Run migrations first.');
        }

        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:course_discussions,id'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $parentId = (int) ($validated['parent_id'] ?? 0);
        if ($parentId > 0) {
            $parent = CourseDiscussion::query()
                ->where('id', $parentId)
                ->where('course_id', (int) $course->id)
                ->first();
            if (!$parent) {
                return redirect()->back()->with('error', 'Invalid discussion reply target.');
            }
        }

        CourseDiscussion::query()->create([
            'course_id' => (int) $course->id,
            'user_id' => (int) $request->user()->id,
            'parent_id' => $parentId > 0 ? $parentId : null,
            'content' => (string) $validated['content'],
        ]);

        $request->user()->notify(new CourseEventNotification(
            'Discussion Posted',
            'You posted a discussion update in ' . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.',
            route('teacher.courses.show', $course) . '#discussion'
        ));

        $studentIds = $this->enrolledStudentIds((int) $course->id);
        if (!empty($studentIds)) {
            $students = User::query()->whereIn('id', $studentIds)->get();
            foreach ($students as $student) {
                $student->notify(new CourseEventNotification(
                    'New Discussion Post',
                    'Teacher posted a new discussion update in ' . ((string) ($course->title ?: $course->course_number ?: 'your course')) . '.',
                    route('student.courses.show', $course) . '#discussion'
                ));
            }
        }

        return redirect()->to(route('teacher.courses.show', $course) . '#discussion')->with('success', 'Discussion comment posted.');
    }

    public function updateDiscussion(Request $request, Course $course, CourseDiscussion $discussion): RedirectResponse
    {
        $teacherId = (int) $request->user()->id;
        if ((int) $course->teacher_id !== $teacherId) {
            abort(403);
        }
        if ((int) $discussion->course_id !== (int) $course->id) {
            abort(404);
        }
        if ((int) $discussion->user_id !== $teacherId) {
            return redirect()->to(route('teacher.courses.show', $course) . '#discussion')
                ->with('error', 'You can only edit your own discussion posts.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $discussion->content = (string) $validated['content'];
        $discussion->save();

        return redirect()->to(route('teacher.courses.show', $course) . '#discussion')->with('success', 'Discussion updated.');
    }

    public function destroyDiscussion(Request $request, Course $course, CourseDiscussion $discussion): RedirectResponse
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }
        if ((int) $discussion->course_id !== (int) $course->id) {
            abort(404);
        }

        CourseDiscussion::query()
            ->where('course_id', (int) $course->id)
            ->where('parent_id', (int) $discussion->id)
            ->delete();

        $discussion->delete();

        return redirect()->to(route('teacher.courses.show', $course) . '#discussion')->with('success', 'Discussion deleted.');
    }

    private function enrolledStudentIds(int $courseId): array
    {
        if (!Schema::hasTable('enrollments') || !Schema::hasColumn('enrollments', 'course_id') || !Schema::hasColumn('enrollments', 'student_id')) {
            return [];
        }

        $query = DB::table('enrollments')->where('course_id', $courseId);
        if (Schema::hasColumn('enrollments', 'status')) {
            $query->whereRaw('LOWER(status) = ?', ['active']);
        }

        return $query->pluck('student_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }
}
