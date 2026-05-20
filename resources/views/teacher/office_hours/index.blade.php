@extends('layouts.teacher', ['title' => 'Office Hours & Q&A - ' . ($course->title ?? '')])

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

    $totalSessions = $officeHours->count();
    $activeSessionsCount = $officeHours->filter(function($session) {
        return now()->between($session->start_time, $session->end_time);
    })->count();
    
    $totalQuestions = 0;
    $answeredQuestionsCount = 0;
    foreach($officeHours as $session) {
        $totalQuestions += $session->questions->count();
        $answeredQuestionsCount += $session->questions->whereNotNull('answer')->count();
    }
    $pendingQuestionsCount = $totalQuestions - $answeredQuestionsCount;
    $completionRate = $totalQuestions > 0 ? round(($answeredQuestionsCount / $totalQuestions) * 100) : 100;
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
                <p class="mt-2 text-blue-100/90 font-medium">Live Q&A Board & Office Hours Management</p>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="toggleNewSessionForm()" class="inline-flex items-center gap-2 bg-white text-[#0b2d6b] font-bold px-5 py-3 rounded-xl shadow-md hover:bg-blue-50 transition-all active:scale-[0.98]">
                    <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                    Schedule Session
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Dashboard Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1 -->
        <div class="glass-card custom-shadow rounded-2xl p-6 flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 relative">
                @if($activeSessionsCount > 0)
                    <span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-emerald-500 rounded-full pulse-dot border-2 border-white"></span>
                @endif
                <i data-lucide="video" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Live Active Sessions</p>
                <h3 class="text-2xl font-bold mt-0.5 text-slate-800">{{ $activeSessionsCount }}</h3>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="glass-card custom-shadow rounded-2xl p-6 flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                <i data-lucide="help-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Questions Asked</p>
                <h3 class="text-2xl font-bold mt-0.5 text-slate-800">{{ $totalQuestions }}</h3>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="glass-card custom-shadow rounded-2xl p-6 flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                <i data-lucide="message-square-dashed" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Pending Answers</p>
                <h3 class="text-2xl font-bold mt-0.5 text-slate-800">{{ $pendingQuestionsCount }}</h3>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="glass-card custom-shadow rounded-2xl p-6 flex items-center gap-5">
            <div class="w-12 h-12 rounded-xl bg-violet-50 flex items-center justify-center text-violet-600">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Answer Rate</p>
                <h3 class="text-2xl font-bold mt-0.5 text-slate-800">{{ $completionRate }}%</h3>
            </div>
        </div>
    </div>

    <!-- Main Workspace Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Columns: Scheduled Sessions & Q&A Board -->
        <div class="lg:col-span-2 space-y-6">
            
            <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2 mb-2">
                <i data-lucide="list-collapse" class="w-5 h-5 text-blue-600"></i>
                Office Hours & Live Boards
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
                            <button onclick="filterQuestions('{{ $session->id }}', 'pending', this)" class="px-3 py-1.5 rounded-md hover:text-slate-800 transition-all focus:outline-none filter-tab-btn-{{ $session->id }}">
                                Unanswered ({{ $session->questions->whereNull('answer')->count() }})
                            </button>
                            <button onclick="filterQuestions('{{ $session->id }}', 'answered', this)" class="px-3 py-1.5 rounded-md hover:text-slate-800 transition-all focus:outline-none filter-tab-btn-{{ $session->id }}">
                                Answered ({{ $session->questions->whereNotNull('answer')->count() }})
                            </button>
                        </div>
                    </div>

                    <!-- Session Board Content -->
                    <div class="p-6">
                        <div id="board-{{ $session->id }}" class="space-y-4">
                            @forelse($session->questions as $question)
                                @php
                                    $studentName = $question->student->name ?? 'Student';
                                    $initial = strtoupper(substr(trim($studentName), 0, 1));
                                    $gradient = getGradient($studentName);
                                    $isAnswered = !empty($question->answer);
                                @endphp
                                
                                <div class="question-item-card p-5 rounded-xl border border-slate-100 custom-shadow transition-all duration-300 flex gap-4 bg-white" 
                                     data-session-id="{{ $session->id }}" 
                                     data-answered="{{ $isAnswered ? 'true' : 'false' }}">
                                    
                                    <!-- Avatar with dynamic initials gradient -->
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr {{ $gradient }} text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                                        {{ $initial }}
                                    </div>
                                    
                                    <!-- Question Content -->
                                    <div class="flex-1 space-y-3">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-bold text-slate-800 text-sm">{{ $studentName }}</h4>
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
                                                    Teacher's Answer
                                                </div>
                                                <p class="text-slate-800 text-sm leading-relaxed font-medium">{{ $question->answer }}</p>
                                            </div>
                                        @else
                                            <!-- Post Answer Field Form -->
                                            <div class="mt-3 pt-2">
                                                <form action="{{ route('teacher.office-hours.answer', [$course, $question]) }}" method="POST" class="space-y-2">
                                                    @csrf
                                                    <div class="relative rounded-xl border border-slate-200 overflow-hidden bg-slate-50/50 focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-100 transition-all">
                                                        <textarea name="answer" rows="2" class="w-full bg-transparent border-0 px-4 py-3 text-slate-800 text-sm focus:outline-none placeholder:text-slate-400 font-medium" placeholder="Write your professional response to guide the student..." required></textarea>
                                                    </div>
                                                    <div class="flex justify-end">
                                                        <button type="submit" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 active:scale-[0.97] text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-sm transition-all">
                                                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                                            Publish Response
                                                        </button>
                                                    </div>
                                                </form>
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
                                    <p class="text-xs text-slate-400 mt-1">Students will be able to submit their questions when they enter the Live Room.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="glass-card custom-shadow rounded-2xl p-8 text-center border-dashed border-2 border-slate-300">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-400 mb-4 shadow-inner">
                        <i data-lucide="calendar" class="w-8 h-8"></i>
                    </div>
                    <h4 class="font-bold text-slate-700 text-base">No active or past sessions</h4>
                    <p class="text-sm text-slate-400 mt-1 max-w-sm mx-auto">Get started by scheduling your very first Q&A session. Students will be notified and can join to ask questions.</p>
                    <button onclick="toggleNewSessionForm()" class="mt-4 inline-flex items-center gap-2 bg-[#0b2d6b] hover:bg-[#153f8a] text-white text-xs font-bold px-5 py-3 rounded-xl shadow-md transition-all">
                        <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                        Schedule First Session
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Right 1 Column: Sticky Schedule Form Panel -->
        <div class="lg:col-span-1">
            <div class="sticky top-6 space-y-6">
                <!-- Create Session Form Card -->
                <div id="newSessionFormCard" class="glass-card custom-shadow rounded-2xl overflow-hidden transition-all duration-300">
                    <div class="p-6 bg-[#0b2d6b] text-white flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar-plus" class="w-5 h-5 text-blue-200"></i>
                            <h3 class="font-bold text-base tracking-tight">Schedule Q&A Room</h3>
                        </div>
                    </div>
                    <div class="p-6 text-gray-900">
                        <form action="{{ route('teacher.office-hours.store', $course) }}" method="POST" class="space-y-5">
                            @csrf
                            <div>
                                <label for="title" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Session Title</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                        <i data-lucide="heading" class="w-4 h-4"></i>
                                    </span>
                                    <input type="text" name="title" id="title" class="custom-input pl-9 block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 text-sm focus:outline-none transition-all placeholder:text-slate-400 font-medium" placeholder="e.g. Midterm Q&A Review Session" required>
                                </div>
                            </div>
                            
                            <div>
                                <label for="start_time" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Start Date & Time</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                        <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                                    </span>
                                    <input type="datetime-local" name="start_time" id="start_time" class="custom-input pl-9 block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 text-sm focus:outline-none transition-all font-medium text-slate-700" required>
                                </div>
                            </div>

                            <div>
                                <label for="end_time" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">End Date & Time</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                                        <i data-lucide="calendar-check" class="w-4 h-4"></i>
                                    </span>
                                    <input type="datetime-local" name="end_time" id="end_time" class="custom-input pl-9 block w-full rounded-xl border border-slate-200 bg-slate-50/50 py-3 text-sm focus:outline-none transition-all font-medium text-slate-700" required>
                                </div>
                            </div>

                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-[#0b2d6b] hover:bg-blue-800 active:scale-[0.98] text-white font-bold py-3 px-4 rounded-xl shadow-md transition-all">
                                <i data-lucide="sparkles" class="w-4 h-4 text-blue-200"></i>
                                Launch Live Room
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Guidelines Card -->
                <div class="glass-card custom-shadow rounded-2xl p-6 bg-slate-50 border border-slate-200/60 space-y-4">
                    <div class="flex items-center gap-2 font-bold text-slate-700 text-sm">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600"></i>
                        Teaching Guidelines
                    </div>
                    <ul class="space-y-2.5 text-xs text-slate-500 font-medium leading-relaxed">
                        <li class="flex gap-2">
                            <span class="text-blue-500 font-bold">•</span>
                            <span>When active, students can post Q&A board questions instantly without refreshing.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-blue-500 font-bold">•</span>
                            <span>Filter by **Unanswered** questions to view queue priorities instantly.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="text-blue-500 font-bold">•</span>
                            <span>Providing clear answers helps direct all students asynchronously.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function toggleNewSessionForm() {
        const card = document.getElementById('newSessionFormCard');
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('ring-4', 'ring-blue-100');
            setTimeout(() => {
                card.classList.remove('ring-4', 'ring-blue-100');
            }, 1500);
        }
    }

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
        let visibleCount = 0;
        
        questionCards.forEach(card => {
            const isAnswered = card.getAttribute('data-answered') === 'true';
            
            if (filterType === 'all') {
                card.classList.remove('hidden');
                visibleCount++;
            } else if (filterType === 'pending') {
                if (!isAnswered) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            } else if (filterType === 'answered') {
                if (isAnswered) {
                    card.classList.remove('hidden');
                    visibleCount++;
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
