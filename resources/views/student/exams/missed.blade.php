@extends('layouts.student')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
            <i data-lucide="x-circle" class="h-6 w-6 text-red-500"></i>
            <span>Missed Exam</span>
        </div>
        <a href="{{ route('student.exams.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Exams
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
        <div class="h-16 w-16 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="x-circle" class="h-8 w-8 text-red-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $exam->title }}</h3>
        <p class="text-sm text-slate-500 mb-4">{{ $exam->course->course_number ?? $exam->course->title }}</p>
        
        <div class="inline-flex items-center px-4 py-2 rounded-lg bg-red-50 border border-red-200 mb-6">
            <i data-lucide="calendar-x" class="h-4 w-4 text-red-600 mr-2"></i>
            <span class="text-sm text-red-800">
                This exam was held on {{ $exam->exam_date->format('F d, Y \a\t g:i A') }}
            </span>
        </div>
        
        <p class="text-sm text-slate-500 max-w-md mx-auto">
            You did not take this exam. Please contact your instructor if you believe this is an error or need to arrange a makeup exam.
        </p>
        
        <div class="mt-6">
            <a href="{{ route('student.courses.show', $exam->course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c]">
                <i data-lucide="message-circle" class="h-4 w-4 mr-2"></i>Contact Instructor
            </a>
        </div>
    </div>
@endsection
