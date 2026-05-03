@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="pencil" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Edit Assignment</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <a href="{{ route('teacher.assignments.show', [$course, $assignment]) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
        </a>
    </div>

    <form method="POST" action="{{ route('teacher.assignments.update', [$course, $assignment]) }}">
        @csrf
        @method('PUT')

        {{-- Assignment Details Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="file-text" class="h-4 w-4 text-slate-500"></i>
                    Assignment Details
                </div>
            </div>
            <div class="p-5 space-y-5">
                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="type" class="h-4 w-4 text-slate-400"></i>
                            Title
                        </span>
                    </label>
                    <input type="text" name="title" value="{{ old('title', $assignment->title) }}" placeholder="Enter assignment title" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                    @error('title')
                        <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="align-left" class="h-4 w-4 text-slate-400"></i>
                            Description
                        </span>
                    </label>
                    <textarea name="description" rows="5" placeholder="Describe the assignment, instructions, requirements..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none">{{ old('description', $assignment->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Settings Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="settings-2" class="h-4 w-4 text-slate-500"></i>
                    Settings
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Due Date --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="calendar-clock" class="h-4 w-4 text-amber-500"></i>
                                Due Date & Time
                            </span>
                        </label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                        @if($assignment->due_date)
                            <p class="mt-1 text-xs text-slate-500">
                                Current: {{ $assignment->due_date->format('M d, Y g:i A') }}
                            </p>
                        @endif
                        @error('due_date')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Max Score --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="target" class="h-4 w-4 text-emerald-500"></i>
                                Maximum Score
                            </span>
                        </label>
                        <div class="relative">
                            <input type="number" name="max_score" value="{{ old('max_score', $assignment->max_score ?? 100) }}" min="0" max="999" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] pl-3 pr-12" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">pts</span>
                        </div>
                        @error('max_score')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('teacher.assignments.show', [$course, $assignment]) }}" class="inline-flex items-center justify-center h-11 px-6 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="h-4 w-4 mr-2"></i>Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                <i data-lucide="check" class="h-4 w-4 mr-2"></i>Save Changes
            </button>
        </div>
    </form>
@endsection

