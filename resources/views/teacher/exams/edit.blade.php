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
        .form-input-premium {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-input-premium:focus {
            background: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(124, 58, 237, 0.4);
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

        <div class="relative z-10 max-w-5xl mx-auto space-y-12 pb-24">
            {{-- Premium Header --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5 animate-fade-in-up">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 p-[2px] shadow-lg shadow-indigo-500/20">
                        <div class="h-full w-full bg-white rounded-xl flex items-center justify-center">
                            <i data-lucide="settings-2" class="h-6 w-6 text-indigo-600"></i>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Edit Exam Settings</h1>
                            @if($exam->is_published ?? false)
                                <span class="px-3 py-1 bg-emerald-100/80 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">Published</span>
                            @else
                                <span class="px-3 py-1 bg-amber-100/80 text-amber-700 text-xs font-bold rounded-full border border-amber-200">Draft</span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-slate-500 flex items-center gap-2">
                            <i data-lucide="book-open" class="h-4 w-4"></i>
                            {{ $exam->course->course_number }} &bull; {{ $exam->course->title }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('teacher.exams.show', $exam) }}" 
                    class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-200 bg-white/60 backdrop-blur text-sm font-bold text-slate-600 hover:bg-white hover:text-indigo-600 hover:shadow-md transition-all">
                    <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Exam
                </a>
            </div>

            {{-- Error Messages --}}
            @if($errors->any())
                <div class="glass-panel rounded-2xl p-5 border-l-4 border-l-rose-500 animate-fade-in-up" style="animation-delay: 0.1s;">
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                            <i data-lucide="alert-circle" class="h-4 w-4 text-rose-600"></i>
                        </div>
                        <div>
                            <h5 class="text-sm font-bold text-rose-900">Please correct the following errors:</h5>
                            <ul class="list-disc list-inside text-sm text-rose-700 mt-2 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Form Container --}}
            <form method="POST" action="{{ route('teacher.exams.update', $exam) }}" class="space-y-12">
                @csrf
                @method('PUT')

                {{-- General Configuration Card --}}
                <div class="glass-panel rounded-3xl overflow-hidden animate-fade-in-up" style="animation-delay: 0.2s;">
                    <div class="px-8 py-5 border-b border-white/40 bg-white/40 flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                            <i data-lucide="file-edit" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">General Information</h3>
                    </div>
                    <div class="p-8 sm:p-10 space-y-8 bg-white/50">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Exam Title <span class="text-rose-500">*</span></label>
                            <input type="text" name="title" value="{{ old('title', $exam->title) }}" placeholder="e.g. Final Examination in Advanced Mathematics"
                                class="w-full rounded-xl form-input-premium text-base py-3 px-4 outline-none" required>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Instructions & Description</label>
                            <textarea name="description" rows="4" placeholder="Explain the exam rules or reminders..."
                                class="w-full rounded-xl form-input-premium text-base resize-none py-3 px-4 outline-none">{{ old('description', $exam->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Scheduling & Duration Card --}}
                <div class="glass-panel rounded-3xl overflow-hidden animate-fade-in-up" style="animation-delay: 0.3s;">
                    <div class="px-8 py-5 border-b border-white/40 bg-white/40 flex items-center gap-3">
                        <div class="p-2 bg-amber-100 rounded-lg text-amber-600">
                            <i data-lucide="clock" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Timing & Availability</h3>
                    </div>
                    <div class="p-8 sm:p-10 bg-white/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Start Date & Time</label>
                                <input type="datetime-local" name="exam_date" 
                                    value="{{ old('exam_date', $exam->exam_date?->format('Y-m-d\TH:i')) }}" 
                                    class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none">
                                <span class="text-xs font-medium text-slate-500 mt-2 block">Leave empty for open access.</span>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Due Date</label>
                                <input type="datetime-local" name="due_date" 
                                    value="{{ old('due_date', $exam->due_date?->format('Y-m-d\TH:i')) }}" 
                                    class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none">
                                <span class="text-xs font-medium text-slate-500 mt-2 block">Late submissions blocked after this time.</span>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Time Limit (Duration) <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input type="number" name="duration" value="{{ old('duration', $exam->duration ?? 60) }}" min="1" max="480"
                                        class="w-full rounded-xl form-input-premium text-sm py-3 px-4 pr-16 outline-none font-bold text-indigo-900">
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none border-l border-slate-200 pl-3 my-2">
                                        <span class="text-slate-400 text-sm font-bold">mins</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Advanced Rules Card --}}
                <div class="glass-panel rounded-3xl overflow-hidden animate-fade-in-up" style="animation-delay: 0.4s;">
                    <div class="px-8 py-5 border-b border-white/40 bg-white/40 flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600">
                            <i data-lucide="shield-check" class="h-5 w-5"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">Exam Policies & Rules</h3>
                    </div>
                    <div class="p-8 sm:p-10 space-y-10 bg-white/50">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Allowed Attempts</label>
                                <input type="number" name="attempts_allowed" value="{{ old('attempts_allowed', $exam->attempts_allowed ?? 1) }}" min="1" max="10"
                                    class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Feedback Release</label>
                                <select name="feedback_type" class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[length:12px_12px] bg-[right_1rem_center]">
                                    <option value="instant" {{ old('feedback_type', $exam->feedback_type) == 'instant' ? 'selected' : '' }}>Instant Results</option>
                                    <option value="delayed" {{ old('feedback_type', $exam->feedback_type) == 'delayed' ? 'selected' : '' }}>Delayed / Manual Review</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Random Questions Pool</label>
                                <input type="number" name="random_subset_count" value="{{ old('random_subset_count', $exam->random_subset_count) }}" placeholder="Leave blank for all"
                                    class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-6 border-t border-slate-200/50">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Maximum Possible Score</label>
                                <input type="number" name="max_score" value="{{ old('max_score', $exam->max_score ?? 100) }}" min="1"
                                    class="w-full rounded-xl border border-indigo-200 bg-indigo-50/50 text-indigo-900 font-extrabold text-lg py-2.5 px-4 outline-none">
                                <span class="text-xs font-medium text-slate-500 mt-2 block">Total capacity of the exam.</span>
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-start gap-4 cursor-pointer group p-3 rounded-xl hover:bg-white/50 transition-colors w-full">
                                    <div class="relative flex items-center justify-center mt-0.5">
                                        <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions', $exam->shuffle_questions) ? 'checked' : '' }} 
                                            class="peer appearance-none w-6 h-6 border-2 border-slate-300 rounded-lg checked:bg-indigo-500 checked:border-indigo-500 transition-all outline-none focus:ring-4 focus:ring-indigo-500/20">
                                        <i data-lucide="check" class="absolute h-4 w-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity"></i>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Shuffle Questions</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Random order per student</span>
                                    </div>
                                </label>
                            </div>
                            <div class="flex items-center pt-5">
                                <label class="flex items-start gap-4 cursor-pointer group p-3 rounded-xl hover:bg-white/50 transition-colors w-full">
                                    <div class="relative flex items-center justify-center mt-0.5">
                                        <input type="checkbox" name="show_results" value="1" {{ old('show_results', $exam->show_results) ? 'checked' : '' }} 
                                            class="peer appearance-none w-6 h-6 border-2 border-slate-300 rounded-lg checked:bg-indigo-500 checked:border-indigo-500 transition-all outline-none focus:ring-4 focus:ring-indigo-500/20">
                                        <i data-lucide="check" class="absolute h-4 w-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity"></i>
                                    </div>
                                    <div>
                                        <span class="block text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Show Final Score</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">Revealed to student on submit</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="sticky bottom-6 z-50 mt-10">
                    <div class="glass-panel rounded-2xl shadow-2xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 bg-white/95 backdrop-blur-xl border border-indigo-50">
                        <a href="{{ route('teacher.exams.show', $exam) }}" 
                            class="text-sm font-bold text-slate-500 hover:text-rose-600 transition-colors px-4 py-2 rounded-lg hover:bg-rose-50 order-2 sm:order-1">
                            Cancel Changes
                        </a>
                        
                        <div class="flex flex-wrap items-center gap-3 order-1 sm:order-2 w-full sm:w-auto">
                            <a href="{{ route('teacher.exams.questions', $exam) }}" 
                                class="flex-1 sm:flex-none inline-flex items-center justify-center h-12 px-6 rounded-xl border-2 border-slate-200 bg-white text-slate-700 text-sm font-bold hover:border-indigo-200 hover:text-indigo-700 transition-all focus:ring-4 focus:ring-slate-500/10">
                                <i data-lucide="list-checks" class="h-5 w-5 mr-2 text-indigo-500"></i> Manage Questions ({{ $exam->questions()->count() }})
                            </a>

                            <button type="submit" 
                                class="flex-1 sm:flex-none inline-flex items-center justify-center h-12 px-8 rounded-xl btn-primary text-white text-sm font-bold shadow-lg shadow-indigo-500/30 focus:ring-4 focus:ring-indigo-500/40">
                                <i data-lucide="save" class="h-5 w-5 mr-2"></i> Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Premium Dashboard Stats Cards --}}
            <div class="pt-6 animate-fade-in-up" style="animation-delay: 0.5s;">
                <h4 class="text-base font-extrabold text-slate-800 mb-5 tracking-tight flex items-center gap-2">
                    <i data-lucide="bar-chart-2" class="h-5 w-5 text-indigo-500"></i> Performance Insights
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Stat 1 -->
                    <div class="glass-panel stat-card rounded-3xl p-6 bg-white/60 relative overflow-hidden">
                        <div class="absolute -right-2 -bottom-2 opacity-10">
                            <i data-lucide="users" class="h-16 w-16 text-indigo-500"></i>
                        </div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="h-10 w-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                                <i data-lucide="users" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Attempts</span>
                        </div>
                        <span class="text-3xl font-extrabold text-slate-900 block">{{ $exam->attempts->count() }}</span>
                    </div>

                    <!-- Stat 2 -->
                    <div class="glass-panel stat-card rounded-3xl p-6 bg-white/60 relative overflow-hidden">
                        <div class="absolute -right-2 -bottom-2 opacity-10">
                            <i data-lucide="check-circle" class="h-16 w-16 text-emerald-500"></i>
                        </div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                                <i data-lucide="check-circle" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Completed</span>
                        </div>
                        <span class="text-3xl font-extrabold text-slate-900 block">{{ $exam->attempts->whereNotNull('completed_at')->count() }}</span>
                    </div>

                    <!-- Stat 3 -->
                    <div class="glass-panel stat-card rounded-3xl p-6 bg-white/60 relative overflow-hidden">
                        <div class="absolute -right-2 -bottom-2 opacity-10">
                            <i data-lucide="user-check" class="h-16 w-16 text-purple-500"></i>
                        </div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="h-10 w-10 rounded-xl bg-purple-100 flex items-center justify-center text-purple-600">
                                <i data-lucide="user-check" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Students</span>
                        </div>
                        <span class="text-3xl font-extrabold text-slate-900 block">{{ $exam->attempts->pluck('student_id')->unique()->count() }}</span>
                    </div>

                    <!-- Stat 4 -->
                    <div class="glass-panel stat-card rounded-3xl p-6 bg-white/60 relative overflow-hidden">
                        <div class="absolute -right-2 -bottom-2 opacity-10">
                            <i data-lucide="target" class="h-16 w-16 text-amber-500"></i>
                        </div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="h-10 w-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                                <i data-lucide="target" class="h-5 w-5"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Avg Score</span>
                        </div>
                        <span class="text-3xl font-extrabold text-slate-900 block">{{ round($exam->attempts->whereNotNull('completed_at')->avg('score') ?? 0, 1) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
