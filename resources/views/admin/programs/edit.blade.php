@extends('layouts.dashboard', [
    'title' => 'Edit Program',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    <div class="px-6 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Edit Program</h1>
            <p class="text-sm text-slate-500 mt-1">Update the program catalog entry.</p>
        </div>

        <form method="POST" action="{{ route('admin.programs.update', $program) }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700" for="college_id">College</label>
                <select id="college_id" name="college_id" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                    @foreach($colleges as $college)
                        <option value="{{ $college->id }}" {{ (int) $program->college_id === (int) $college->id ? 'selected' : '' }}>
                            {{ $college->name }}
                        </option>
                    @endforeach
                </select>
                @error('college_id')
                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" for="name">Program Name</label>
                <input id="name" name="name" type="text"
                       value="{{ old('name', $program->name) }}"
                       class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                       required>
                @error('name')
                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Update
                </button>
                <a href="{{ route('admin.programs.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
