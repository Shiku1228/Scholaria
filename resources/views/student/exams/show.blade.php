@extends('layouts.student')

@section('content')
    @php
        $formatDuration = function ($start, $end): string {
            if (!$start || !$end) {
                return 'N/A';
            }

            $seconds = max(0, $start->diffInSeconds($end));
            $hours = intdiv($seconds, 3600);
            $minutes = intdiv($seconds % 3600, 60);
            $secs = $seconds % 60;

            $parts = [];
            if ($hours > 0) {
                $parts[] = $hours . ' hr';
            }
            if ($minutes > 0) {
                $parts[] = $minutes . ' min';
            }
            if ($secs > 0 || empty($parts)) {
                $parts[] = $secs . ' sec';
            }

            return implode(' ', $parts);
        };
    @endphp

    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex flex-wrap items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $exam->title }}</span>
                @if($exam->isOnline())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        <i data-lucide="laptop" class="h-3 w-3 mr-1"></i>Online Exam
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        <i data-lucide="users" class="h-3 w-3 mr-1"></i>Face-to-Face Exam
                    </span>
                @endif
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $exam->course->course_number }} • {{ $exam->course->title }}</div>
        </div>
        <a href="{{ route('student.exams.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Exams
        </a>
    </div>

    @if($exam->isFaceToFace())
        @php
            $ftfPast = $exam->exam_date && $exam->exam_date->isPast();
        @endphp
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 mb-6 flex items-start gap-4">
            <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i data-lucide="info" class="h-5 w-5 text-amber-600"></i>
            </div>
            <div>
                <div class="font-semibold text-amber-900 mb-1">Face-to-Face Exam</div>
                <p class="text-sm text-amber-800">
                    {{ $ftfPast
                        ? 'This face-to-face exam has already been conducted. Please contact your instructor if you need more information.'
                        : 'This exam will be conducted face-to-face. Please follow your teacher\'s instructions and be present on the scheduled date.' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-800">Exam Information</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-xs text-slate-500">Total Points</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ $exam->max_score }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Exam Date</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ $exam->exam_date?->format('M j, Y g:i A') ?? 'Immediately' }}</div>
                            </div>
                            @if($exam->location)
                                <div>
                                    <div class="text-xs text-slate-500">Location</div>
                                    <div class="font-semibold text-slate-900 mt-0.5">{{ $exam->location }}</div>
                                </div>
                            @endif
                        </div>
                        @if($exam->instructions)
                            <div class="pt-4 border-t border-slate-100">
                                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Instructions</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $exam->instructions }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">Exam Status</h3>
                    <div class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                        <i data-lucide="users" class="h-3.5 w-3.5 mr-1.5"></i>Face-to-Face — No online submission
                    </div>
                </div>
            </div>
        </div>
    @else
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm text-red-600">
                {{ session('error') }}
            </div>
        @endif

        @php
            $canShowResults = $exam->show_results && !($exam->feedback_type === 'delayed' && !$exam->results_released);
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-800">Exam Information</h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <div class="text-xs text-slate-500">Questions</div>
                                <div class="font-semibold text-slate-900 mt-0.5">
                                    {{ $exam->random_subset_count ?: $exam->questions->count() }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Duration</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ $exam->duration }} min</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Total Points</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ $exam->max_score }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Attempts Allowed</div>
                                <div class="font-semibold text-slate-900 mt-0.5">{{ $exam->attempts_allowed ?? 1 }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100 text-sm">
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Start Date:</span>
                                <span class="font-medium text-slate-900">
                                    {{ $exam->exam_date ? $exam->exam_date->format('M j, Y g:i A') : 'Immediately' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar-days" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Due Date:</span>
                                <span class="font-medium text-slate-900">
                                    {{ $exam->due_date ? $exam->due_date->format('M j, Y g:i A') : 'No due date' }}
                                </span>
                            </div>
                        </div>

                        @if($exam->instructions)
                            <div class="pt-4 border-t border-slate-100">
                                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Instructions</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ $exam->instructions }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                @if($selectedAttempt)
                    @php
                        $attemptStatusLabel = match ($selectedAttempt->status) {
                            'graded' => 'Graded',
                            'submitted' => $selectedAttempt->answers->contains(fn ($a) => $a->points_earned === null) ? 'Pending Review' : 'Submitted',
                            default => ucfirst(str_replace('_', ' ', (string) $selectedAttempt->status)),
                        };
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                            <h2 class="text-sm font-semibold text-slate-800">
                                Review Attempt #{{ $selectedAttempt->attempt_number }}
                                <span class="text-xs font-normal text-slate-500 ml-1">
                                    (Submitted {{ $selectedAttempt->submitted_at?->format('M j, Y g:i A') }})
                                </span>
                            </h2>

                            @if($exam->feedback_type === 'delayed' && !$exam->results_released)
                                <span class="text-xs bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full font-medium">Feedback Delayed</span>
                            @endif
                        </div>

                        <div class="p-5 space-y-6">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-between">
                                <div>
                                    <div class="text-xs text-slate-500">Attempt Score</div>
                                    <div class="text-2xl font-bold text-slate-900 mt-0.5">
                                        @if(!$exam->show_results)
                                            <span class="text-sm font-normal text-slate-500">Results are hidden by instructor.</span>
                                        @elseif($exam->feedback_type === 'delayed' && !$exam->results_released)
                                            <span class="text-sm font-normal text-slate-500">Pending release by instructor.</span>
                                        @else
                                            {{ $selectedAttempt->score ?? 0 }} <span class="text-sm font-normal text-slate-400">/ {{ $exam->max_score }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right text-xs text-slate-500">
                                    <div>Status</div>
                                    <div class="font-semibold text-slate-900 text-sm mt-0.5">{{ $attemptStatusLabel }}</div>
                                    <div class="mt-2">Time Taken</div>
                                    <div class="font-semibold text-slate-900 text-sm mt-0.5">
                                        {{ $formatDuration($selectedAttempt->started_at, $selectedAttempt->submitted_at) }}
                                    </div>
                                </div>
                            </div>

                            @if($canShowResults)
                                <div class="space-y-4">
                                    <h3 class="font-semibold text-slate-800 text-sm">Question Review</h3>

                                    @foreach($selectedAttempt->answers as $index => $answer)
                                        @php
                                            $q = $answer->question;
                                            $maxPts = (int) ($q->points ?? 0);
                                            $earned = $answer->points_earned;
                                            $isEssay = $q->isEssay();
                                            $isManual = $isEssay || $q->isShortAnswer();

                                            if ($earned === null) {
                                                $badge = ['label' => 'Pending Review', 'cls' => 'bg-amber-100 text-amber-700'];
                                            } elseif ($isManual) {
                                                if ($earned === $maxPts && $maxPts > 0) {
                                                    $badge = ['label' => 'Manually Graded', 'cls' => 'bg-indigo-100 text-indigo-700'];
                                                } elseif ($earned === 0) {
                                                    $badge = ['label' => 'Manually Graded', 'cls' => 'bg-indigo-100 text-indigo-700'];
                                                } else {
                                                    $badge = ['label' => 'Partial', 'cls' => 'bg-blue-100 text-blue-700'];
                                                }
                                            } elseif ($earned === $maxPts && $maxPts > 0) {
                                                $badge = ['label' => 'Correct', 'cls' => 'bg-green-100 text-green-700'];
                                            } elseif ($earned === 0) {
                                                $badge = ['label' => 'Incorrect', 'cls' => 'bg-red-100 text-red-700'];
                                            } else {
                                                $badge = ['label' => 'Partial', 'cls' => 'bg-blue-100 text-blue-700'];
                                            }
                                        @endphp

                                        <div class="border border-slate-200 rounded-lg p-4 space-y-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="font-medium text-sm text-slate-900">
                                                    <span class="text-slate-500 mr-1.5">{{ $index + 1 }}.</span>
                                                    {{ $q->question_text }}
                                                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                                        <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold">
                                                            {{ ucfirst(str_replace('_', ' ', $q->question_type)) }}
                                                        </span>
                                                        <span class="text-xs text-slate-500">{{ $maxPts }} pts possible</span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge['cls'] }}">
                                                        {{ $badge['label'] }}
                                                    </span>
                                                    <div class="text-xs font-semibold text-slate-700 mt-1.5">
                                                        {{ $earned !== null ? $earned : '—' }} / {{ $maxPts }} pts
                                                    </div>
                                                </div>
                                            </div>

                                            @if($q->isMultipleChoice() && $q->options)
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                                    @foreach($q->options as $key => $option)
                                                        @php
                                                            $isSelected = $answer->answer === $key;
                                                            $isRight = $q->correct_answer === $key;
                                                            $optionClass = 'bg-slate-50 border-slate-200 text-slate-700';

                                                            if ($isSelected) {
                                                                $optionClass = $earned === $maxPts ? 'bg-green-50 border-green-300 text-green-800 font-medium' : 'bg-red-50 border-red-300 text-red-800 font-medium';
                                                            } elseif ($isRight) {
                                                                $optionClass = 'bg-green-50 border-green-200 text-green-700 font-medium';
                                                            }
                                                        @endphp
                                                        <div class="p-2 border rounded-lg flex items-center justify-between {{ $optionClass }}">
                                                            <span>{{ $key }}. {{ $option }}</span>
                                                            @if($isSelected && $earned === $maxPts)
                                                                <i data-lucide="check" class="h-4 w-4 text-green-600"></i>
                                                            @elseif($isSelected && $earned !== $maxPts)
                                                                <i data-lucide="x" class="h-4 w-4 text-red-600"></i>
                                                            @elseif($isRight)
                                                                <i data-lucide="check" class="h-4 w-4 text-green-500"></i>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @elseif($q->isTrueFalse())
                                                <div class="flex gap-4 text-sm">
                                                    @foreach(['true', 'false'] as $val)
                                                        @php
                                                            $isSelected = strtolower((string) $answer->answer) === $val;
                                                            $isRight = strtolower((string) $q->correct_answer) === $val;
                                                            $optionClass = 'bg-slate-50 border-slate-200 text-slate-700';

                                                            if ($isSelected) {
                                                                $optionClass = $earned === $maxPts ? 'bg-green-50 border-green-300 text-green-800 font-medium' : 'bg-red-50 border-red-300 text-red-800 font-medium';
                                                            } elseif ($isRight) {
                                                                $optionClass = 'bg-green-50 border-green-200 text-green-700 font-medium';
                                                            }
                                                        @endphp
                                                        <div class="px-4 py-2 border rounded-lg flex items-center gap-2 {{ $optionClass }}">
                                                            <span>{{ ucfirst($val) }}</span>
                                                            @if($isSelected && $earned === $maxPts)
                                                                <i data-lucide="check" class="h-4 w-4 text-green-600"></i>
                                                            @elseif($isSelected && $earned !== $maxPts)
                                                                <i data-lucide="x" class="h-4 w-4 text-red-600"></i>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-sm bg-slate-50 rounded-lg p-3 border border-slate-100">
                                                    <div class="text-xs text-slate-400">Your Answer:</div>
                                                    <div class="font-medium text-slate-800 mt-0.5 whitespace-pre-wrap">{{ $answer->answer ?: '[No answer submitted]' }}</div>

                                                    @if($earned === null)
                                                        <div class="text-xs text-amber-600 mt-2">Pending instructor review.</div>
                                                    @elseif($answer->feedback)
                                                        <div class="text-xs text-indigo-600 mt-2">Teacher Feedback: {{ $answer->feedback }}</div>
                                                    @endif

                                                    @if($q->correct_answer)
                                                        <div class="text-xs text-green-600 mt-2">Correct Answer: {{ $q->correct_answer }}</div>
                                                    @endif
                                                </div>
                                            @endif

                                            @if($q->explanation)
                                                <div class="bg-amber-50/50 border border-amber-100 rounded-lg p-3 text-xs text-slate-600 mt-1.5 flex items-start gap-2">
                                                    <i data-lucide="info" class="h-4 w-4 text-amber-600 mt-0.5 flex-shrink-0"></i>
                                                    <div>
                                                        <span class="font-semibold text-slate-700">Explanation:</span>
                                                        {{ $q->explanation }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center p-6 bg-slate-50 border border-slate-100 rounded-lg text-slate-500 text-sm">
                                    <i data-lucide="lock" class="h-8 w-8 text-slate-400 mx-auto mb-2"></i>
                                    Detailed question reviews and correct answers are hidden for this attempt.
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                    <h3 class="text-sm font-semibold text-slate-800">Exam Status</h3>

                    @php
                        $submittedAttempts = $attempts->whereIn('status', ['submitted', 'graded']);
                        $attemptsUsed = $submittedAttempts->count();
                        $attemptsAllowed = $exam->attempts_allowed ?? 1;
                        $hasAttemptsLeft = $attemptsUsed < $attemptsAllowed;

                        $isOpen = true;
                        $statusMessage = '';
                        $statusColor = 'text-green-600';

                        if ($exam->exam_date && $exam->exam_date->isFuture()) {
                            $isOpen = false;
                            $statusMessage = 'Not available yet (opens ' . $exam->exam_date->format('M j, g:i A') . ')';
                            $statusColor = 'text-amber-600';
                        } elseif ($exam->due_date && $exam->due_date->isPast()) {
                            $isOpen = false;
                            $statusMessage = 'Closed (due date was ' . $exam->due_date->format('M j, g:i A') . ')';
                            $statusColor = 'text-red-600';
                        } elseif (!$hasAttemptsLeft) {
                            $isOpen = false;
                            $statusMessage = 'No attempts remaining';
                            $statusColor = 'text-slate-600';
                        }
                    @endphp

                    <div class="space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">Attempts Used:</span>
                            <span class="font-semibold text-slate-800">{{ $attemptsUsed }} / {{ $attemptsAllowed }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">Availability:</span>
                            <span class="font-semibold {{ $statusColor }}">{{ $statusMessage ?: 'Available' }}</span>
                        </div>
                    </div>

                    @if($isOpen)
                        <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                                <i data-lucide="play" class="h-4 w-4 mr-2"></i>
                                @if($attemptsUsed > 0)
                                    Retake Exam
                                @else
                                    Start Exam
                                @endif
                            </button>
                        </form>
                    @else
                        <button class="w-full inline-flex items-center justify-center h-11 px-6 rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold cursor-not-allowed border border-slate-200" disabled>
                            <i data-lucide="lock" class="h-4 w-4 mr-2"></i>Start Exam
                        </button>
                    @endif
                </div>

                @if($attempts->count() > 0)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                            <h3 class="text-sm font-semibold text-slate-800">Your Attempts</h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach($attempts as $att)
                                <a href="{{ route('student.exams.show', ['exam' => $exam, 'attempt_id' => $att->id]) }}"
                                   class="block p-4 hover:bg-slate-50 transition-colors {{ ($selectedAttempt && $selectedAttempt->id === $att->id) ? 'bg-blue-50/50' : '' }}">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-semibold text-slate-700">Attempt #{{ $att->attempt_number }}</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                {{ $att->submitted_at ? 'Submitted ' . $att->submitted_at->diffForHumans() : 'In Progress' }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs font-semibold text-slate-900">
                                                @if(!$exam->show_results)
                                                    <span class="text-slate-400">Hidden</span>
                                                @elseif($exam->feedback_type === 'delayed' && !$exam->results_released)
                                                    <span class="text-purple-600 font-normal">Pending</span>
                                                @else
                                                    {{ $att->score ?? 0 }} pts
                                                @endif
                                            </div>
                                            <span class="text-[10px] text-[#0b2d6b] hover:underline font-medium block mt-0.5">View details</span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
