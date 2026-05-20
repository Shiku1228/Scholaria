@extends('layouts.student')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
            <i data-lucide="laptop" class="h-6 w-6 text-[#0b2d6b]"></i>
            <span>My Exams</span>
        </div>
        <a href="{{ route('student.tasks.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Tasks
        </a>
    </div>

    <div class="space-y-4">
        @forelse($exams as $exam)
            @php
                $attempt = $attempts[$exam->id] ?? null;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-4">
                        <div class="h-12 w-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="laptop" class="h-6 w-6 text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900">{{ $exam->title }}</h3>
                            <p class="text-sm text-slate-500">{{ $exam->course->course_number ?? $exam->course->title }}</p>
                            <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="h-3 w-3"></i>
                                    {{ $exam->exam_date?->format('M d, Y g:i A') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="clock" class="h-3 w-3"></i>
                                    {{ $exam->duration }} min
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="help-circle" class="h-3 w-3"></i>
                                    {{ $exam->questions->count() }} questions
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($attempt)
                            @if($attempt->isSubmitted())
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    Completed
                                </span>
                                <span class="text-sm font-semibold text-slate-900">
                                    {{ $attempt->score ?? 0 }}/{{ $exam->max_score }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    In Progress
                                </span>
                            @endif
                        @elseif($exam->exam_date && $exam->exam_date->isFuture())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                Upcoming
                            </span>
                        @endif
                        <a href="{{ route('student.exams.show', $exam) }}" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c]">
                            {{ $attempt ? 'View' : 'Take Exam' }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
                <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="laptop" class="h-8 w-8 text-slate-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No exams available</h3>
                <p class="text-sm text-slate-500">You don't have any online exams at the moment.</p>
            </div>
        @endforelse
    </div>

    @if($exams->hasPages())
        <div class="mt-6">
            {{ $exams->links() }}
        </div>
    @endif
@endsection
