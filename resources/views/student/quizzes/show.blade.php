@extends('layouts.student')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $quiz->title }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $quiz->course->course_number }} • {{ $quiz->course->title }}</div>
        </div>
        <a href="{{ route('student.courses.show', ['course' => $quiz->course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
        </a>
    </div>

    {{-- Quiz Info Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-xs text-slate-500">Questions</div>
                    <div class="font-semibold text-slate-900">{{ $quiz->questions->count() }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Time Limit</div>
                    <div class="font-semibold text-slate-900">{{ $quiz->time_limit ?? 15 }} min</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Total Points</div>
                    <div class="font-semibold text-slate-900">{{ $quiz->points ?? $quiz->max_score ?? 0 }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Attempts Allowed</div>
                    <div class="font-semibold text-slate-900">{{ $quiz->attempts_allowed ?? 1 }}</div>
                </div>
            </div>
            @if($quiz->description)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-600">{{ $quiz->description }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Attempt Status --}}
    @if($attempt)
        <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-5 mb-6">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-emerald-100 flex items-center justify-center">
                    <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-emerald-900">Quiz Completed</div>
                    <p class="text-xs text-emerald-700">Score: {{ $attempt->score ?? 0 }} / {{ $quiz->points ?? $quiz->max_score ?? 0 }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="flex justify-center">
            <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center h-12 px-8 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                    <i data-lucide="play" class="h-5 w-5 mr-2"></i>Start Quiz
                </button>
            </form>
        </div>
    @endif
@endsection
