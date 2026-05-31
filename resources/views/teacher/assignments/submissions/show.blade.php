@extends('layouts.teacher')

@section('content')
    @php
        $isMC      = ($assignment->assignment_format ?? 'essay') === 'multiple_choice';
        $maxScore  = $assignment->max_score ?? 100;
        $hasScore  = $submission->score !== null && $submission->score !== '';
        $isGraded  = $hasScore && $submission->graded_at !== null;
        $percentage = $hasScore && $maxScore ? round(($submission->score / $maxScore) * 100) : 0;
        $isLate    = $submission->submitted_at && $assignment->due_date && $submission->submitted_at->isAfter($assignment->due_date);
    @endphp

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-8">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] flex items-center justify-center shadow-lg flex-shrink-0">
                <i data-lucide="clipboard-check" class="h-6 w-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 leading-tight">
                    {{ $submission->student?->name ?? 'Student' }}'s Submission
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $assignment->title }}
                    &middot;
                    {{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}
                </p>
            </div>
        </div>
        <a href="{{ route('teacher.assignments.show', [$course, $assignment]) }}"
           class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm flex-shrink-0">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Submissions
        </a>
    </div>

    {{-- Success / Error flash --}}
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-5 py-4 text-sm text-emerald-800">
            <i data-lucide="check-circle" class="h-5 w-5 text-emerald-500 flex-shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-xl bg-red-50 border border-red-200 px-5 py-4 text-sm text-red-800">
            <div class="flex items-center gap-2 mb-2 font-semibold">
                <i data-lucide="alert-circle" class="h-4 w-4"></i>Please fix the following errors:
            </div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Info Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        {{-- Student --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Student</div>
            <div class="text-sm font-bold text-slate-900 truncate">{{ $submission->student?->name ?? '—' }}</div>
            @if($submission->student?->student_number ?? null)
                <div class="text-xs text-slate-400 mt-0.5">{{ $submission->student->student_number }}</div>
            @endif
        </div>

        {{-- Submitted --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Submitted</div>
            @if($submission->submitted_at)
                <div class="text-sm font-bold text-slate-900">{{ $submission->submitted_at->format('M d, Y') }}</div>
                <div class="text-xs text-slate-400 mt-0.5">
                    {{ $submission->submitted_at->format('g:i A') }}
                    @if($isLate)
                        <span class="ml-1 text-red-600 font-semibold">· Late</span>
                    @endif
                </div>
            @else
                <div class="text-sm text-slate-400">—</div>
            @endif
        </div>

        {{-- Format --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Format</div>
            <div class="text-sm font-bold text-slate-900">
                @if($hasQuestions)
                    {{ $isMC ? 'Multiple Choice' : 'Essay' }}
                @else
                    Legacy
                @endif
            </div>
            @if($hasQuestions)
                <div class="text-xs text-slate-400 mt-0.5">{{ $questions->count() }} question{{ $questions->count() !== 1 ? 's' : '' }} · {{ $maxScore }} pts</div>
            @endif
        </div>

        {{-- Score --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
            <div class="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">Score</div>
            @if($hasScore)
                @php $color = $percentage >= 80 ? 'emerald' : ($percentage >= 60 ? 'amber' : 'red'); @endphp
                <div class="text-xl font-bold text-{{ $color }}-600">{{ $submission->score }}<span class="text-sm font-normal text-slate-400"> / {{ $maxScore }}</span></div>
                <div class="text-xs text-slate-400 mt-0.5">{{ $percentage }}%</div>
            @elseif($isMC && $hasQuestions)
                <div class="text-sm font-semibold text-[#0b2d6b]">Auto-scored</div>
            @else
                <div class="text-sm text-slate-400">Not yet graded</div>
            @endif
        </div>
    </div>

    {{-- Grading status banner --}}
    @if($isGraded)
        <div class="mb-6 flex items-center gap-3 rounded-xl bg-[#eaf0fb] border border-[#c9d7f2] px-5 py-4">
            <i data-lucide="check-circle" class="h-5 w-5 text-[#0b2d6b] flex-shrink-0"></i>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-[#0b2d6b]">Graded</p>
                <p class="text-xs text-slate-500 mt-0.5">
                    By {{ $submission->gradedBy?->name ?? 'Teacher' }}
                    on {{ $submission->graded_at->format('M d, Y \a\t g:i A') }}
                </p>
            </div>
            <span class="text-sm font-bold text-[#0b2d6b]">{{ $submission->score }} / {{ $maxScore }}</span>
        </div>
    @elseif(!$isMC && $hasQuestions)
        <div class="mb-6 flex items-center gap-3 rounded-xl bg-amber-50 border border-amber-200 px-5 py-4">
            <i data-lucide="clock" class="h-5 w-5 text-amber-600 flex-shrink-0"></i>
            <p class="text-sm font-semibold text-amber-800">Pending grading — score each question below and click Save.</p>
        </div>
    @endif

    {{-- ── QUESTION-BASED SUBMISSION ── --}}
    @if($hasQuestions)
        @if($isMC)
            {{-- Multiple Choice: read-only review --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <i data-lucide="list-checks" class="h-5 w-5 text-indigo-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Student Answers</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Multiple choice — auto-graded</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    @foreach($questions as $qi => $q)
                        @php
                            $ans = $answers->get($q->id);
                            $selectedChoice = $ans?->selectedChoice ?? null;
                            $qScore = (int) ($ans?->score ?? 0);
                        @endphp
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <span class="h-7 w-7 rounded-full bg-[#0b2d6b] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ $qi + 1 }}</span>
                                    <p class="text-sm font-medium text-slate-800 leading-relaxed">{{ $q->question_text }}</p>
                                </div>
                                <span class="flex-shrink-0 text-xs font-bold
                                    {{ $qScore >= $q->points ? 'text-emerald-600' : ($qScore > 0 ? 'text-amber-600' : 'text-red-600') }}">
                                    {{ $qScore }} / {{ $q->points }} pts
                                </span>
                            </div>
                            <div class="ml-10 space-y-1.5">
                                @foreach($q->choices as $c)
                                    @php $isSelected = $selectedChoice && (int)$selectedChoice->id === (int)$c->id; @endphp
                                    <div class="flex items-center gap-2 text-xs rounded-md px-3 py-2
                                        {{ $isSelected && $c->is_correct  ? 'bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold'
                                        : ($isSelected && !$c->is_correct ? 'bg-red-50 border border-red-200 text-red-800 font-semibold'
                                        : ($c->is_correct                 ? 'bg-emerald-50/50 border border-emerald-100 text-emerald-700'
                                        :                                    'bg-white border border-slate-200 text-slate-500')) }}">
                                        @if($isSelected && $c->is_correct)
                                            <i data-lucide="check-circle" class="h-4 w-4 text-emerald-500 flex-shrink-0"></i>
                                        @elseif($isSelected && !$c->is_correct)
                                            <i data-lucide="x-circle" class="h-4 w-4 text-red-500 flex-shrink-0"></i>
                                        @elseif($c->is_correct)
                                            <i data-lucide="check-circle" class="h-4 w-4 text-emerald-400 opacity-70 flex-shrink-0"></i>
                                        @else
                                            <i data-lucide="circle" class="h-4 w-4 text-slate-300 flex-shrink-0"></i>
                                        @endif
                                        <span class="flex-1">{{ $c->choice_text }}</span>
                                        @if($isSelected)
                                            <span class="text-xs opacity-70">(student's answer)</span>
                                        @endif
                                        @if($c->is_correct && !$isSelected)
                                            <span class="text-xs text-emerald-600">(correct)</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- MC overall score summary + optional feedback --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                        <i data-lucide="star" class="h-5 w-5 text-slate-500"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Result</h2>
                </div>

                @php $totalPoints = $questions->sum('points'); @endphp
                <div class="flex items-center gap-6 mb-6">
                    <div class="text-center">
                        @php $pct = $totalPoints ? round(($submission->score / $totalPoints) * 100) : 0; @endphp
                        <div class="text-4xl font-bold {{ $pct >= 80 ? 'text-emerald-600' : ($pct >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                            {{ $submission->score ?? '—' }}
                        </div>
                        <div class="text-sm text-slate-500">out of {{ $totalPoints }}</div>
                    </div>
                    <div class="flex-1">
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $pct >= 80 ? 'bg-emerald-500' : ($pct >= 60 ? 'bg-amber-400' : 'bg-red-400') }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="mt-1 text-xs text-slate-500">{{ $pct }}%
                            @if($pct >= 80) · Excellent
                            @elseif($pct >= 60) · Good
                            @else · Needs Improvement
                            @endif
                        </div>
                    </div>
                </div>

                @if($submission->feedback)
                    <div class="rounded-lg bg-[#eaf0fb] border border-[#c9d7f2] px-4 py-3">
                        <p class="text-xs font-semibold text-[#0b2d6b] mb-1">Teacher Feedback</p>
                        <p class="text-sm text-slate-700">{{ $submission->feedback }}</p>
                    </div>
                @endif
            </div>

        @else
            {{-- Essay: per-question answers + grading form --}}
            <form method="POST" action="{{ route('teacher.submissions.update', [$course, $assignment, $submission]) }}">
                @csrf
                @method('PATCH')

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                    <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <i data-lucide="pen-line" class="h-5 w-5 text-indigo-600"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Student Answers</h2>
                                <p class="text-xs text-slate-500 mt-0.5">Score each question and optionally add feedback per answer</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-6">
                        @foreach($questions as $qi => $q)
                            @php $ans = $answers->get($q->id); @endphp
                            <div class="rounded-xl border {{ $ans && $ans->score !== null ? 'border-emerald-200 bg-emerald-50/30' : 'border-slate-200 bg-slate-50/50' }} p-5">
                                {{-- Question header --}}
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="flex items-start gap-3 flex-1 min-w-0">
                                        <span class="h-7 w-7 rounded-full bg-[#0b2d6b] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ $qi + 1 }}</span>
                                        <p class="text-sm font-semibold text-slate-900 leading-relaxed">{{ $q->question_text }}</p>
                                    </div>
                                    <span class="flex-shrink-0 text-xs font-semibold text-slate-500 bg-slate-200 rounded-full px-3 py-1 mt-0.5">{{ $q->points }} pts</span>
                                </div>

                                {{-- Student's essay answer --}}
                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Student's Answer</p>
                                    @if($ans && $ans->essay_answer)
                                        <div class="bg-white rounded-lg border border-slate-200 p-4 text-sm text-slate-800 leading-relaxed whitespace-pre-wrap max-h-48 overflow-y-auto shadow-inner">{{ $ans->essay_answer }}</div>
                                    @else
                                        <div class="bg-white rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-400 italic">No answer provided</div>
                                    @endif
                                </div>

                                {{-- Score + feedback inputs --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Score <span class="font-normal text-slate-400">(0–{{ $q->points }})</span>
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input type="number"
                                                   name="answer_scores[{{ $q->id }}]"
                                                   value="{{ old('answer_scores.' . $q->id, $ans?->score) }}"
                                                   min="0"
                                                   max="{{ $q->points }}"
                                                   class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-center font-semibold focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20 outline-none"
                                                   placeholder="—">
                                            <span class="text-sm text-slate-400">/ {{ $q->points }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                            Feedback <span class="font-normal text-slate-400">(optional)</span>
                                        </label>
                                        <input type="text"
                                               name="answer_feedback[{{ $q->id }}]"
                                               value="{{ old('answer_feedback.' . $q->id, $ans?->feedback) }}"
                                               maxlength="2000"
                                               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20 outline-none"
                                               placeholder="Comment on this answer...">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Overall feedback + save --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                            <i data-lucide="message-square" class="h-5 w-5 text-slate-500"></i>
                        </div>
                        <h2 class="text-lg font-semibold text-slate-900">Overall Feedback</h2>
                    </div>

                    <textarea name="feedback"
                              rows="3"
                              maxlength="5000"
                              class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20 outline-none resize-none mb-5"
                              placeholder="Write overall feedback for this student (optional)...">{{ old('feedback', $submission->feedback) }}</textarea>

                    <div class="flex items-center justify-between gap-4">
                        <p class="text-xs text-slate-500">
                            <i data-lucide="info" class="h-3.5 w-3.5 inline mr-1"></i>
                            The total score will be calculated automatically from per-question scores.
                        </p>
                        <button type="submit"
                                class="inline-flex items-center justify-center h-11 px-6 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all">
                            <i data-lucide="save" class="h-4 w-4 mr-2"></i>Save Grade
                        </button>
                    </div>
                </div>
            </form>
        @endif

    @else
        {{-- ── LEGACY SUBMISSION ── --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                        <i data-lucide="file" class="h-5 w-5 text-slate-600"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Submitted Work</h2>
                </div>
            </div>
            <div class="p-6">
                @switch($submission->submission_type ?? 'file')
                    @case('text')
                        <div class="bg-slate-50 rounded-lg border border-slate-200 p-5 text-sm text-slate-800 leading-relaxed whitespace-pre-wrap max-h-96 overflow-y-auto">
                            {!! nl2br(htmlspecialchars(strip_tags($submission->content ?? ''), ENT_QUOTES)) !!}
                        </div>
                        @break
                    @case('link')
                        @if($submission->content)
                            <a href="{{ $submission->content }}" target="_blank"
                               class="inline-flex items-center gap-2 text-[#0b2d6b] hover:underline font-medium text-sm">
                                <i data-lucide="external-link" class="h-4 w-4"></i>
                                {{ $submission->content }}
                            </a>
                        @else
                            <p class="text-slate-400 text-sm italic">No link provided</p>
                        @endif
                        @break
                    @default
                        @if($submission->file_path)
                            <a href="{{ asset('storage/' . $submission->file_path) }}" target="_blank"
                               class="inline-flex items-center gap-2 h-10 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                                <i data-lucide="download" class="h-4 w-4"></i>Download File
                            </a>
                        @else
                            <p class="text-slate-400 text-sm italic">No file uploaded</p>
                        @endif
                @endswitch
            </div>
        </div>

        {{-- Legacy grading form --}}
        <form method="POST" action="{{ route('teacher.submissions.update', [$course, $assignment, $submission]) }}">
            @csrf
            @method('PATCH')

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                        <i data-lucide="star" class="h-5 w-5 text-slate-500"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-slate-900">Grade This Submission</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Score <span class="font-normal text-slate-400">(0–{{ $maxScore }})</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   name="score"
                                   value="{{ old('score', $submission->score) }}"
                                   min="0"
                                   max="{{ $maxScore }}"
                                   class="w-28 rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20 outline-none"
                                   placeholder="—">
                            <span class="text-sm text-slate-400">/ {{ $maxScore }}</span>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Feedback <span class="font-normal text-slate-400">(optional)</span>
                    </label>
                    <textarea name="feedback"
                              rows="3"
                              maxlength="5000"
                              class="w-full rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20 outline-none resize-none"
                              placeholder="Write feedback for this student...">{{ old('feedback', $submission->feedback) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center justify-center h-11 px-6 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all">
                        <i data-lucide="save" class="h-4 w-4 mr-2"></i>Save Grade
                    </button>
                </div>
            </div>
        </form>
    @endif
@endsection
