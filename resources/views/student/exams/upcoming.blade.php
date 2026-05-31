@extends('layouts.student')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
            <i data-lucide="calendar-clock" class="h-6 w-6 text-[#0b2d6b]"></i>
            <span>Upcoming Exam</span>
        </div>
        <a href="{{ route('student.exams.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Exams
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
        <div class="h-16 w-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="clock" class="h-8 w-8 text-amber-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $exam->title }}</h3>
        <p class="text-sm text-slate-500 mb-4">{{ $exam->course->course_number ?? $exam->course->title }}</p>
        
        <div class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-50 border border-amber-200 mb-6">
            <i data-lucide="calendar" class="h-4 w-4 text-amber-600 mr-2"></i>
            <span class="text-sm text-amber-800">
                Starts on {{ $exam->exam_date->format('F d, Y \a\t g:i A') }}
            </span>
        </div>
        
        <p class="text-sm text-slate-500 max-w-md mx-auto">
            This exam will be available once the scheduled time arrives. Make sure to check back at the scheduled time.
        </p>
        
        <div class="mt-6 grid grid-cols-3 gap-4 max-w-md mx-auto">
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-lg font-semibold text-slate-900">{{ $exam->duration }}</div>
                <div class="text-xs text-slate-500">minutes</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-lg font-semibold text-slate-900">{{ $exam->max_score }}</div>
                <div class="text-xs text-slate-500">points</div>
            </div>
            <div class="bg-slate-50 rounded-lg p-3">
                <div class="text-lg font-semibold text-slate-900">{{ $exam->questions->count() }}</div>
                <div class="text-xs text-slate-500">questions</div>
            </div>
        </div>
    </div>
@endsection
