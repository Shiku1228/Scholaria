@extends('layouts.teacher')

@section('content')
    {{-- Simple Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-bold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Edit Quiz</span>
            </div>
            <p class="text-sm text-slate-500 mt-1">{{ $quiz->course->course_number }} - {{ $quiz->course->title }}</p>
        </div>
        <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="text-sm text-[#0b2d6b] hover:underline">
            &larr; Back to Quiz
        </a>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            @foreach($errors->all() as $error)
                <p class="text-red-600 text-sm">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.quizzes.update', $quiz) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        @csrf
        @method('PUT')

        {{-- Form Header --}}
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <span class="inline-flex items-center gap-1.5">
                    <i data-lucide="help-circle" class="h-4 w-4 text-amber-600"></i>
                    Quiz Details
                </span>
            </div>
            @if($quiz->is_published ?? false)
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Published</span>
            @else
                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full">Draft</span>
            @endif
        </div>

        {{-- Form Fields --}}
        <div class="p-6 space-y-5">
            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Quiz Title *</label>
                <input type="text" name="title" value="{{ old('title', $quiz->title) }}" 
                    class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]" 
                    required>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="2" 
                    class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] resize-none">{{ old('description', $quiz->description) }}</textarea>
            </div>

            {{-- Date & Time Limit Row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Due Date</label>
                    <input type="datetime-local" name="due_date" 
                        value="{{ old('due_date', $quiz->due_date?->format('Y-m-d\TH:i')) }}" 
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Time Limit (minutes) *</label>
                    <input type="number" name="time_limit" value="{{ old('time_limit', $quiz->time_limit ?? 15) }}" 
                        min="1" max="120" step="1"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]"
                        required>
                </div>
            </div>

            {{-- Score, Attempts & Options Row --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Maximum Score</label>
                    <input type="number" name="max_score" value="{{ old('max_score', $quiz->max_score ?? 100) }}" 
                        min="1" max="1000"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Attempts Allowed</label>
                    <input type="number" name="attempts_allowed" value="{{ old('attempts_allowed', $quiz->attempts_allowed ?? 1) }}" 
                        min="1" max="10"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Options</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="shuffle_questions" value="1" 
                                {{ old('shuffle_questions', $quiz->shuffle_questions) ? 'checked' : '' }} 
                                class="rounded border-slate-300 text-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <span class="text-sm text-slate-700">Shuffle Questions</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="show_results" value="1" 
                                {{ old('show_results', $quiz->show_results) ? 'checked' : '' }} 
                                class="rounded border-slate-300 text-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <span class="text-sm text-slate-700">Show Results</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-[#0b2d6b] text-white text-sm font-medium rounded-lg hover:bg-[#0a275c]">
                    Save Changes
                </button>
                <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="px-5 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                    Cancel
                </a>
            </div>
            <a href="{{ route('teacher.quizzes.questions', $quiz) }}" class="px-5 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">
                Questions ({{ $quiz->questions()->count() }})
            </a>
        </div>
    </form>

    {{-- Quiz Statistics Card --}}
    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $quiz->attempts->count() }}</div>
            <div class="text-xs text-slate-500">Total Attempts</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-2xl font-bold text-emerald-600">{{ $quiz->attempts->whereNotNull('completed_at')->count() }}</div>
            <div class="text-xs text-slate-500">Completed</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $quiz->attempts->pluck('student_id')->unique()->count() }}</div>
            <div class="text-xs text-slate-500">Unique Students</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
            <div class="text-2xl font-bold text-amber-600">{{ round($quiz->attempts->whereNotNull('completed_at')->avg('score') ?? 0, 1) }}</div>
            <div class="text-xs text-slate-500">Average Score</div>
        </div>
    </div>
@endsection
