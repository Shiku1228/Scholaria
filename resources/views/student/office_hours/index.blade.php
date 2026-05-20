@extends('layouts.student', ['title' => 'Office Hours & Q&A - ' . ($course->title ?? '')])

@section('content')
@php
    function getGradient($name) {
        $firstChar = strtoupper(substr(trim($name ?? 'S'), 0, 1));
        $gradients = [
            'A' => 'from-pink-500 to-rose-500', 'B' => 'from-rose-500 to-red-500', 'C' => 'from-red-500 to-orange-500',
            'D' => 'from-orange-500 to-amber-500', 'E' => 'from-amber-500 to-yellow-500', 'F' => 'from-yellow-500 to-lime-500',
            'G' => 'from-lime-500 to-green-500', 'H' => 'from-green-500 to-emerald-500', 'I' => 'from-emerald-500 to-teal-500',
            'J' => 'from-teal-500 to-cyan-500', 'K' => 'from-cyan-500 to-sky-500', 'L' => 'from-sky-500 to-blue-500',
            'M' => 'from-blue-500 to-indigo-500', 'N' => 'from-indigo-500 to-violet-500', 'O' => 'from-violet-500 to-purple-500',
            'P' => 'from-purple-500 to-fuchsia-500', 'Q' => 'from-fuchsia-500 to-pink-500', 'R' => 'from-pink-500 to-rose-500',
            'S' => 'from-sky-400 to-blue-600', 'T' => 'from-teal-400 to-emerald-600', 'U' => 'from-orange-400 to-red-600',
            'V' => 'from-indigo-400 to-purple-600', 'W' => 'from-fuchsia-400 to-pink-600', 'X' => 'from-violet-400 to-indigo-600',
            'Y' => 'from-cyan-400 to-blue-600', 'Z' => 'from-emerald-400 to-teal-600'
        ];
        return $gradients[$firstChar] ?? 'from-blue-500 to-indigo-500';
    }
