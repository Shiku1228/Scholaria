@extends('layouts.teacher')

@section('content')
    {{-- Header Section --}}
    <div class="flex items-start justify-between gap-4 mb-8">
        <div class="flex-1">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] flex items-center justify-center shadow-lg">
                    <i data-lucide="file-text" class="h-6 w-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $assignment->title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
            </a>
            <a href="{{ route('teacher.assignments.edit', [$course, $assignment]) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all">
                <i data-lucide="pencil" class="h-4 w-4 mr-2"></i>Edit
            </a>
            <form method="POST" action="{{ route('teacher.assignments.destroy', [$course, $assignment]) }}" onsubmit="return confirm('Delete this assignment? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center h-11 px-4 rounded-lg border border-red-200 bg-red-50 text-sm font-semibold text-red-700 hover:bg-red-100 transition-colors shadow-sm">
                    <i data-lucide="trash-2" class="h-4 w-4 mr-2"></i>Delete
                </button>
            </form>
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

        {{-- Submissions Count Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Submissions</div>
                    @php
                        $submissionCount = $submissions instanceof \Illuminate\Support\Collection ? $submissions->count() : $submissions->total();
                    @endphp
                    <div class="mt-1 text-sm font-bold text-slate-900">{{ $submissionCount }} <span class="text-xs font-normal text-slate-500">submitted</span></div>
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

    {{-- Questions Overview (only when the assignment has questions) --}}
    @if($questions->isNotEmpty())
        @php $isMCFormat = ($assignment->assignment_format ?? 'essay') === 'multiple_choice'; @endphp
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                        <i data-lucide="list-checks" class="h-5 w-5 text-indigo-600"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Assignment Questions</h2>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ $questions->count() }} question{{ $questions->count() !== 1 ? 's' : '' }}
                            &middot; {{ $isMCFormat ? 'Multiple Choice' : 'Essay' }}
                            &middot; {{ $assignment->max_score ?? $questions->sum('points') }} pts total
                        </p>
                    </div>
                </div>
            </div>
            <div class="p-6 space-y-3">
                @foreach($questions as $qi => $q)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <span class="h-7 w-7 rounded-full bg-[#0b2d6b] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ $qi + 1 }}</span>
                                <p class="text-sm text-slate-800 font-medium leading-relaxed">{{ $q->question_text }}</p>
                            </div>
                            <span class="flex-shrink-0 text-xs font-semibold text-slate-500 bg-slate-200 rounded-full px-3 py-1">{{ $q->points }} pt{{ $q->points !== 1 ? 's' : '' }}</span>
                        </div>
                        @if($isMCFormat && $q->choices->isNotEmpty())
                            <div class="mt-3 ml-10 space-y-1">
                                @foreach($q->choices as $c)
                                    <div class="flex items-center gap-2 text-xs {{ $c->is_correct ? 'text-emerald-700 font-semibold' : 'text-slate-500' }}">
                                        <i data-lucide="{{ $c->is_correct ? 'check-circle' : 'circle' }}" class="h-3.5 w-3.5 flex-shrink-0 {{ $c->is_correct ? 'text-emerald-500' : 'text-slate-300' }}"></i>
                                        {{ $c->choice_text }}
                                        @if($c->is_correct) <span class="text-emerald-600">(correct)</span> @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Submissions Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-slate-200 flex items-center justify-center">
                            <i data-lucide="inbox" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Student Submissions</h2>
                            <p class="text-xs text-slate-500 mt-1">Review, grade, and provide feedback to students</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            @if ($submissions instanceof \Illuminate\Support\Collection ? $submissions->isEmpty() : $submissions->count() === 0)
                <div class="text-center py-12">
                    <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="inbox" class="h-8 w-8 text-slate-400"></i>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 mb-1">No submissions yet</h3>
                    <p class="text-sm text-slate-500">Students will appear here once they submit their work</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($submissions as $s)
                        @php
                            $isLate = $s->submitted_at && $assignment->due_date && $s->submitted_at->isAfter($assignment->due_date);
                            $hasScore = $s->score !== null && $s->score !== '';
                            $scorePercentage = $hasScore && ($assignment->max_score ?? 100) ? ($s->score / ($assignment->max_score ?? 100)) * 100 : 0;
                            $isMC = ($assignment->assignment_format ?? 'essay') === 'multiple_choice';
                            $hasQs = $questions->isNotEmpty();
                            $isGraded = $hasScore;
                            $needsGrading = !$isGraded && $hasQs && !$isMC;
                        @endphp

                        <div class="group flex items-center justify-between gap-4 bg-white rounded-lg border border-slate-200 hover:border-[#c9d7f2] hover:shadow-sm transition-all duration-200 px-5 py-4">
                            {{-- Student info --}}
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] text-white flex items-center justify-center text-sm font-bold flex-shrink-0">
                                    {{ strtoupper(substr($s->student?->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-900 truncate">{{ $s->student?->name ?? 'Unknown Student' }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        @if($s->submitted_at)
                                            {{ $s->submitted_at->format('M d, Y \a\t g:i A') }}
                                        @elseif($s->created_at)
                                            {{ $s->created_at->format('M d, Y \a\t g:i A') }}
                                        @else
                                            —
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Badges --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($isLate)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <i data-lucide="clock" class="h-3 w-3"></i>Late
                                    </span>
                                @endif

                                @if($isGraded)
                                    @php $color = $scorePercentage >= 80 ? 'emerald' : ($scorePercentage >= 60 ? 'amber' : 'red'); @endphp
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-{{ $color }}-100 text-{{ $color }}-700">
                                        {{ $s->score }} / {{ $assignment->max_score ?? 100 }}
                                    </span>
                                @elseif($needsGrading)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <i data-lucide="clock" class="h-3 w-3"></i>Needs grading
                                    </span>
                                @elseif($isMC && $hasQs)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-[#eaf0fb] text-[#0b2d6b]">
                                        <i data-lucide="check-circle" class="h-3 w-3"></i>Auto-graded
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                        Not graded
                                    </span>
                                @endif

                                <a href="{{ route('teacher.submissions.show', [$course, $assignment, $s]) }}"
                                   class="inline-flex items-center justify-center h-9 px-4 rounded-lg text-xs font-semibold transition-colors
                                       {{ $needsGrading
                                           ? 'bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white hover:shadow-md'
                                           : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                                    @if($needsGrading)
                                        <i data-lucide="check-square" class="h-3.5 w-3.5 mr-1.5"></i>Grade
                                    @else
                                        <i data-lucide="eye" class="h-3.5 w-3.5 mr-1.5"></i>View
                                    @endif
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (method_exists($submissions, 'links'))
                    <div class="mt-6 pt-6 border-t border-slate-200">{{ $submissions->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
