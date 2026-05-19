@extends('layouts.student')

@section('content')
    <div class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] flex items-center justify-center shadow-lg">
                        <i data-lucide="file-text" class="h-6 w-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">{{ $assignment->title }}</h1>
                        <p class="mt-1 text-slate-600">{{ $course?->title ?? $course?->name ?? 'Course' }}</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('student.assignments.index') }}" class="inline-flex items-center justify-center h-11 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
            </a>
        </div>
    </div>

    {{-- Assignment Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        {{-- Due Date Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="calendar-clock" class="h-6 w-6 text-amber-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Due Date</div>
                    <div class="mt-1">
                        @if($assignment->due_date)
                            @php
                                $daysUntilDue = now()->diffInDays($assignment->due_date);
                                $isOverdue = $assignment->due_date < now();
                                $isUrgent = $daysUntilDue <= 3 && !$isOverdue;
                            @endphp
                            <div class="text-sm font-bold text-slate-900">{{ $assignment->due_date->format('M d, Y') }}</div>
                            <div class="text-xs {{ $isOverdue ? 'text-red-600 font-semibold' : ($isUrgent ? 'text-amber-600 font-semibold' : 'text-slate-500') }}">
                                @if($isOverdue)
                                    <i data-lucide="alert-circle" class="h-3 w-3 inline mr-1"></i>Overdue
                                @elseif($isUrgent)
                                    <i data-lucide="clock" class="h-3 w-3 inline mr-1"></i>{{ $daysUntilDue }} day{{ $daysUntilDue !== 1 ? 's' : '' }} left
                                @else
                                    {{ $assignment->due_date->format('g:i A') }}
                                @endif
                            </div>
                        @else
                            <div class="text-sm font-semibold text-slate-400">No due date set</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Max Score Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="target" class="h-6 w-6 text-emerald-600"></i>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Max Score</div>
                    <div class="mt-1 text-sm font-bold text-slate-900">{{ $assignment->max_score ?? 100 }} <span class="text-xs font-normal text-slate-500">points</span></div>
                </div>
            </div>
        </div>

        {{-- Status Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg {{ $submission ? 'bg-emerald-100' : 'bg-blue-100' }} flex items-center justify-center flex-shrink-0">
                    <i data-lucide="{{ $submission ? 'check-circle' : 'file-check' }}" class="h-6 w-6 {{ $submission ? 'text-emerald-600' : 'text-blue-600' }}"></i>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Status</div>
                    <div class="mt-1 text-sm font-bold text-slate-900">
                        @if($submission)
                            <span class="inline-flex items-center gap-1 text-emerald-700">
                                <i data-lucide="check" class="h-4 w-4"></i>
                                Submitted
                            </span>
                            @if($submission->score !== null)
                                <div class="text-xs text-slate-600 font-normal mt-0.5">Score: {{ $submission->score }}/{{ $assignment->max_score ?? 100 }}</div>
                            @endif
                        @else
                            <span>Not Submitted</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Description Section --}}
    @if($assignment->description)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                    <i data-lucide="align-left" class="h-5 w-5 text-slate-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Description</h2>
            </div>
            <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $assignment->description }}</div>
        </div>
    @endif

    {{-- Submission Info Section --}}
    @if($submission)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-emerald-50 to-transparent">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600"></i>
                            Your Submission
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Submitted on {{ $submission->submitted_at ? \Carbon\Carbon::parse($submission->submitted_at)->format('M d, Y \a\t g:i A') : 'Unknown date' }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-4">
                    {{-- Submission Type --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Submission Type</label>
                        <div class="flex items-center gap-2 text-sm text-slate-700">
                            @switch($submission->submission_type ?? 'file')
                                @case('text')
                                    <i data-lucide="file-text" class="h-4 w-4 text-blue-500"></i>
                                    <span>Text Submission</span>
                                    @break
                                @case('link')
                                    <i data-lucide="link" class="h-4 w-4 text-purple-500"></i>
                                    <span>Link Submission</span>
                                    @break
                                @case('file')
                                @default
                                    <i data-lucide="file" class="h-4 w-4 text-amber-500"></i>
                                    <span>File Submission</span>
                                    @break
                            @endswitch
                        </div>
                    </div>

                    {{-- Submission Details --}}
                    <div class="pt-4 border-t border-slate-200">
                        @switch($submission->submission_type ?? 'file')
                            @case('text')
                                @if($submission->content)
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Your Response</label>
                                    <div class="bg-slate-50 rounded-lg border border-slate-200 p-4 text-sm text-slate-700 leading-relaxed whitespace-pre-wrap max-h-64 overflow-y-auto">
                                        {!! nl2br(htmlspecialchars(strip_tags($submission->content, '<p><br><strong><em><u><ul><ol><li>'), ENT_QUOTES)) !!}
                                    </div>
                                @endif
                                @break
                            @case('link')
                                @if($submission->content)
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Submitted Link</label>
                                    <a href="{{ $submission->content }}" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-[#0b2d6b] hover:text-[#0a275c] hover:underline bg-blue-50 rounded-lg px-4 py-2 border border-blue-200">
                                        <i data-lucide="external-link" class="h-4 w-4"></i>
                                        {{ $submission->content }}
                                    </a>
                                @endif
                                @break
                            @case('file')
                            @default
                                @if ($submission->file_path)
                                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Submitted File</label>
                                    <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank" class="inline-flex items-center gap-2 text-sm font-medium text-[#0b2d6b] hover:text-[#0a275c] hover:underline bg-blue-50 rounded-lg px-4 py-2 border border-blue-200">
                                        <i data-lucide="download" class="h-4 w-4"></i>
                                        Download File
                                    </a>
                                @endif
                                @break
                        @endswitch
                    </div>

                    {{-- Feedback Section --}}
                    @if($submission->feedback)
                        <div class="pt-4 border-t border-slate-200">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Teacher Feedback</label>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-slate-700">
                                <p>{{ $submission->feedback }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Score Section --}}
                    @if($submission->score !== null)
                        <div class="pt-4 border-t border-slate-200">
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Your Score</label>
                            @php
                                $scorePercentage = ($submission->score / ($assignment->max_score ?? 100)) * 100;
                                $scoreColor = $scorePercentage >= 80 ? 'emerald' : ($scorePercentage >= 60 ? 'amber' : 'red');
                            @endphp
                            <div class="inline-flex items-center gap-4 bg-{{ $scoreColor }}-50 border border-{{ $scoreColor }}-200 rounded-lg px-4 py-3">
                                <div>
                                    <div class="text-2xl font-bold text-{{ $scoreColor }}-700">{{ $submission->score }}</div>
                                    <div class="text-xs text-{{ $scoreColor }}-600 font-medium">out of {{ $assignment->max_score ?? 100 }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-{{ $scoreColor }}-700">{{ round($scorePercentage) }}%</div>
                                    <div class="text-xs text-{{ $scoreColor }}-600 font-medium">
                                        @if($scorePercentage >= 80)
                                            Excellent
                                        @elseif($scorePercentage >= 60)
                                            Good
                                        @else
                                            Needs Improvement
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @else
            {{-- No Submission Yet Section --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 mb-8 text-center">
                <div class="h-16 w-16 rounded-full bg-amber-50 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="send" class="h-8 w-8 text-amber-500"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No Submission Yet</h3>
                <p class="text-sm text-slate-600 mb-6">You haven't submitted this assignment yet. Click the button below to submit your work.</p>
                <div class="flex items-center justify-center gap-3">
                    <a href="{{ route('student.assignments.index') }}" class="inline-flex items-center justify-center h-11 px-6 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                    <a href="{{ route('student.assignments.submit', $assignment) }}" class="inline-flex items-center justify-center h-11 px-6 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-[#0b2d6b] focus:ring-offset-2">
                        <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                        Submit Assignment
                    </a>
                </div>
            </div>
    @endif

    {{-- Action Buttons --}}
    @if($submission)
        <div class="flex items-center justify-start gap-3 pb-8">
            <a href="{{ route('student.assignments.index') }}" class="inline-flex items-center justify-center h-11 px-6 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                Back to Assignments
            </a>
        </div>
    @endif
@endsection


