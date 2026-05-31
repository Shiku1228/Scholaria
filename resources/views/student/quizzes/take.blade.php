@extends('layouts.student')

@section('content')
    @php
        $startedAt = $attempt->started_at ?? now();
        $timeLimitMinutes = $quiz->time_limit ?? 15;
        $endTime = $startedAt->copy()->addMinutes($timeLimitMinutes);
        $remainingSeconds = (int) max(0, now()->diffInSeconds($endTime, false));
    @endphp

    {{-- Header --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-2xl font-bold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $quiz->title }}</span>
            </div>
            <div class="mt-1 text-xs text-slate-500 flex flex-wrap gap-x-4 gap-y-1">
                <span>Attempt #{{ $attempt->attempt_number }}</span>
                <span>•</span>
                <span class="font-medium text-slate-600">Timer Started at: <span class="text-slate-950 font-bold">{{ $startedAt->format('g:i:s A') }}</span></span>
            </div>
        </div>
        <div class="flex-shrink-0 flex items-center gap-2 bg-rose-50 border border-rose-200 px-4 py-2.5 rounded-xl">
            <i data-lucide="clock" class="h-5 w-5 text-rose-600 animate-pulse"></i>
            <div class="text-xs">
                <span class="text-rose-700 block font-medium">Time Remaining</span>
                <span id="countdown_timer" class="text-rose-900 font-bold text-lg leading-none">--:--</span>
            </div>
        </div>
    </div>

    {{-- Quiz Form --}}
    <form id="quiz-taking-form" method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="space-y-6">
        @csrf

        @foreach($questions as $index => $question)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-[#0b2d6b] text-white flex items-center justify-center text-xs font-semibold">{{ $index + 1 }}</span>
                        <span class="text-sm font-medium text-slate-800">Question {{ $index + 1 }}</span>
                        <span class="text-xs text-slate-500">({{ $question->points }} pts)</span>
                    </div>
                </div>
                <div class="p-5">
                    <p class="text-sm text-slate-900 mb-4">{{ $question->question_text }}</p>

                    @if($question->isMultipleChoice())
                        <div class="space-y-2">
                            @foreach($question->options as $key => $option)
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" class="text-[#0b2d6b]" required>
                                    <span class="text-sm"><strong>{{ $key }}.</strong> {{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif($question->isTrueFalse())
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="answers[{{ $question->id }}]" value="true" class="text-[#0b2d6b]" required>
                                <span class="text-sm">True</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="answers[{{ $question->id }}]" value="false" class="text-[#0b2d6b]">
                                <span class="text-sm">False</span>
                            </label>
                        </div>
                    @elseif($question->isShortAnswer())
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" placeholder="Enter your answer..."></textarea>
                    @elseif($question->isEssay())
                        <textarea name="answers[{{ $question->id }}]" rows="5" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" placeholder="Write your essay response..."></textarea>
                    @else
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" placeholder="Enter your answer..."></textarea>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="flex justify-center pb-24 md:pb-8">
            <button type="submit" id="submit-btn" class="inline-flex items-center justify-center h-12 px-8 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors shadow-sm" onclick="return confirm('Submit quiz?')">
                <i data-lucide="check-circle" class="h-5 w-5 mr-2"></i>Submit Quiz
            </button>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let timeLeft = {{ $remainingSeconds }};
            const timerEl = document.getElementById('countdown_timer');
            const form = document.getElementById('quiz-taking-form');

            function formatTime(secs) {
                const h = Math.floor(secs / 3600);
                const m = Math.floor((secs % 3600) / 60);
                const s = Math.floor(secs % 60);
                const mm = String(m).padStart(2, '0');
                const ss = String(s).padStart(2, '0');
                return h > 0 ? (h + ':' + mm + ':' + ss) : (mm + ':' + ss);
            }

            function updateTimer() {
                if (timeLeft <= 0) {
                    timerEl.textContent = "00:00";
                    clearInterval(timerInterval);
                    const submitBtn = document.getElementById('submit-btn');
                    if (submitBtn) {
                        submitBtn.onclick = null;
                    }
                    alert("Time has expired! Submitting your quiz automatically.");
                    form.submit();
                    return;
                }

                timerEl.textContent = formatTime(timeLeft);
                timeLeft--;
            }

            updateTimer();
            const timerInterval = setInterval(updateTimer, 1000);
        });
    </script>
@endsection
