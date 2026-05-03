@extends('layouts.student')

@section('content')
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $exam->title }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                    <i data-lucide="laptop" class="h-3 w-3 mr-1"></i>Online Exam
                </span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $exam->course->course_number ?? $exam->course->title }}</div>
        </div>
        <a href="{{ route('student.tasks.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Tasks
        </a>
    </div>

    {{-- Exam Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="calendar-clock" class="h-5 w-5 text-amber-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Exam Date</div>
                    <div class="text-sm font-semibold text-slate-900">
                        {{ $exam->exam_date?->format('M d, Y') ?? 'Not scheduled' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i data-lucide="clock" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Duration</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $exam->duration }} minutes</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <i data-lucide="target" class="h-5 w-5 text-emerald-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Total Points</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $exam->max_score }} points</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Instructions --}}
    @if($exam->instructions)
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-5 mb-6">
            <div class="flex items-center gap-2 text-sm font-semibold text-blue-900 mb-2">
                <i data-lucide="info" class="h-4 w-4"></i>
                Instructions
            </div>
            <p class="text-sm text-blue-800">{{ $exam->instructions }}</p>
        </div>
    @endif

    {{-- Start Exam Button --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
        <div class="h-16 w-16 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="laptop" class="h-8 w-8 text-purple-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-2">Ready to take the exam?</h3>
        <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">
            This exam has {{ $exam->questions->count() ?? 0 }} questions and takes {{ $exam->duration }} minutes to complete.
            Make sure you have a stable internet connection before starting.
        </p>
        <form method="POST" action="{{ route('student.exams.start', $exam) }}">
            @csrf
            <button type="submit" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 transition-colors shadow-sm">
                <i data-lucide="play" class="h-4 w-4 mr-2"></i>Start Exam
            </button>
        </form>
    </div>
@endsection
