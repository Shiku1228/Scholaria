<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\OfficeHour;
use App\Models\OfficeHourQuestion;

class TeacherOfficeHourController extends Controller
{
    public function index(Course $course)
    {
        $officeHours = $course->officeHours()->with('questions.student')->latest()->get();
        return view('teacher.office_hours.index', compact('course', 'officeHours'));
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $course->officeHours()->create([
            'title' => $request->title,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        return back()->with('success', 'Office hour created successfully.');
    }

    public function answer(Request $request, Course $course, OfficeHourQuestion $question)
    {
        $request->validate([
            'answer' => 'required|string',
        ]);

        $question->update([
            'answer' => $request->answer,
        ]);

        return back()->with('success', 'Answer posted successfully.');
    }
}
