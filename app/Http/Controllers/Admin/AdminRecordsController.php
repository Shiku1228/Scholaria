<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRecordsController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = $request->query('tab', 'students');
        $search = trim((string) $request->query('q', ''));
        $yearLevel = $request->query('year_level', '');
        $program = $request->query('program', '');
        $college = $request->query('college', '');

        // Get distinct filter values for dropdowns
        $yearLevels = Student::distinct()->whereNotNull('year_level')->pluck('year_level')->sort()->values();
        $programs = Student::distinct()->whereNotNull('program')->pluck('program')->sort()->values();
        $colleges = Student::distinct()->whereNotNull('college')->pluck('college')->sort()->values();

        // Get Students
        $studentsQuery = Student::with('user')
            ->withCount(['enrollments', 'submissions']);

        if ($search !== '' && $activeTab === 'students') {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('student_number', 'like', '%' . $search . '%')
                  ->orWhere('program', 'like', '%' . $search . '%')
                  ->orWhere('college', 'like', '%' . $search . '%');
            });
        }

        // Apply filters
        if ($yearLevel !== '') {
            $studentsQuery->where('year_level', $yearLevel);
        }
        if ($program !== '') {
            $studentsQuery->where('program', $program);
        }
        if ($college !== '') {
            $studentsQuery->where('college', $college);
        }

        $students = $studentsQuery->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'students_page')
            ->withQueryString();

        // Get Teachers
        $teachersQuery = Teacher::with('user')
            ->withCount(['courses', 'announcements']);

        if ($search !== '' && $activeTab === 'teachers') {
            $teachersQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                  ->orWhere('last_name', 'like', '%' . $search . '%')
                  ->orWhere('employee_id', 'like', '%' . $search . '%')
                  ->orWhere('specialization', 'like', '%' . $search . '%')
                  ->orWhere('college', 'like', '%' . $search . '%');
            });
        }

        $teachers = $teachersQuery->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'teachers_page')
            ->withQueryString();

        return view('admin.records.index', [
            'students' => $students,
            'teachers' => $teachers,
            'activeTab' => $activeTab,
            'search' => $search,
            'yearLevel' => $yearLevel,
            'program' => $program,
            'college' => $college,
            'yearLevels' => $yearLevels,
            'programs' => $programs,
            'colleges' => $colleges,
            'title' => 'Records',
        ]);
    }
}
