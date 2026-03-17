<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminCourseController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $semester = trim((string) $request->query('semester', ''));

        $query = Course::query()->with('teacher')->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('course_number', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%');
            });
        }

        if (in_array($semester, ['first', 'second', 'summer'], true)) {
            $query->where('semester', $semester);
        }

        $courses = $query->paginate(15)->withQueryString();

        return view('admin.courses.index', [
            'courses' => $courses,
            'filters' => [
                'search' => $search,
                'semester' => $semester,
            ],
        ]);
    }

    public function create(): View
    {
        $teachers = $this->getTeacherOptions();

        return view('admin.courses.create', [
            'teachers' => $teachers,
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $validated = $request->validated();

        $course = Course::create([
            'course_number' => $validated['course_number'],
            'title' => $validated['course_title'],
            'description' => $validated['course_description'] ?? null,
            'semester' => $validated['semester'],
            'school_year' => $validated['school_year'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['class_time_start'] ?? null,
            'end_time' => $validated['class_time_end'] ?? null,
            'days_pattern' => isset($validated['class_days']) ? implode(',', $validated['class_days']) : null,
            'teacher_id' => $validated['teacher_id'],
        ]);

        return redirect()->route('admin.courses.edit', $course)->with('success', 'Course created.');
    }

    public function edit(Course $course): View
    {
        $teachers = $this->getTeacherOptions();

        return view('admin.courses.edit', [
            'course' => $course,
            'teachers' => $teachers,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $validated = $request->validated();

        $course->update([
            'course_number' => $validated['course_number'],
            'title' => $validated['course_title'],
            'description' => $validated['course_description'] ?? null,
            'semester' => $validated['semester'],
            'school_year' => $validated['school_year'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'start_time' => $validated['class_time_start'] ?? null,
            'end_time' => $validated['class_time_end'] ?? null,
            'days_pattern' => isset($validated['class_days']) ? implode(',', $validated['class_days']) : null,
            'teacher_id' => $validated['teacher_id'],
        ]);

        return redirect()->route('admin.courses.edit', $course)->with('success', 'Course updated.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Course deleted.');
    }

    public function checkNumber(Request $request): JsonResponse
    {
        $courseNumber = strtoupper(trim((string) $request->query('course_number', '')));
        $ignoreId = (int) $request->query('ignore_id', 0);

        if ($courseNumber === '') {
            return response()->json(['exists' => false]);
        }

        $query = Course::query()->where('course_number', $courseNumber);
        if ($ignoreId > 0) {
            $query->where('id', '!=', $ignoreId);
        }

        return response()->json(['exists' => $query->exists()]);
    }

    private function getTeacherOptions()
    {
        return User::query()
            ->where(function ($query) {
                $query->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'Teacher');
                });

                if (Schema::hasColumn('users', 'role')) {
                    $query->orWhere('role', 'teacher');
                }
            })
            ->orderBy('name')
            ->get();
    }
}
