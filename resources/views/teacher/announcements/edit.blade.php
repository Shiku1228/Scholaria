@extends('layouts.teacher')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Edit Announcement</div>
            <div class="text-sm text-gray-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <a href="{{ route('teacher.announcements.show', [$course, $announcement]) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
    </div>

    <form method="POST" action="{{ route('teacher.announcements.update', [$course, $announcement]) }}" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <div class="text-sm font-semibold text-gray-700">Title</div>
            <input name="title" value="{{ old('title', $announcement->title) }}" class="mt-2 w-full rounded-xl border-gray-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
            @error('title')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <div class="text-sm font-semibold text-gray-700">Message</div>
            <textarea name="message" rows="6" class="mt-2 w-full rounded-xl border-gray-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">{{ old('message', $announcement->message) }}</textarea>
            @error('message')
                <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('teacher.announcements.show', [$course, $announcement]) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Save</button>
        </div>
    </form>
@endsection

