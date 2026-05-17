<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class AdminProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:programs.view')->only(['index', 'show']);
        $this->middleware('permission:programs.create')->only(['create', 'store']);
        $this->middleware('permission:programs.edit')->only(['edit', 'update']);
        $this->middleware('permission:programs.delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $collegeId = (int) $request->query('college_id', 0);

        $query = Program::query()
            ->with('college')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where('name', 'like', $search . '%');
        }

        if ($collegeId > 0) {
            $query->where('college_id', $collegeId);
        }

        $programs = $query->paginate(15)->withQueryString();
        $colleges = College::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.programs.index', [
            'programs' => $programs,
            'colleges' => $colleges,
            'filters' => [
                'q' => $search,
                'college_id' => $collegeId,
            ],
        ]);
    }

    public function create(): View
    {
        $colleges = College::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.programs.create', [
            'colleges' => $colleges,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'college_id' => ['required', 'integer', 'exists:colleges,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $validated['name'] = trim($validated['name']);

        // enforce unique(['college_id','name']) at model/db level too
        $exists = Program::query()
            ->where('college_id', (int) $validated['college_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Program already exists for the selected college.'])->withInput();
        }

        Program::create($validated);

        return Redirect::route('admin.programs.index')->with('success', 'Program created successfully.');
    }

    public function edit(Program $program): View
    {
        $colleges = College::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.programs.edit', [
            'program' => $program,
            'colleges' => $colleges,
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'college_id' => ['required', 'integer', 'exists:colleges,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $validated['name'] = trim($validated['name']);

        $program->update($validated);

        return Redirect::route('admin.programs.index')->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program)
    {
        $program->delete();

        return Redirect::route('admin.programs.index')->with('success', 'Program deleted successfully.');
    }
}
