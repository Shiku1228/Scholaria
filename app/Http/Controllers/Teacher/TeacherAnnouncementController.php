<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TeacherAnnouncementController extends Controller
{
    public function overview(Request $request): View
    {
        $teacherId = (int) $request->user()->id;

        $announcements = collect();

        try {
            if (Schema::hasTable('announcements') && Schema::hasTable('courses')) {
                $announcements = Announcement::query()
                    ->whereHas('course', fn ($q) => $q->where('teacher_id', $teacherId))
                    ->with(['course'])
                    ->orderByDesc('id')
                    ->paginate(20);
            }
        } catch (\Throwable) {
            $announcements = collect();
        }

        return view('teacher.announcements.overview', [
            'announcements' => $announcements,
        ]);
    }

    public function index(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        $announcements = collect();

        try {
            if (Schema::hasTable('announcements')) {
                $announcements = $course->announcements()->orderByDesc('id')->paginate(15);
            }
        } catch (\Throwable) {
            $announcements = collect();
        }

        return view('teacher.announcements.index', [
            'course' => $course,
            'announcements' => $announcements,
        ]);
    }

    public function create(Request $request, Course $course): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        return view('teacher.announcements.create', [
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
            'message' => ['required', 'string'],
        ]);

        $announcement = Announcement::create([
            'course_id' => (int) $course->id,
            'teacher_id' => (int) $request->user()->id,
            'title' => (string) $validated['title'],
            'message' => (string) $validated['message'],
        ]);

        return redirect()->route('teacher.announcements.show', [$course, $announcement])->with('success', 'Announcement posted.');
    }

    public function show(Request $request, Course $course, Announcement $announcement): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $announcement->course_id !== (int) $course->id) {
            abort(404);
        }

        return view('teacher.announcements.show', [
            'course' => $course,
            'announcement' => $announcement,
        ]);
    }

    public function edit(Request $request, Course $course, Announcement $announcement): View
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $announcement->course_id !== (int) $course->id) {
            abort(404);
        }

        return view('teacher.announcements.edit', [
            'course' => $course,
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Course $course, Announcement $announcement)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $announcement->course_id !== (int) $course->id) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $announcement->update([
            'title' => (string) $validated['title'],
            'message' => (string) $validated['message'],
        ]);

        return redirect()->route('teacher.announcements.show', [$course, $announcement])->with('success', 'Announcement updated.');
    }

    public function destroy(Request $request, Course $course, Announcement $announcement)
    {
        if ((int) $course->teacher_id !== (int) $request->user()->id) {
            abort(403);
        }

        if ((int) $announcement->course_id !== (int) $course->id) {
            abort(404);
        }

        $announcement->delete();

        return redirect()->route('teacher.announcements.index', $course)->with('success', 'Announcement deleted.');
    }
}
