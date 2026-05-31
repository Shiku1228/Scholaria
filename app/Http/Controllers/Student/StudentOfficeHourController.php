<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\OfficeHour;
use App\Models\OfficeHourQuestion;

class StudentOfficeHourController extends Controller
{
    public function index(Course $course)
    {
        $officeHours = $course->officeHours()->with('questions.student')->orderBy('start_time', 'asc')->get();
        return view('student.office_hours.index', compact('course', 'officeHours'));
    }

    public function storeQuestion(Request $request, Course $course, OfficeHour $officeHour)
    {
        $request->validate([
            'question' => 'required|string',
        ]);

        $officeHour->questions()->create([
            'student_id' => auth()->id(),
            'question' => $request->question,
        ]);

        return back()->with('success', 'Question submitted successfully.');
    }
}
