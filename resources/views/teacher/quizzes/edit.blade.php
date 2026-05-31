@extends('layouts.teacher')

@section('content')
    <style>
        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .glow-hover:hover {
            box-shadow: 0 0 15px rgba(11, 45, 107, 0.08);
            border-color: #0b2d6b;
        }
        .glass-footer {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
        }
        
        /* High-Contrast Brand buttons - Bulletproof Fallback */
        .btn-brand-primary {
            background-color: #0b2d6b !important;
            color: #ffffff !important;
        }
        .btn-brand-primary:hover {
            background-color: #0a275c !important;
        }
        .btn-brand-secondary {
            background-color: #ffffff !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .btn-brand-secondary:hover {
            background-color: #f1f5f9 !important;
        }
    </style>

    {{-- Premium Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="h-11 w-11 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100 shadow-sm">
                    <i data-lucide="pencil" class="h-5 w-5 text-[#0b2d6b]"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Quiz Settings</h1>
                        @if($quiz->is_published ?? false)
                            <span class="text-xs font-bold bg-green-50 text-green-700 px-2.5 py-0.5 rounded-full border border-green-100">Published</span>
                        @else
                            <span class="text-xs font-bold bg-amber-50 text-amber-700 px-2.5 py-0.5 rounded-full border border-amber-100">Draft</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $quiz->course->course_number }} &bull; {{ $quiz->course->title }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('teacher.quizzes.show', $quiz) }}" 
            class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Quiz
        </a>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 animate-slide-in">
            <div class="flex gap-2.5">
                <i data-lucide="alert-circle" class="h-5 w-5 text-red-600 mt-0.5"></i>
                <div>
                    <h5 class="text-sm font-semibold text-red-900">Please correct the following errors:</h5>
                    <ul class="list-disc list-inside text-xs text-red-700 mt-1 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Form Container --}}
    <form method="POST" action="{{ route('teacher.quizzes.update', $quiz) }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- General Configuration Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden glow-hover transition-all">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                <i data-lucide="file-text" class="h-5 w-5 text-indigo-600"></i>
                <h3 class="text-sm font-bold text-slate-800">General Specifications</h3>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Quiz Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $quiz->title) }}" placeholder="e.g. Midterm Examination in Algebra"
                        class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm py-2.5" 
                        required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Instructions & Description</label>
                    <textarea name="description" rows="3" placeholder="Explain the quiz rules, allowed topics, or reminders..."
                        class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm resize-none py-2">{{ old('description', $quiz->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Scheduling & Duration Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden glow-hover transition-all">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                <i data-lucide="clock" class="h-5 w-5 text-amber-500"></i>
                <h3 class="text-sm font-bold text-slate-800">Timing & Schedule</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date</label>
                        <input type="datetime-local" name="start_date" 
                            value="{{ old('start_date', $quiz->start_date?->format('Y-m-d\TH:i')) }}" 
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        <span class="text-xs text-slate-400 mt-1 block">Leave empty for instant access.</span>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Due Date</label>
                        <input type="datetime-local" name="due_date" 
                            value="{{ old('due_date', $quiz->due_date?->format('Y-m-d\TH:i')) }}" 
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        <span class="text-xs text-slate-400 mt-1 block">Time window lock.</span>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Time Limit <span class="text-red-500">*</span></label>
                        <div class="relative rounded-xl shadow-sm">
                            <input type="number" name="time_limit" value="{{ old('time_limit', $quiz->time_limit ?? 15) }}" min="1" max="480"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm pr-12">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-slate-400 text-xs font-semibold">min</span>
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 mt-1 block">Auto-submits on expiry.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Advanced Rules Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden glow-hover transition-all">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                <i data-lucide="shield-check" class="h-5 w-5 text-emerald-600"></i>
                <h3 class="text-sm font-bold text-slate-800">Advanced Rules & Feedback Release</h3>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Attempts Allowed</label>
                        <input type="number" name="attempts_allowed" value="{{ old('attempts_allowed', $quiz->attempts_allowed ?? 1) }}" min="1" max="10"
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Feedback Mode</label>
                        <select name="feedback_type" class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                            <option value="instant" {{ old('feedback_type', $quiz->feedback_type) == 'instant' ? 'selected' : '' }}>Instant (Immediate Review)</option>
                            <option value="delayed" {{ old('feedback_type', $quiz->feedback_type) == 'delayed' ? 'selected' : '' }}>Delayed (Manual Release)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Random Subset Count</label>
                        <input type="number" name="random_subset_count" value="{{ old('random_subset_count', $quiz->random_subset_count) }}" placeholder="All questions"
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-4 border-t border-slate-100">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Maximum Score</label>
                        <input type="number" name="max_score" value="{{ old('max_score', $quiz->max_score ?? 100) }}" min="1"
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm bg-slate-50 font-bold text-slate-700">
                        <span class="text-xs text-slate-400 mt-1 block">Maximum point capacity of this exam.</span>
                    </div>
                    <div class="flex items-center h-full pt-6">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions', $quiz->shuffle_questions) ? 'checked' : '' }} 
                                class="rounded-lg border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5">
                            <div class="text-sm">
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Shuffle Question Order</span>
                                <p class="text-xs text-slate-400">Randomizes layout for each attempt.</p>
                            </div>
                        </label>
                    </div>
                    <div class="flex items-center h-full pt-6">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="show_results" value="1" {{ old('show_results', $quiz->show_results) ? 'checked' : '' }} 
                                class="rounded-lg border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5">
                            <div class="text-sm">
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Show Results to Students</span>
                                <p class="text-xs text-slate-400">Displays score and details after submission.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Premium Contained Actions Footer --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm py-4 px-6 mt-6 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('teacher.quizzes.show', $quiz) }}" 
                        class="text-xs font-semibold text-slate-400 hover:text-slate-655 hover:text-slate-600 transition-colors uppercase tracking-wider block">
                        Discard Changes
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('teacher.quizzes.questions', $quiz) }}" 
                        class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm btn-brand-secondary">
                        <i data-lucide="help-circle" class="h-4.5 w-4.5 mr-1.5 text-amber-500"></i>Manage Questions ({{ $quiz->questions()->count() }})
                    </a>

                    <button type="submit" 
                        class="inline-flex items-center justify-center h-10 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-bold hover:bg-[#0a275c] transition-all shadow-sm btn-brand-primary">
                        <i data-lucide="check" class="h-4.5 w-4.5 mr-1.5"></i>Save Settings Changes
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Premium Dashboard Stats Cards --}}
    <div class="border-t border-slate-200 pt-8 mt-4 animate-slide-in">
        <h4 class="text-sm font-bold text-slate-900 mb-4 tracking-tight">Active Quiz Performance Stats</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5">
            <!-- Stat Card 1 -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100">
                    <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Attempts</span>
                    <span class="text-xl font-extrabold text-slate-900 mt-0.5 block">{{ $quiz->attempts->count() }}</span>
                </div>
            </div>

            <!-- Stat Card 2 -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center border border-emerald-100">
                    <i data-lucide="check-circle" class="h-6 w-6 text-emerald-600"></i>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Completed</span>
                    <span class="text-xl font-extrabold text-slate-900 mt-0.5 block">{{ $quiz->attempts->whereNotNull('completed_at')->count() }}</span>
                </div>
            </div>

            <!-- Stat Card 3 -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-purple-50 flex items-center justify-center border border-purple-100">
                    <i data-lucide="user-check" class="h-6 w-6 text-purple-600"></i>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Unique Students</span>
                    <span class="text-xl font-extrabold text-slate-900 mt-0.5 block">{{ $quiz->attempts->pluck('student_id')->unique()->count() }}</span>
                </div>
            </div>

            <!-- Stat Card 4 -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-amber-50 flex items-center justify-center border border-amber-100">
                    <i data-lucide="target" class="h-6 w-6 text-amber-600"></i>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Average Score</span>
                    <span class="text-xl font-extrabold text-slate-900 mt-0.5 block">{{ round($quiz->attempts->whereNotNull('completed_at')->avg('score') ?? 0, 1) }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection
