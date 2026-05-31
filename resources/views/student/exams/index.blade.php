@extends('layouts.student')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
            <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
            <span>My Exams</span>
        </div>
        <a href="{{ route('student.tasks.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Tasks
        </a>
    </div>

    <div class="space-y-4">
        @forelse($exams as $exam)
            @php
                $isFaceToFace = $exam->isFaceToFace();
                $attempt = $attempts[$exam->id] ?? null;
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="h-12 w-12 rounded-xl {{ $isFaceToFace ? 'bg-amber-100' : 'bg-purple-100' }} flex items-center justify-center flex-shrink-0">
                            <i data-lucide="{{ $isFaceToFace ? 'users' : 'laptop' }}" class="h-6 w-6 {{ $isFaceToFace ? 'text-amber-600' : 'text-purple-600' }}"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h3 class="font-semibold text-slate-900 text-sm">{{ $exam->title }}</h3>
                                @if($isFaceToFace)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                        <i data-lucide="users" class="h-3 w-3 mr-1"></i>Face-to-Face
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                        <i data-lucide="laptop" class="h-3 w-3 mr-1"></i>Online
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mb-2">{{ $exam->course->course_number ?? $exam->course->title }}</p>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-400">
                                @if($exam->exam_date)
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="calendar" class="h-3 w-3"></i>
                                        {{ $exam->exam_date->format('M d, Y g:i A') }}
                                    </span>
                                @endif
                                @if(!$isFaceToFace && $exam->duration)
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="clock" class="h-3 w-3"></i>
                                        {{ $exam->duration }} min
                                    </span>
                                @endif
                                @if($isFaceToFace && $exam->location)
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="map-pin" class="h-3 w-3"></i>
                                        {{ $exam->location }}
                                    </span>
                                @endif
                                @if(!$isFaceToFace)
                                    <span class="flex items-center gap-1">
                                        <i data-lucide="help-circle" class="h-3 w-3"></i>
                                        {{ $exam->questions->count() }} questions
                                    </span>
                                @endif
                                <span class="flex items-center gap-1">
                                    <i data-lucide="target" class="h-3 w-3"></i>
                                    {{ $exam->max_score }} pts
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        @if($isFaceToFace)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                <i data-lucide="eye" class="h-3 w-3 mr-1"></i>Info Only
                            </span>
                        @elseif($attempt && $attempt->isSubmitted())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>Completed
                            </span>
                            @if($exam->show_results)
                                <span class="text-xs font-semibold text-slate-700">{{ $attempt->score ?? 0 }}/{{ $exam->max_score }}</span>
                            @endif
                        @elseif($attempt)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                In Progress
                            </span>
                        @elseif($exam->exam_date && $exam->exam_date->isFuture())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                Upcoming
                            </span>
                        @elseif($exam->due_date && $exam->due_date->isPast())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                Closed
                            </span>
                        @endif
                        <a href="{{ route('student.exams.show', $exam) }}"
                            class="inline-flex items-center justify-center h-9 px-4 rounded-lg {{ $isFaceToFace ? 'border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-[#0b2d6b] text-white hover:bg-[#0a275c]' }} text-xs font-semibold transition-colors">
                            @if($isFaceToFace)
                                <i data-lucide="info" class="h-3.5 w-3.5 mr-1.5"></i>View Details
                            @elseif($attempt && $attempt->isSubmitted())
                                <i data-lucide="eye" class="h-3.5 w-3.5 mr-1.5"></i>Review
                            @else
                                <i data-lucide="play" class="h-3.5 w-3.5 mr-1.5"></i>{{ $attempt ? 'Continue' : 'Take Exam' }}
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center">
                <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-text" class="h-8 w-8 text-slate-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No exams available</h3>
                <p class="text-sm text-slate-500">You don't have any exams at the moment.</p>
            </div>
        @endforelse
    </div>

    @if($exams->hasPages())
        <div class="mt-6">{{ $exams->links() }}</div>
    @endif
@endsection
