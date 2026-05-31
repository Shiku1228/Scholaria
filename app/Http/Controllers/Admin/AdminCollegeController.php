<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class AdminCollegeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:colleges.view')->only(['index', 'show']);
        $this->middleware('permission:colleges.create')->only(['create', 'store']);
        $this->middleware('permission:colleges.edit')->only(['edit', 'update']);
        $this->middleware('permission:colleges.delete')->only(['destroy']);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $query = College::query()->orderByDesc('id');

        if ($search !== '') {
            $query->where('name', 'like', $search . '%');
        }

        $colleges = $query->paginate(15)->withQueryString();

        return view('admin.colleges.index', [
            'colleges' => $colleges,
            'filters' => ['q' => $search],
        ]);
    }

    public function create(): View
    {
        return view('admin.colleges.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:colleges,name'],
        ]);

        College::create($validated);

        return Redirect::route('admin.colleges.index')->with('success', 'College created successfully.');
    }

    public function edit(College $college): View
    {
        return view('admin.colleges.edit', ['college' => $college]);
    }

    public function update(Request $request, College $college)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:colleges,name,' . $college->id],
        ]);

        $college->update($validated);

        return Redirect::route('admin.colleges.index')->with('success', 'College updated successfully.');
    }

    public function destroy(College $college)
    {
        // If programs exist, FK will cascade only on delete; but uniqueness and FK integrity should be fine.
        $college->delete();

        return Redirect::route('admin.colleges.index')->with('success', 'College deleted successfully.');
    }
}
