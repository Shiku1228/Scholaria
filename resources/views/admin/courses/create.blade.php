@extends('layouts.admin')

@section('content')
    <div>
        <div class="text-xl font-semibold">Create Course</div>
        <div class="text-sm text-gray-500">Add a new course</div>
    </div>

    <form method="POST" action="{{ route('admin.courses.store') }}" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf

        @include('admin.courses._form', ['course' => null])

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Save</button>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
@endsection
