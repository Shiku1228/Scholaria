@extends('layouts.teacher')

@section('content')
    @php
        $maxScore   = $quiz->max_score ?? $quiz->points ?? $attempt->answers->sum(fn($a) => $a->question?->points ?? 0);
        $timeTaken  = $attempt->getTimeSpentMinutes();
        $finalScore = $attempt->score ?? 0;
        $percentage = $maxScore > 0 ? round(($finalScore / $maxScore) * 100) : 0;
        $scoreColor = $percentage >= 80 ? 'emerald' : ($percentage >= 60 ? 'amber' : 'red');
        $isGraded   = $attempt->isGraded();
        $hasEssay   = $attempt->answers->contains(fn($a) => $a->question && !$a->question->canAutoGrade());
        $hasOverride = $attempt->answers->contains(fn($a) => $a->is_overridden);
    @endphp

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="clipboard-check" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Grade Quiz Response</span>
            </div>
            <div class="mt-1 text-sm text-slate-500 flex flex-wrap items-center gap-x-3 gap-y-1">
                <span>{{ $quiz->title }}</span>
                <span class="text-slate-300">•</span>
                <span>{{ $attempt->student->name ?? 'Unknown' }}</span>
                <span class="text-slate-300">•</span>
                <span>Attempt #{{ $attempt->attempt_number }}</span>
                @if($isGraded)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                        <i data-lucide="check-circle" class="h-3 w-3"></i>Graded
                    </span>
                @elseif($hasEssay)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                        <i data-lucide="clock" class="h-3 w-3"></i>Needs Review
                    </span>
                @endif
            </div>
        </div>
        <a href="{{ route('teacher.quizzes.show', $quiz) }}"
            class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Quiz
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6 text-sm text-emerald-700 flex items-center gap-2">
            <i data-lucide="check-circle" class="h-4 w-4 flex-shrink-0"></i>{{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="text-xs text-slate-500 mb-1">Student</div>
            <div class="text-sm font-semibold text-slate-900 truncate">{{ $attempt->student->name ?? 'Unknown' }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="text-xs text-slate-500 mb-1">Current Score</div>
            <div class="text-sm font-bold text-{{ $scoreColor }}-700">
                {{ $finalScore }} / {{ $maxScore }}
                <span class="text-xs font-normal text-{{ $scoreColor }}-500">({{ $percentage }}%)</span>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="text-xs text-slate-500 mb-1">Submitted</div>
            <div class="text-sm font-semibold text-slate-900">
                {{ $attempt->submitted_at?->format('M d, Y g:i A') ?? '—' }}
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="text-xs text-slate-500 mb-1">Time Taken</div>
            <div class="text-sm font-semibold text-slate-900">{{ $timeTaken }} min</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="text-xs text-slate-500 mb-1">Status</div>
            <div class="text-sm font-semibold text-slate-900">
                @if($isGraded)
                    <span class="text-indigo-700">Graded</span>
                    @if($attempt->graded_at)
                        <div class="text-xs font-normal text-slate-400">{{ $attempt->graded_at->format('M d g:i A') }}</div>
                    @endif
                @elseif($attempt->submitted_at)
                    <span class="text-emerald-700">Submitted</span>
                @else
                    <span class="text-amber-700">In Progress</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Grading Form --}}
    <form method="POST" action="{{ route('teacher.quizzes.attempts.grade', [$quiz, $attempt]) }}" id="grading-form">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="list-checks" class="h-4 w-4 text-slate-500"></i>
                    Question-by-Question Grading
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-slate-500">{{ $attempt->answers->count() }} questions</span>
                    <button type="button" id="reset-auto-btn"
                        class="inline-flex items-center h-7 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                        <i data-lucide="rotate-ccw" class="h-3 w-3 mr-1.5"></i>Reset to Auto
                    </button>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($attempt->answers as $index => $answer)
                    @php
                        $question      = $answer->question;
                        $maxPts        = (int) ($question?->points ?? 0);
                        $pointsEarned  = (int) ($answer->points_earned ?? 0);
                        $autoScore     = $answer->auto_score ?? $pointsEarned;
                        $isAutoGraded  = $question && $question->canAutoGrade();
                        $isOverridden  = (bool) $answer->is_overridden;

                        // Badge logic
                        if ($isOverridden) {
                            $badge = ['label' => 'Overridden', 'cls' => 'bg-violet-100 text-violet-700'];
                        } elseif (!$isAutoGraded && $answer->points_earned === null) {
                            $badge = ['label' => 'Needs Review', 'cls' => 'bg-amber-100 text-amber-700'];
                        } elseif ($pointsEarned === $maxPts && $maxPts > 0) {
                            $badge = ['label' => 'Correct', 'cls' => 'bg-emerald-100 text-emerald-700'];
                        } elseif ($pointsEarned === 0) {
                            $badge = ['label' => 'Incorrect', 'cls' => 'bg-red-100 text-red-700'];
                        } else {
                            $badge = ['label' => 'Partial', 'cls' => 'bg-blue-100 text-blue-700'];
                        }
                    @endphp

                    <div class="p-5" data-question-row data-auto-score="{{ $autoScore }}"
                         data-question-id="{{ $question?->id ?? 0 }}" data-max-pts="{{ $maxPts }}">

                        {{-- Question header --}}
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-start gap-3 min-w-0">
                                <span class="h-7 w-7 rounded-full bg-[#0b2d6b] text-white text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ $index + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 leading-snug">{{ $question?->question_text ?? 'Deleted question' }}</p>
                                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                                            {{ ucfirst(str_replace('_', ' ', $question?->question_type ?? 'unknown')) }}
                                        </span>
                                        <span class="text-xs text-slate-500 font-medium">{{ $maxPts }} pts possible</span>
                                        @if($isAutoGraded)
                                            <span class="text-xs text-slate-400">Auto: {{ $autoScore }}/{{ $maxPts }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge['cls'] }}">
                                            {{ $badge['label'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Student answer display --}}
                        @if($question?->isMultipleChoice() && $question->options)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-4">
                                @foreach($question->options as $key => $option)
                                    @php
                                        $isSelected = $answer->answer === $key;
                                        $isRight    = $question->correct_answer === $key;
                                        $cls = 'bg-slate-50 border-slate-200 text-slate-700';
                                        if ($isSelected && $isRight)   $cls = 'bg-emerald-50 border-emerald-300 text-emerald-800 font-semibold';
                                        elseif ($isSelected && !$isRight) $cls = 'bg-red-50 border-red-300 text-red-800 font-semibold';
                                        elseif ($isRight) $cls = 'bg-emerald-50/60 border-emerald-200 text-emerald-700';
                                    @endphp
                                    <div class="flex items-center justify-between p-2.5 border rounded-lg text-sm {{ $cls }}">
                                        <span>{{ $key }}. {{ $option }}</span>
                                        <span class="text-xs">
                                            @if($isSelected && $isRight) <i data-lucide="check" class="h-3.5 w-3.5 text-emerald-600 inline"></i> Student's ✓
                                            @elseif($isSelected && !$isRight) <i data-lucide="x" class="h-3.5 w-3.5 text-red-600 inline"></i> Student's ✗
                                            @elseif($isRight) Correct answer
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($question?->isTrueFalse())
                            <div class="flex gap-3 mb-4">
                                @foreach(['true', 'false'] as $val)
                                    @php
                                        $isSelected = strtolower((string)$answer->answer) === $val;
                                        $isRight    = strtolower((string)$question->correct_answer) === $val;
                                        $cls = 'bg-slate-50 border-slate-200 text-slate-700';
                                        if ($isSelected && $isRight)   $cls = 'bg-emerald-50 border-emerald-300 text-emerald-800 font-semibold';
                                        elseif ($isSelected && !$isRight) $cls = 'bg-red-50 border-red-300 text-red-800 font-semibold';
                                        elseif ($isRight) $cls = 'bg-emerald-50/60 border-emerald-200 text-emerald-700';
                                    @endphp
                                    <div class="flex items-center gap-2 px-4 py-2 border rounded-lg text-sm {{ $cls }}">
                                        <span>{{ ucfirst($val) }}</span>
                                        @if($isSelected) <span class="text-xs">(student)</span> @endif
                                        @if($isRight && !$isSelected) <span class="text-xs text-emerald-600">✓ correct</span> @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
                                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Student's Answer</div>
                                <div class="text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $answer->answer ?: '— No answer submitted —' }}</div>
                            </div>
                            @if($question?->correct_answer)
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 mb-4 text-xs text-emerald-800">
                                    <span class="font-semibold">Reference answer:</span> {{ $question->correct_answer }}
                                </div>
                            @endif
                        @endif

                        @if($question?->explanation)
                            <div class="bg-amber-50/60 border border-amber-100 rounded-lg p-3 text-xs text-slate-600 flex items-start gap-2 mb-4">
                                <i data-lucide="info" class="h-3.5 w-3.5 text-amber-500 mt-0.5 flex-shrink-0"></i>
                                <span><strong>Explanation:</strong> {{ $question->explanation }}</span>
                            </div>
                        @endif

                        {{-- Grading controls --}}
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 space-y-3">
                            <div class="text-xs font-bold text-slate-600 uppercase tracking-wider">Teacher Scoring</div>
                            <div class="flex items-center gap-4 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-semibold text-slate-600 whitespace-nowrap">Score:</label>
                                    <input type="number"
                                        name="scores[{{ $question?->id ?? 0 }}]"
                                        value="{{ old("scores.{$question?->id}", $pointsEarned) }}"
                                        min="0"
                                        max="{{ $maxPts }}"
                                        step="1"
                                        data-score-input
                                        class="w-20 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-900 px-3 py-1.5 text-center focus:border-[#0b2d6b] focus:ring-[#0b2d6b]
                                               @error("scores.{$question?->id}") border-red-400 @enderror"
                                        {{ !$question ? 'disabled' : '' }}
                                    >
                                    <span class="text-xs text-slate-500 font-medium">/ {{ $maxPts }} pts</span>
                                </div>

                                {{-- Quick-set buttons --}}
                                @if($maxPts > 0 && $question)
                                    <div class="flex items-center gap-1.5">
                                        <button type="button"
                                            onclick="setScore(this, {{ $maxPts }})"
                                            class="h-7 px-2.5 rounded-lg border border-emerald-300 bg-emerald-50 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition-colors">
                                            Full ({{ $maxPts }})
                                        </button>
                                        @if($maxPts > 1)
                                            <button type="button"
                                                onclick="setScore(this, {{ (int)round($maxPts / 2) }})"
                                                class="h-7 px-2.5 rounded-lg border border-blue-300 bg-blue-50 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition-colors">
                                                Half ({{ (int)round($maxPts / 2) }})
                                            </button>
                                        @endif
                                        <button type="button"
                                            onclick="setScore(this, 0)"
                                            class="h-7 px-2.5 rounded-lg border border-red-300 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100 transition-colors">
                                            Zero (0)
                                        </button>
                                    </div>
                                @endif
                            </div>
                            @error("scores.{$question?->id}")
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">
                                    Feedback / Comment <span class="font-normal text-slate-400">(optional)</span>
                                </label>
                                <textarea
                                    name="feedback[{{ $question?->id ?? 0 }}]"
                                    rows="2"
                                    placeholder="Add a comment for this answer..."
                                    class="w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 px-3 py-2 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none"
                                    {{ !$question ? 'disabled' : '' }}
                                >{{ old("feedback.{$question?->id}", $answer->feedback) }}</textarea>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-slate-500">No answers recorded for this attempt.</div>
                @endforelse
            </div>
        </div>

        {{-- Live score preview + Action bar --}}
        <div class="sticky bottom-6 z-50">
            <div class="bg-white border border-slate-200 rounded-2xl shadow-xl px-5 py-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm text-slate-600">
                        Total score preview:
                        <span id="live-total" class="font-bold text-slate-900 text-base ml-1">{{ $finalScore }}</span>
                        <span class="text-slate-400">/ {{ $maxScore }}</span>
                    </div>
                    @if($hasOverride)
                        <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full font-semibold">Has overrides</span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('teacher.quizzes.show', $quiz) }}"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                        <i data-lucide="save" class="h-4 w-4 mr-2"></i>Save Scores
                    </button>
                </div>
            </div>
        </div>

    </form>

    <div class="pb-24"></div>

    <script>
        // Quick-set score
        function setScore(btn, value) {
            const row = btn.closest('[data-question-row]');
            const input = row?.querySelector('[data-score-input]');
            if (input) {
                input.value = value;
                input.dispatchEvent(new Event('input'));
            }
        }

        // Live total preview
        function recalcTotal() {
            let total = 0;
            document.querySelectorAll('[data-score-input]').forEach(function(input) {
                const v = parseFloat(input.value);
                if (!isNaN(v)) total += v;
            });
            const el = document.getElementById('live-total');
            if (el) el.textContent = Math.round(total);
        }

        document.querySelectorAll('[data-score-input]').forEach(function(input) {
            input.addEventListener('input', function() {
                const row = this.closest('[data-question-row]');
                const max = parseFloat(row?.dataset.maxPts ?? 0);
                let v = parseFloat(this.value);
                if (!isNaN(v) && v > max) { this.value = max; v = max; }
                if (!isNaN(v) && v < 0)   { this.value = 0; }
                recalcTotal();
            });
        });

        // Reset all inputs to their original auto scores
        document.getElementById('reset-auto-btn')?.addEventListener('click', function() {
            if (!confirm('Reset all scores to the auto-computed values?')) return;
            document.querySelectorAll('[data-question-row]').forEach(function(row) {
                const input = row.querySelector('[data-score-input]');
                if (input) {
                    input.value = row.dataset.autoScore ?? 0;
                    input.dispatchEvent(new Event('input'));
                }
            });
        });

        recalcTotal();
    </script>
@endsection
