<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherAssignmentController extends Controller
{
    public function overview(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $assignments = collect();

        try {
            if (Schema::hasTable('assignments') && Schema::hasTable('courses')) {
                $assignments = Assignment::query()
                    ->whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->with(['course'])
                    ->withCount('submissions')
                    ->orderByDesc('id')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $assignments = collect();
        }

        return view('teacher.assignments.overview', [
            'assignments' => $assignments,
        ]);
    }

    public function index(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $assignments = collect();

        try {
            if (Schema::hasTable('assignments')) {
                $assignments = $course->assignments()->withCount('submissions')->orderByDesc('id')->paginate(15);
            }
        } catch (\Throwable) {
            $assignments = collect();
        }

        return view('teacher.assignments.index', [
            'course' => $course,
            'assignments' => $assignments,
        ]);
    }

    public function create(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.assignments.create', [
            'course' => $course,
        ]);
    }

    public function store(Request $request, Course $course)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $assignment = Assignment::create([
            'course_id' => (int) $course->id,
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'max_score' => (int) ($validated['max_score'] ?? 100),
        ]);

        return redirect()->route('teacher.assignments.show', [$course, $assignment])->with('success', 'Assignment created.');
    }

    public function show(Request $request, Course $course, Assignment $assignment): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        $submissions = collect();

        try {
            if (Schema::hasTable('submissions')) {
                $submissions = Submission::query()
                    ->where('assignment_id', $assignment->id)
                    ->with('student')
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('id')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $submissions = collect();
        }

        return view('teacher.assignments.show', [
            'course' => $course,
            'assignment' => $assignment,
            'submissions' => $submissions,
        ]);
    }

    public function edit(Request $request, Course $course, Assignment $assignment): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        return view('teacher.assignments.edit', [
            'course' => $course,
            'assignment' => $assignment,
        ]);
    }

    public function update(Request $request, Course $course, Assignment $assignment)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'max_score' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $assignment->update([
            'title' => (string) $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'max_score' => (int) ($validated['max_score'] ?? $assignment->max_score ?? 100),
        ]);

        return redirect()->route('teacher.assignments.show', [$course, $assignment])->with('success', 'Assignment updated.');
    }

    public function destroy(Request $request, Course $course, Assignment $assignment)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $assignment->course_id !== (int) $course->id) {
            abort(404);
        }

        $assignment->delete();

        return redirect()->route('teacher.assignments.index', $course)->with('success', 'Assignment deleted.');
    }
}
