@extends('layouts.student')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Submit Assignment</div>
            <div class="text-sm text-gray-500">{{ $assignment->title ?? ('Assignment #' . $assignment->id) }}</div>
        </div>
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
    </div>

    <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" enctype="multipart/form-data" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
        @csrf

        <div>
            <div class="text-sm font-semibold text-gray-700">Upload File</div>
            <input type="file" name="file" class="mt-2 block w-full text-sm" />
            @error('file')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
            <div class="text-xs text-gray-500 mt-2">Max 10MB. Accepted types depend on your PHP upload settings.</div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Submit</button>
        </div>
    </form>
@endsection