@endphp

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    .pulse-dot {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-breathing 2s infinite;
    }
    @keyframes pulse-breathing {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }
        70% {
            transform: scale(1);
            box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
        }
        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }
    .custom-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .custom-shadow {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.03), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
    }
    .hover-translate {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-translate:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.05), 0 8px 15px -6px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="space-y-8 max-w-7xl mx-auto py-2">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#0b2d6b] via-[#153f8a] to-[#1e58bd] p-8 text-white shadow-lg">
        <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="absolute -bottom-10 right-20 h-32 w-32 rounded-full bg-blue-400/10 blur-xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-xs font-semibold text-blue-100 backdrop-blur-md mb-3">
                    <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                    {{ $course->course_number ?? 'CS' }}
                </span>
                <h1 class="text-3xl font-extrabold tracking-tight">{{ $course->title }}</h1>
                <p class="mt-2 text-blue-100/90 font-medium">Join Live Office Hours & Get Your Questions Answered</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 bg-white/15 px-4 py-2.5 rounded-xl text-xs font-bold border border-white/10 backdrop-blur-md">
                    <i data-lucide="users" class="w-4 h-4 text-blue-200"></i>
                    Student Room Active
                </span>
            </div>
        </div>
    </div>

    <!-- Main Workspace Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Columns: active sessions & questions -->
        <div class="lg:col-span-2 space-y-6">
            
            <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2 mb-2">
                <i data-lucide="video" class="w-5 h-5 text-blue-600"></i>
                Q&A Rooms & Active Boards
            </h2>

            @forelse($officeHours as $session)
                @php
                    $isLive = now()->between($session->start_time, $session->end_time);
                    $isUpcoming = now()->lessThan($session->start_time);
                    $isEnded = now()->greaterThan($session->end_time);
                @endphp
                
                <div class="glass-card custom-shadow rounded-2xl overflow-hidden hover-translate border-l-4 
                    @if($isLive) border-l-emerald-500 @elseif($isUpcoming) border-l-blue-500 @else border-l-slate-300 @endif mb-6">
                    
                    <!-- Session Banner Header -->
                    <div class="p-6 bg-slate-50/50 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-slate-800">{{ $session->title }}</h3>
                                @if($isLive)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold pulse-dot">
                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                        LIVE NOW
                                    </span>
                                @elseif($isUpcoming)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-100">
                                        UPCOMING
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium border border-slate-200">
                                        ENDED
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4 text-xs font-medium text-slate-500">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-slate-400"></i>
                                    {{ $session->start_time->format('M j, Y') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3.5 h-3.5 text-slate-400"></i>
                                    {{ $session->start_time->format('g:i A') }} - {{ $session->end_time->format('g:i A') }}
                                </span>
                            </div>
                        </div>

                        <!-- Instant Board Filters -->
                        <div class="flex rounded-lg bg-slate-100 p-0.5 border border-slate-200 text-xs font-bold text-slate-600">
                            <button onclick="filterQuestions('{{ $session->id }}', 'all', this)" class="px-3 py-1.5 rounded-md bg-white text-slate-800 shadow-sm transition-all focus:outline-none filter-tab-btn-{{ $session->id }}">
                                All ({{ $session->questions->count() }})
                            </button>
                            <button onclick="filterQuestions('{{ $session->id }}', 'my', this)" class="px-3 py-1.5 rounded-md hover:text-slate-800 transition-all focus:outline-none filter-tab-btn-{{ $session->id }}">
                                My Questions ({{ $session->questions->where('student_id', auth()->id())->count() }})
                            </button>
                            <button onclick="filterQuestions('{{ $session->id }}', 'answered', this)" class="px-3 py-1.5 rounded-md hover:text-slate-800 transition-all focus:outline-none filter-tab-btn-{{ $session->id }}">
                                Answered ({{ $session->questions->whereNotNull('answer')->count() }})
                            </button>
                        </div>
                    </div>

                    <!-- Session Board Content -->
                    <div class="p-6 space-y-6">
                        
                        <!-- Post Question Form (If active/live or upcoming for asynchronous questions) -->
                        @if($isLive || $isUpcoming)
                            <div class="bg-gradient-to-r {{ $isLive ? 'from-emerald-50/70 to-teal-50/40 border-emerald-100' : 'from-blue-50 to-indigo-50/50 border-blue-100/80' }} p-5 rounded-2xl border custom-shadow space-y-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg {{ $isLive ? 'bg-emerald-600' : 'bg-blue-600' }} text-white flex items-center justify-center shadow-sm">
                                            <i data-lucide="{{ $isLive ? 'message-square-plus' : 'calendar-plus' }}" class="w-4 h-4"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-slate-800 text-sm">
                                                {{ $isLive ? 'Ask the Teacher a Question' : 'Ask a Question Ahead of Time (Asynchronous)' }}
                                            </h4>
                                            <p class="text-xs text-slate-500 mt-0.5 font-medium">
                                                {{ $isLive ? 'Your question will be instantly queued for live answers.' : 'Post your questions early! The teacher will address them during the live hour.' }}
                                            </p>
                                        </div>
                                    </div>
                                    @if($isUpcoming)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[#0b2d6b] bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                            <i data-lucide="sparkles" class="w-2.5 h-2.5"></i> ASYNC ACTIVE
                                        </span>
                                    @endif
                                </div>

                                <form action="{{ route('student.courses.office-hours.question.store', [$course, $session]) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="relative rounded-xl border border-slate-200 overflow-hidden bg-white focus-within:border-[#0b2d6b] focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                                        <textarea name="question" rows="2" class="w-full bg-transparent border-0 px-4 py-3 text-slate-800 text-sm focus:outline-none placeholder:text-slate-400 font-medium" placeholder="What part of the lecture, quiz, or project are you having trouble with? Be specific..." required></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" class="inline-flex items-center gap-1.5 {{ $isLive ? 'bg-emerald-700 hover:bg-emerald-800' : 'bg-[#0b2d6b] hover:bg-[#153f8a]' }} active:scale-[0.97] text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition-all">
                                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                            {{ $isLive ? 'Submit Question Live' : 'Submit Early Question' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <div id="board-{{ $session->id }}" class="space-y-4">
                            @forelse($session->questions as $question)
                                @php
                                    $studentName = $question->student->name ?? 'Student';
                                    $initial = strtoupper(substr(trim($studentName), 0, 1));
                                    $gradient = getGradient($studentName);
                                    $isAnswered = !empty($question->answer);
                                    $isMyQuestion = (int) $question->student_id === (int) auth()->id();
                                @endphp
                                
                                <div class="question-item-card p-5 rounded-xl border border-slate-100 custom-shadow transition-all duration-300 flex gap-4 bg-white" 
                                     data-session-id="{{ $session->id }}" 
                                     data-student-id="{{ $question->student_id }}"
                                     data-answered="{{ $isAnswered ? 'true' : 'false' }}">
                                    
                                    <!-- Avatar with dynamic initials gradient -->
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr {{ $gradient }} text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                        {{ $initial }}
                                    </div>
                                    
                                    <!-- Question Content -->
                                    <div class="flex-1 space-y-3">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-slate-800 text-sm">
                                                    {{ $studentName }}
                                                    @if($isMyQuestion)
                                                        <span class="text-xs text-blue-500 font-semibold">(You)</span>
                                                    @endif
                                                </h4>
                                                @if($isAnswered)
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                                        <i data-lucide="check" class="w-2.5 h-2.5"></i> ANSWERED
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                                                        <i data-lucide="clock-3" class="w-2.5 h-2.5"></i> PENDING
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                                <i data-lucide="history" class="w-3.5 h-3.5 text-slate-300"></i>
                                                {{ $question->created_at->diffForHumans() }}
                                            </span>
                                        </div>

                                        <p class="text-slate-700 text-sm leading-relaxed font-medium bg-slate-50/50 p-3 rounded-lg border border-slate-100">{{ $question->question }}</p>

                                        @if($isAnswered)
                                            <!-- Teacher Answer Block -->
                                            <div class="pl-4 border-l-4 border-emerald-500 bg-emerald-50/30 p-4 rounded-r-lg border border-emerald-100/50 space-y-1">
                                                <div class="flex items-center gap-1 text-xs font-bold text-emerald-800">
                                                    <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                                    Teacher's Response
                                                </div>
                                                <p class="text-slate-800 text-sm leading-relaxed font-medium">{{ $question->answer }}</p>
                                            </div>
                                        @else
                                            <div class="text-xs text-slate-400 font-medium italic flex items-center gap-1">
                                                <i data-lucide="hourglass" class="w-3.5 h-3.5 text-slate-300 animate-pulse"></i>
                                                Awaiting teacher's live explanation...
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-10">
                                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-3 shadow-inner">
                                        <i data-lucide="message-square" class="w-8 h-8"></i>
                                    </div>
                                    <h5 class="font-bold text-slate-700 text-sm">No questions asked yet</h5>
                                    @if($isLive)
                                        <p class="text-xs text-slate-400 mt-1">Be the first to submit a question in the field above!</p>
                                    @else
                                        <p class="text-xs text-slate-400 mt-1">This session is currently closed for submissions.</p>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="glass-card custom-shadow rounded-2xl p-8 text-center border-dashed border-2 border-slate-300">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-4 shadow-inner">
                        <i data-lucide="calendar-x" class="w-8 h-8"></i>
                    </div>
                    <h4 class="font-bold text-slate-700 text-base">No scheduled rooms at the moment</h4>
                    <p class="text-sm text-slate-400 mt-1 max-w-sm mx-auto">Your teacher hasn't scheduled any live Q&A or office hours yet. Keep an eye out for course updates!</p>
                </div>
            @endforelse
        </div>

        <!-- Right 1 Column: Sticky Info Box -->
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-6">
                <!-- Info/Rules Card -->
                <div class="glass-card custom-shadow rounded-2xl overflow-hidden">
                    <div class="p-6 bg-[#0b2d6b] text-white">
                        <div class="flex items-center gap-2">
                            <i data-lucide="help-circle" class="w-5 h-5 text-blue-200"></i>
                            <h3 class="font-bold text-base tracking-tight">Q&A Guidelines</h3>
                        </div>
                    </div>
                    <div class="p-6 text-gray-900 bg-slate-50/50 space-y-4">
                        <p class="text-xs text-slate-500 font-medium leading-relaxed">
                            Welcome to the course Q&A board! Here are quick guidelines to get optimal responses from your instructors:
                        </p>
                        
                        <div class="space-y-3">
                            <div class="flex gap-2.5">
                                <span class="w-5 h-5 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 text-xs font-bold">1</span>
                                <p class="text-xs text-slate-500 font-semibold leading-normal">Ask clear and highly specific questions.</p>
                            </div>
                            <div class="flex gap-2.5">
                                <span class="w-5 h-5 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 text-xs font-bold">2</span>
                                <p class="text-xs text-slate-500 font-semibold leading-normal">Ensure your question has not already been answered in the **Answered** tab filter.</p>
                            </div>
                            <div class="flex gap-2.5">
                                <span class="w-5 h-5 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 text-xs font-bold">3</span>
                                <p class="text-xs text-slate-500 font-semibold leading-normal">Follow up respectfully if the teacher requests clarifications.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Notification Card (Stunning Asynchronous Q&A highlight) -->
                <div class="relative overflow-hidden rounded-2xl p-6 bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#0f172a] text-white shadow-xl border border-blue-500/35 space-y-4">
                    <!-- Dynamic background glow elements -->
                    <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-blue-500/20 blur-2xl"></div>
                    <div class="absolute -left-10 -bottom-10 h-32 w-32 rounded-full bg-indigo-500/25 blur-2xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-tr from-blue-500 to-indigo-500 text-white shadow-md shadow-blue-500/20">
                                <i data-lucide="sparkles" class="w-4 h-4 text-yellow-300 animate-pulse"></i>
                            </span>
                            <h3 class="font-extrabold text-xs tracking-tight text-white uppercase tracking-wider">Asynchronous Q&A</h3>
                        </div>
                        
                        <p class="mt-3 text-xs text-slate-300 font-semibold leading-relaxed">
                            Cannot attend the live hour in person? No problem! 
                        </p>
                        
                        <div class="mt-3 p-3 rounded-xl bg-slate-800/40 border border-slate-700/50 space-y-2.5">
                            <p class="text-xs text-blue-250 font-semibold flex items-start gap-1.5 leading-normal">
                                <i data-lucide="help-circle" class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <span>You can post questions <strong class="text-white">ahead of time</strong> in any upcoming session!</span>
                            </p>
                            <p class="text-xs text-emerald-300 font-semibold flex items-start gap-1.5 leading-normal">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400 mt-0.5 flex-shrink-0"></i>
                                <span>Instructors will answer during the session, and you can view replies anytime.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    const currentStudentId = {{ auth()->id() }};

    function filterQuestions(sessionId, filterType, btn) {
        // Toggle Active Button Styles
        const buttons = document.querySelectorAll('.filter-tab-btn-' + sessionId);
        buttons.forEach(button => {
            button.classList.remove('bg-white', 'text-slate-800', 'shadow-sm');
            button.classList.add('hover:text-slate-800');
        });
        
        btn.classList.add('bg-white', 'text-slate-800', 'shadow-sm');
        btn.classList.remove('hover:text-slate-800');

        // Filter cards
        const questionCards = document.querySelectorAll('.question-item-card[data-session-id="' + sessionId + '"]');
        
        questionCards.forEach(card => {
            const isAnswered = card.getAttribute('data-answered') === 'true';
            const cardStudentId = parseInt(card.getAttribute('data-student-id'));
            
            if (filterType === 'all') {
                card.classList.remove('hidden');
            } else if (filterType === 'my') {
                if (cardStudentId === currentStudentId) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            } else if (filterType === 'answered') {
                if (isAnswered) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            }
        });
    }

    // Initialize lucide icons in page context after content rendering
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    });
</script>
@endsection
