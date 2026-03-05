@extends('layouts.teacher')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">{{ $announcement->title }}</div>
            <div class="text-sm text-gray-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.announcements.index', $course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
            <a href="{{ route('teacher.announcements.edit', [$course, $announcement]) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Edit</a>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="text-xs text-gray-500">Posted {{ $announcement->created_at ?? '' }}</div>
        <div class="mt-4 text-sm text-gray-700 whitespace-pre-line">{{ $announcement->message }}</div>
    </div>
@endsection
