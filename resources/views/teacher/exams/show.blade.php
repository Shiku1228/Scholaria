@extends('layouts.teacher')

@section('content')
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 10px 40px rgba(31, 38, 135, 0.05);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s ease;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        .btn-outline {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s ease;
        }
        .btn-outline:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.05);
        }
        .animate-fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="relative min-h-screen bg-slate-50/50 p-4 sm:p-8 rounded-3xl">
        <!-- Ambient Background -->
        <div class="absolute inset-0 overflow-hidden rounded-3xl pointer-events-none z-0">
            <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-indigo-400/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-purple-400/10 rounded-full blur-3xl translate-y-1/3 -translate-x-1/4"></div>
        </div>

        <div class="relative z-10 max-w-6xl mx-auto space-y-12 pb-16">
            {{-- Premium Header --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 animate-fade-in-up">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-[2px] shadow-lg shadow-indigo-500/20 shrink-0">
                        <div class="h-full w-full bg-white rounded-xl flex items-center justify-center">
                            <i data-lucide="file-text" class="h-6 w-6 text-indigo-600"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-3 mb-1">
                            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $exam->title }}</h1>
                            @if($exam->isOnline())
                                <span class="px-3 py-1 bg-purple-100/80 text-purple-700 text-xs font-bold rounded-full border border-purple-200 shadow-sm flex items-center gap-1">
                                    <i data-lucide="laptop" class="h-3.5 w-3.5"></i>Online
                                </span>
                            @else
                                <span class="px-3 py-1 bg-amber-100/80 text-amber-700 text-xs font-bold rounded-full border border-amber-200 shadow-sm flex items-center gap-1">
                                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>Scheduled
                                </span>
                            @endif
                            @if($exam->is_published)
                                <span class="px-3 py-1 bg-emerald-100/80 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200 shadow-sm flex items-center gap-1">
                                    <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>Published
                                </span>
                            @else
                                <span class="px-3 py-1 bg-slate-100/80 text-slate-600 text-xs font-bold rounded-full border border-slate-200 shadow-sm flex items-center gap-1">
                                    <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>Draft
                                </span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <i data-lucide="book-open" class="h-4 w-4 text-indigo-400"></i>
                            {{ $exam->course->course_number ?? $exam->course->title }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('teacher.exams.edit', $exam) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl btn-outline text-sm font-bold text-slate-700 shadow-sm">
                        <i data-lucide="pencil" class="h-4.5 w-4.5 mr-2 text-indigo-500"></i>Edit Settings
                    </a>
                    @if($exam->isOnline())
                        <a href="{{ route('teacher.exams.questions', $exam) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl btn-primary text-white text-sm font-bold shadow-lg shadow-indigo-500/30">
                            <i data-lucide="list-checks" class="h-4.5 w-4.5 mr-2"></i>Questions
                        </a>
                    @endif
                    <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-white border border-rose-200 text-sm font-bold text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-colors shadow-sm" onclick="return confirm('Are you sure you want to delete this exam?')">
                            <i data-lucide="trash-2" class="h-4.5 w-4.5 mr-2"></i>Delete
                        </button>
                    </form>
                </div>
            </div>

            {{-- 4-Column Full Width Stats Row --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 animate-fade-in-up" style="animation-delay: 0.1s;">
                {{-- Date Card --}}
                <div class="glass-panel stat-card rounded-3xl p-5 bg-white/60 relative overflow-hidden flex items-center gap-4">
                    <div class="absolute -right-2 -bottom-2 opacity-5">
                        <i data-lucide="calendar-clock" class="h-20 w-20 text-amber-500"></i>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/30 text-white shrink-0">
                        <i data-lucide="calendar-clock" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Date</span>
                        <span class="text-lg font-extrabold text-slate-900 block mt-0.5 leading-tight">
                            @if($exam->exam_date)
                                {{ $exam->exam_date->format('M d, Y') }}
                            @else
                                None
                            @endif
                        </span>
                        @if($exam->exam_date)
                            <span class="text-xs font-semibold text-amber-600 block">{{ $exam->exam_date->format('g:i A') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Duration Card --}}
                <div class="glass-panel stat-card rounded-3xl p-5 bg-white/60 relative overflow-hidden flex items-center gap-4">
                    <div class="absolute -right-2 -bottom-2 opacity-5">
                        <i data-lucide="clock" class="h-20 w-20 text-blue-500"></i>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-400 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/30 text-white shrink-0">
                        <i data-lucide="clock" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Duration</span>
                        <span class="text-xl font-extrabold text-slate-900 block mt-0.5">{{ $exam->duration ?? 120 }} <span class="text-sm text-slate-500 font-semibold">mins</span></span>
                    </div>
                </div>

                {{-- Score Card --}}
                <div class="glass-panel stat-card rounded-3xl p-5 bg-white/60 relative overflow-hidden flex items-center gap-4">
                    <div class="absolute -right-2 -bottom-2 opacity-5">
                        <i data-lucide="target" class="h-20 w-20 text-emerald-500"></i>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center shadow-lg shadow-emerald-500/30 text-white shrink-0">
                        <i data-lucide="target" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Max Score</span>
                        <span class="text-xl font-extrabold text-slate-900 block mt-0.5">{{ $exam->max_score ?? 100 }} <span class="text-sm text-slate-500 font-semibold">pts</span></span>
                    </div>
                </div>
                
                {{-- Submissions Card --}}
                <div class="glass-panel stat-card rounded-3xl p-5 bg-white/60 relative overflow-hidden flex items-center gap-4">
                    <div class="absolute -right-2 -bottom-2 opacity-5">
                        <i data-lucide="users" class="h-20 w-20 text-indigo-500"></i>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center shadow-lg shadow-indigo-500/30 text-white shrink-0">
                        <i data-lucide="users" class="h-6 w-6"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Submissions</span>
                        <span class="text-xl font-extrabold text-slate-900 block mt-0.5">{{ $attempts->total() ?? $attempts->count() }} <span class="text-sm text-slate-500 font-semibold">total</span></span>
                    </div>
                </div>
            </div>

            {{-- Middle Row: 2 Columns for Info and Management --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 animate-fade-in-up" style="animation-delay: 0.2s;">
                
                {{-- Left: Details & Descriptions --}}
                <div class="glass-panel rounded-3xl overflow-hidden bg-white/60 h-full flex flex-col">
                    <div class="px-6 py-4 border-b border-slate-100/50 flex items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-lg text-slate-600">
                            <i data-lucide="align-left" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Exam Details</h3>
                    </div>
                    <div class="p-8 space-y-8 flex-1 text-sm text-slate-700">
                        @if($exam->description)
                            <div>
                                <h4 class="font-bold text-slate-900 mb-2 uppercase tracking-wider text-xs">Description</h4>
                                <p class="leading-relaxed">{{ $exam->description }}</p>
                            </div>
                        @endif
                        @if($exam->instructions)
                            <div>
                                <h4 class="font-bold text-slate-900 mb-2 uppercase tracking-wider text-xs">Instructions</h4>
                                <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl leading-relaxed">
                                    {{ $exam->instructions }}
                                </div>
                            </div>
                        @endif
                        @if($exam->location)
                            <div class="flex items-center gap-3 p-4 bg-rose-50/50 border border-rose-100 rounded-2xl text-rose-900">
                                <i data-lucide="map-pin" class="h-5 w-5 text-rose-500 shrink-0"></i>
                                <span class="font-medium"><strong>Location:</strong> {{ $exam->location }}</span>
                            </div>
                        @endif
                        
                        @if(!$exam->description && !$exam->instructions && !$exam->location)
                            <div class="flex items-center justify-center h-full text-slate-400 italic">
                                No additional details provided.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: Exam Management --}}
                @if($exam->isOnline())
                <div class="glass-panel rounded-3xl overflow-hidden bg-gradient-to-b from-indigo-50/50 to-white/50 h-full flex flex-col">
                    <div class="px-6 py-4 border-b border-indigo-100/50 flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                            <i data-lucide="settings" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Management & Rules</h3>
                    </div>
                    <div class="p-8 flex-1 flex flex-col">
                        @if(!$exam->hasQuestions())
                            <div class="mb-5 p-4 bg-amber-50/80 border border-amber-200 rounded-2xl">
                                <div class="flex items-start gap-3 text-amber-800">
                                    <i data-lucide="alert-triangle" class="h-5 w-5 shrink-0 mt-0.5"></i>
                                    <div class="text-sm">
                                        <span class="font-bold block mb-1">No questions added yet</span>
                                        <a href="{{ route('teacher.exams.questions', $exam) }}" class="underline font-semibold hover:text-amber-900">Add questions</a> to publish this exam.
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-3 text-sm mb-6">
                            <div class="p-3 bg-white/60 rounded-xl border border-slate-100 shadow-sm text-center">
                                <span class="block text-xl font-black text-indigo-700 mb-1">{{ $exam->questions->count() }}</span>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Questions Pool</span>
                            </div>
                            <div class="p-3 bg-white/60 rounded-xl border border-slate-100 shadow-sm text-center">
                                <span class="block text-xl font-black text-emerald-700 mb-1">{{ $exam->attempts_allowed ?? 1 }}</span>
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Max Attempts</span>
                            </div>
                        </div>

                        <div class="space-y-3 font-medium text-sm text-slate-600 mb-6 flex-1">
                            <div class="flex justify-between items-center p-2.5 rounded-lg bg-white/40 border border-slate-100">
                                <span class="flex items-center gap-2"><i data-lucide="clock" class="h-4 w-4 text-slate-400"></i>Due Date:</span>
                                <span class="font-bold text-slate-900">{{ $exam->due_date ? $exam->due_date->format('M d, Y g:i A') : 'No Due Date' }}</span>
                            </div>
                            <div class="flex justify-between items-center p-2.5 rounded-lg bg-white/40 border border-slate-100">
                                <span class="flex items-center gap-2"><i data-lucide="message-square" class="h-4 w-4 text-slate-400"></i>Feedback:</span>
                                <span class="font-bold text-slate-900">{{ ucfirst($exam->feedback_type ?? 'instant') }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-auto">
                            @if(!$exam->is_published)
                                <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center h-11 px-5 rounded-xl btn-success text-white text-sm font-bold shadow-lg shadow-emerald-500/30 disabled:opacity-50 disabled:cursor-not-allowed" {{ $exam->hasQuestions() ? '' : 'disabled' }}>
                                        <i data-lucide="check-circle" class="h-4.5 w-4.5 mr-2"></i>Publish Exam
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('teacher.exams.unpublish', $exam) }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center h-11 px-5 rounded-xl bg-slate-800 text-white text-sm font-bold hover:bg-slate-900 transition-colors shadow-lg">
                                        <i data-lucide="eye-off" class="h-4.5 w-4.5 mr-2"></i>Unpublish
                                    </button>
                                </form>
                            @endif

                            @if($exam->feedback_type === 'delayed' && !$exam->results_released)
                                <form method="POST" action="{{ route('teacher.exams.release-results', $exam) }}" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center h-11 px-5 rounded-xl bg-purple-600 text-white text-sm font-bold hover:bg-purple-700 transition-colors shadow-lg shadow-purple-500/30">
                                        <i data-lucide="unlock" class="h-4.5 w-4.5 mr-2"></i>Release Results
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('teacher.exams.edit', $exam) }}" class="w-full inline-flex items-center justify-center h-11 px-5 rounded-xl bg-white border border-slate-200 text-slate-700 text-sm font-bold hover:bg-slate-50 transition-colors shadow-sm">
                                    <i data-lucide="settings" class="h-4.5 w-4.5 mr-2 text-slate-400"></i>Settings
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @else
                {{-- Offline Exam Management placeholder if needed --}}
                <div class="glass-panel rounded-3xl overflow-hidden bg-gradient-to-b from-slate-50/50 to-white/50 h-full flex items-center justify-center p-8 text-center text-slate-500">
                    <div>
                        <div class="h-16 w-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="calendar" class="h-8 w-8 text-slate-400"></i>
                        </div>
                        <p class="text-sm font-medium">This is an offline, scheduled exam.</p>
                        <p class="text-xs mt-1">Online attempt tracking is disabled.</p>
                    </div>
                </div>
                @endif
            </div>

            {{-- Bottom Row: Full Width Student Attempts Table --}}
            <div class="glass-panel rounded-3xl overflow-hidden animate-fade-in-up" style="animation-delay: 0.3s;">
                <div class="px-6 py-5 border-b border-white/40 bg-white/40 flex flex-wrap items-center justify-between gap-4 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                            <i data-lucide="users" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800">Student Submissions Log</h3>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                            <input type="text" placeholder="Search students..." class="h-10 pl-9 pr-4 rounded-xl border border-slate-200 bg-white/50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white w-full sm:w-64 transition-all">
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto bg-white/50">
                    <table class="min-w-full text-sm whitespace-nowrap">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200/60 bg-slate-50/50">
                                <th class="py-4 px-6">Student Name</th>
                                <th class="py-4 px-6">Started At</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6">Final Score</th>
                                <th class="py-4 px-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100/60">
                            @forelse($attempts as $attempt)
                                <tr class="hover:bg-white/80 transition-colors group">
                                    <td class="py-4 px-6 font-bold text-slate-900 flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 border border-indigo-200 flex items-center justify-center text-indigo-700 font-bold text-xs">
                                            {{ substr($attempt->student->name ?? 'U', 0, 1) }}
                                        </div>
                                        {{ $attempt->student->name ?? 'Unknown' }}
                                    </td>
                                    <td class="py-4 px-6 text-slate-600 font-medium">
                                        {{ $attempt->started_at->format('M d, Y') }}
                                        <span class="text-xs text-slate-400 block">{{ $attempt->started_at->format('g:i A') }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($attempt->isGraded())
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                <i data-lucide="check-circle" class="h-3 w-3 mr-1.5"></i>Graded
                                            </span>
                                        @elseif($attempt->isSubmitted())
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                                <i data-lucide="inbox" class="h-3 w-3 mr-1.5"></i>Submitted
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                                <i data-lucide="loader" class="h-3 w-3 mr-1.5 animate-spin"></i>In Progress
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($attempt->score !== null)
                                            <div class="flex items-end gap-1">
                                                <span class="text-lg font-black text-indigo-900">{{ $attempt->score }}</span>
                                                <span class="text-xs font-bold text-slate-400 mb-0.5">/ {{ $attempt->max_score }}</span>
                                            </div>
                                        @else
                                            <span class="text-slate-400 font-bold tracking-widest">---</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <a href="#" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-600 hover:text-white transition-all opacity-0 group-hover:opacity-100 focus:opacity-100">
                                            Review Attempt
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-16 px-6 text-center">
                                        <div class="relative inline-block mb-4">
                                            <div class="absolute inset-0 bg-blue-400/20 blur-xl rounded-full"></div>
                                            <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center relative z-10 border border-blue-50 shadow-sm mx-auto">
                                                <i data-lucide="users" class="h-6 w-6 text-blue-400"></i>
                                            </div>
                                        </div>
                                        <h4 class="text-lg font-extrabold text-slate-800 mb-1">No Submissions Yet</h4>
                                        <p class="text-sm text-slate-500 max-w-sm mx-auto">Student attempts and scores will appear here once the exam is taken.</p>
                                        @if($exam->exam_date && $exam->exam_date->isFuture())
                                            <div class="mt-4 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-200 text-xs font-bold text-amber-700">
                                                <i data-lucide="calendar" class="h-4 w-4"></i>
                                                Scheduled for {{ $exam->exam_date->format('M d, Y \a\t g:i A') }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($attempts->hasPages())
                    <div class="px-6 py-4 border-t border-slate-200/60 bg-white/40 backdrop-blur-md">
                        {{ $attempts->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection
