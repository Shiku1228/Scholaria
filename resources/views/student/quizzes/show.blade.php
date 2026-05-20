@extends('layouts.student')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $quiz->title }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $quiz->course->course_number }} • {{ $quiz->course->title }}</div>
        </div>
        <a href="{{ route('student.courses.show', ['course' => $quiz->course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 text-sm text-red-600">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left: Details and Action --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Quiz Info Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-semibold text-slate-800">Quiz Information</h2>
                </div>
                <div class="p-5 space-y-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-xs text-slate-500">Questions</div>
                            <div class="font-semibold text-slate-900 mt-0.5">
                                {{ $quiz->random_subset_count ?: $quiz->questions->count() }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Time Limit</div>
                            <div class="font-semibold text-slate-900 mt-0.5">{{ $quiz->time_limit ?? 15 }} min</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Total Points</div>
                            <div class="font-semibold text-slate-900 mt-0.5">{{ $quiz->max_score ?? ($quiz->points ?? 0) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Attempts Allowed</div>
                            <div class="font-semibold text-slate-900 mt-0.5">{{ $quiz->attempts_allowed ?? 1 }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100 text-sm">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                            <span class="text-slate-600">Available:</span>
                            <span class="font-medium text-slate-900">
                                {{ $quiz->start_date ? $quiz->start_date->format('M j, Y g:i A') : 'Immediately' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-days" class="h-4 w-4 text-slate-400"></i>
                            <span class="text-slate-600">Due Date:</span>
                            <span class="font-medium text-slate-900">
                                {{ $quiz->due_date ? $quiz->due_date->format('M j, Y g:i A') : 'No due date' }}
                            </span>
                        </div>
                    </div>

                    @if($quiz->description)
                        <div class="pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Instructions</h3>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ $quiz->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Selected Attempt Review --}}
            @if($selectedAttempt)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-800">
                            Review Attempt #{{ $selectedAttempt->attempt_number }} 
                            <span class="text-xs font-normal text-slate-500 ml-1">
                                (Submitted {{ $selectedAttempt->submitted_at?->format('M j, Y g:i A') }})
                            </span>
                        </h2>
                        
                        @if($quiz->feedback_type === 'delayed' && !$quiz->results_released)
                            <span class="text-xs bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full font-medium">Feedback Delayed</span>
                        @endif
                    </div>

                    <div class="p-5 space-y-6">
                        {{-- Score Feedback Card --}}
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-between">
                            <div>
                                <div class="text-xs text-slate-500">Attempt Score</div>
                                <div class="text-2xl font-bold text-slate-900 mt-0.5">
                                    @if(!$quiz->show_results)
                                        <span class="text-sm font-normal text-slate-500">Results are hidden by instructor.</span>
                                    @elseif($quiz->feedback_type === 'delayed' && !$quiz->results_released)
                                        <span class="text-sm font-normal text-slate-500">Pending release by instructor.</span>
                                    @else
                                        {{ $selectedAttempt->score ?? 0 }} <span class="text-sm font-normal text-slate-400">/ {{ $quiz->max_score ?? 10 }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-xs text-slate-500">
                                <div>Time Taken</div>
                                <div class="font-semibold text-slate-900 text-sm mt-0.5">
                                    @if($selectedAttempt->started_at && $selectedAttempt->submitted_at)
                                        {{ $selectedAttempt->started_at->diffInMinutes($selectedAttempt->submitted_at) }} min
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Detailed Answers (Only shown if feedback released/instant AND show_results is true) --}}
                        @if($quiz->show_results && !($quiz->feedback_type === 'delayed' && !$quiz->results_released))
                            <div class="space-y-4">
                                <h3 class="font-semibold text-slate-800 text-sm">Question Review</h3>
                                
                                @foreach($selectedAttempt->answers as $index => $answer)
                                    @php $q = $answer->question; @endphp
                                    <div class="border border-slate-200 rounded-lg p-4 space-y-3">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="font-medium text-sm text-slate-900">
                                                <span class="text-slate-500 mr-1.5">{{ $index + 1 }}.</span>
                                                {{ $q->question_text }}
                                            </div>
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $answer->is_correct ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $answer->points_earned }} / {{ $q->points }} pts
                                            </span>
                                        </div>

                                        {{-- Options/Selected Answer Display --}}
                                        @if($q->isMultipleChoice() && $q->options)
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                                                @foreach($q->options as $key => $option)
                                                    @php
                                                        $isSelected = $answer->selected_answer === $key;
                                                        $isCorrect = $q->correct_answer === $key;
                                                        $optionClass = 'bg-slate-50 border-slate-200 text-slate-700';
                                                        
                                                        if ($isSelected) {
                                                            $optionClass = $answer->is_correct ? 'bg-green-50 border-green-300 text-green-800 font-medium' : 'bg-red-50 border-red-300 text-red-800 font-medium';
                                                        } elseif ($isCorrect) {
                                                            $optionClass = 'bg-green-50 border-green-200 text-green-700 font-medium';
                                                        }
                                                    @endphp
                                                    <div class="p-2 border rounded-lg flex items-center justify-between {{ $optionClass }}">
                                                        <span>{{ $key }}. {{ $option }}</span>
                                                        @if($isSelected && $answer->is_correct)
                                                            <i data-lucide="check" class="h-4 w-4 text-green-600"></i>
                                                        @elseif($isSelected && !$answer->is_correct)
                                                            <i data-lucide="x" class="h-4 w-4 text-red-600"></i>
                                                        @elseif($isCorrect)
                                                            <i data-lucide="check" class="h-4 w-4 text-green-500"></i>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @elseif($q->isTrueFalse())
                                            <div class="flex gap-4 text-sm">
                                                @foreach(['true', 'false'] as $val)
                                                    @php
                                                        $isSelected = strtolower((string)$answer->selected_answer) === $val;
                                                        $isCorrect = strtolower((string)$q->correct_answer) === $val;
                                                        $optionClass = 'bg-slate-50 border-slate-200 text-slate-700';
                                                        
                                                        if ($isSelected) {
                                                            $optionClass = $answer->is_correct ? 'bg-green-50 border-green-300 text-green-800 font-medium' : 'bg-red-50 border-red-300 text-red-800 font-medium';
                                                        } elseif ($isCorrect) {
                                                            $optionClass = 'bg-green-50 border-green-200 text-green-700 font-medium';
                                                        }
                                                    @endphp
                                                    <div class="px-4 py-2 border rounded-lg flex items-center gap-2 {{ $optionClass }}">
                                                        <span>{{ ucfirst($val) }}</span>
                                                        @if($isSelected && $answer->is_correct)
                                                            <i data-lucide="check" class="h-4 w-4 text-green-600"></i>
                                                        @elseif($isSelected && !$answer->is_correct)
                                                            <i data-lucide="x" class="h-4 w-4 text-red-600"></i>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-sm bg-slate-50 rounded-lg p-3 border border-slate-100">
                                                <div class="text-xs text-slate-400">Your Answer:</div>
                                                <div class="font-medium text-slate-800 mt-0.5">{{ $answer->selected_answer ?: '[No answer submitted]' }}</div>
                                                @if(!$answer->is_correct && $q->correct_answer)
                                                    <div class="text-xs text-green-600 mt-2">Correct Answer: {{ $q->correct_answer }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        {{-- Explanation --}}
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

        {{-- Right Column: Attempts and Actions --}}
        <div class="space-y-6">
            {{-- Take / Start Quiz --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <h3 class="text-sm font-semibold text-slate-800">Quiz Status</h3>
                
                @php
                    $submittedAttempts = $attempts->where('status', 'submitted');
                    $attemptsUsed = $submittedAttempts->count();
                    $attemptsAllowed = $quiz->attempts_allowed ?? 1;
                    $hasAttemptsLeft = $attemptsUsed < $attemptsAllowed;
                    
                    $isOpen = true;
                    $statusMessage = '';
                    $statusColor = 'text-green-600';
                    
                    if ($quiz->start_date && $quiz->start_date->isFuture()) {
                        $isOpen = false;
                        $statusMessage = 'Not available yet (opens ' . $quiz->start_date->format('M j, g:i A') . ')';
                        $statusColor = 'text-amber-600';
                    } elseif ($quiz->due_date && $quiz->due_date->isPast()) {
                        $isOpen = false;
                        $statusMessage = 'Closed (due date was ' . $quiz->due_date->format('M j, g:i A') . ')';
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
                    <form method="POST" action="{{ route('student.quizzes.start', $quiz) }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                            <i data-lucide="play" class="h-4 w-4 mr-2"></i>
                            @if($attemptsUsed > 0)
                                Retake Quiz
                            @else
                                Start Quiz
                            @endif
                        </button>
                    </form>
                @else
                    <button class="w-full inline-flex items-center justify-center h-11 px-6 rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold cursor-not-allowed border border-slate-200" disabled>
                        <i data-lucide="lock" class="h-4 w-4 mr-2"></i>Start Quiz
                    </button>
                @endif
            </div>

            {{-- Attempts History --}}
            @if($attempts->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h3 class="text-sm font-semibold text-slate-800">Your Attempts</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($attempts as $att)
                            <a href="{{ route('student.quizzes.show', ['quiz' => $quiz, 'attempt_id' => $att->id]) }}" 
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
                                            @if(!$quiz->show_results)
                                                <span class="text-slate-400">Hidden</span>
                                            @elseif($quiz->feedback_type === 'delayed' && !$quiz->results_released)
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
@endsection
