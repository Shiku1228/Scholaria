@extends('layouts.teacher')

@section('content')
    <style>
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05);
        }
        .dark .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        .animated-bg {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .step-active {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
            border-color: transparent;
        }
        .step-inactive {
            background: rgba(255, 255, 255, 0.5);
            color: #64748b;
            border-color: #e2e8f0;
        }
        .form-input-premium {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s ease;
        }
        .form-input-premium:focus {
            background: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            transform: translateY(-1px);
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
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-10 animate-fade-in-up">
                <div>
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 text-white">
                            <i data-lucide="sparkles" class="h-6 w-6"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-indigo-900 to-purple-900 tracking-tight">Create New Exam</h1>
                            <p class="text-sm text-indigo-600/80 mt-1 font-medium flex items-center gap-2">
                                <i data-lucide="book-open" class="h-4 w-4"></i>
                                {{ $course->course_number }} &bull; {{ $course->title }}
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" 
                    class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-200/60 bg-white/50 backdrop-blur-sm text-sm font-semibold text-slate-700 hover:bg-white hover:shadow-md transition-all">
                    <i data-lucide="arrow-left" class="h-4 w-4 mr-2 text-indigo-500"></i>Back to Course
                </a>
            </div>

            {{-- Error Messages --}}
            @if($errors->any())
                <div class="glass-panel rounded-2xl p-5 mb-8 animate-fade-in-up border-l-4 border-l-rose-500" style="animation-delay: 0.1s;">
                    <div class="flex gap-3">
                        <div class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center shrink-0">
                            <i data-lucide="alert-triangle" class="h-4 w-4 text-rose-600"></i>
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

            {{-- Interactive Multi-Step Indicator Bar --}}
            <div class="glass-panel rounded-2xl p-6 mb-8 animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 max-w-3xl mx-auto relative">
                    <!-- Connector Line Background -->
                    <div class="hidden sm:block absolute top-1/2 left-12 right-12 h-1 bg-slate-200 rounded-full -translate-y-1/2 z-0"></div>
                    <!-- Connector Line Progress -->
                    <div class="hidden sm:block absolute top-1/2 left-12 w-0 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full -translate-y-1/2 z-0 transition-all duration-500" id="connector-line-progress"></div>

                    <!-- Step 1 Indicator -->
                    <button type="button" onclick="goToStep(1)" class="relative z-10 flex flex-col items-center gap-3 group focus:outline-none w-32">
                        <div id="step-badge-1" class="h-12 w-12 rounded-2xl step-active flex items-center justify-center text-lg font-bold transition-all duration-300 transform group-hover:scale-105">1</div>
                        <div class="text-center">
                            <span class="block text-xs font-bold text-indigo-500 uppercase tracking-wider mb-1">Step 1</span>
                            <span id="step-title-1" class="text-sm font-bold text-slate-800 transition-all">Exam Details</span>
                        </div>
                    </button>

                    <!-- Step 2 Indicator -->
                    <button type="button" onclick="goToStep(2)" class="relative z-10 flex flex-col items-center gap-3 group focus:outline-none w-32">
                        <div id="step-badge-2" class="h-12 w-12 rounded-2xl step-inactive border-2 flex items-center justify-center text-lg font-bold transition-all duration-300 transform group-hover:scale-105">2</div>
                        <div class="text-center">
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Step 2</span>
                            <span id="step-title-2" class="text-sm font-bold text-slate-400 transition-all">Questions</span>
                        </div>
                    </button>
                </div>
            </div>

            <form method="POST" action="{{ route('teacher.exams.store', $course) }}" id="exam-creation-form">
                @csrf

                {{-- ==================== STEP 1: EXAM DETAILS ==================== --}}
                <div id="step-content-1" class="space-y-6 animate-fade-in-up" style="animation-delay: 0.3s;">
                    {{-- General Configuration Card --}}
                    <div class="glass-panel rounded-3xl overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="px-8 py-5 border-b border-white/20 bg-white/40 flex items-center gap-3 backdrop-blur-md">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i data-lucide="edit-3" class="h-5 w-5 text-indigo-600"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">General Specifications</h3>
                        </div>
                        <div class="p-8 sm:p-10 space-y-8 bg-white/50">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Exam Title <span class="text-rose-500">*</span></label>
                                <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Final Examination in Advanced Mathematics"
                                    class="w-full rounded-xl form-input-premium text-base py-3 px-4 outline-none" 
                                    required>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Instructions & Guidelines</label>
                                <textarea name="instructions" rows="4" placeholder="Explain the exam rules, allowed topics, or reminders to your students..."
                                    class="w-full rounded-xl form-input-premium text-base resize-none py-3 px-4 outline-none">{{ old('instructions') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Scheduling & Duration Card --}}
                    <div class="glass-panel rounded-3xl overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="px-8 py-5 border-b border-white/20 bg-white/40 flex items-center gap-3 backdrop-blur-md">
                            <div class="p-2 bg-amber-100 rounded-lg">
                                <i data-lucide="calendar-clock" class="h-5 w-5 text-amber-600"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">Timing & Schedule</h3>
                        </div>
                        <div class="p-8 sm:p-10 bg-white/50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div class="relative group">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Start Date & Time</label>
                                    <input type="datetime-local" name="exam_date" value="{{ old('exam_date') }}" 
                                        class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none cursor-pointer">
                                    <div class="absolute -bottom-6 left-0 text-xs text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">Leave empty for instant access.</div>
                                </div>
                                <div class="relative group">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Due Date</label>
                                    <input type="datetime-local" name="due_date" value="{{ old('due_date') }}" 
                                        class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none cursor-pointer">
                                    <div class="absolute -bottom-6 left-0 text-xs text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">Time window lock.</div>
                                </div>
                                <div class="relative group">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Duration <span class="text-rose-500">*</span></label>
                                    <div class="relative rounded-xl shadow-sm">
                                        <input type="number" name="duration" value="{{ old('duration', 60) }}" min="1" max="480"
                                            class="w-full rounded-xl form-input-premium text-sm py-3 px-4 pr-16 outline-none font-semibold text-indigo-900">
                                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none border-l border-slate-200 pl-3 my-2">
                                            <span class="text-slate-400 text-sm font-bold">mins</span>
                                        </div>
                                    </div>
                                    <div class="absolute -bottom-6 left-0 text-xs text-slate-500 opacity-0 group-hover:opacity-100 transition-opacity">Auto-submits on expiry.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sophisticated Reveal Rules Card --}}
                    <div class="glass-panel rounded-3xl overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="px-8 py-5 border-b border-white/20 bg-white/40 flex items-center gap-3 backdrop-blur-md">
                            <div class="p-2 bg-emerald-100 rounded-lg">
                                <i data-lucide="shield-check" class="h-5 w-5 text-emerald-600"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">Advanced Rules & Feedback</h3>
                        </div>
                        <div class="p-8 sm:p-10 space-y-10 bg-white/50">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Attempts Allowed</label>
                                    <input type="number" name="attempts_allowed" value="{{ old('attempts_allowed', 1) }}" min="1" max="10"
                                        class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Feedback Mode</label>
                                    <select name="feedback_type" class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[length:12px_12px] bg-[right_1rem_center]">
                                        <option value="instant" {{ old('feedback_type') == 'instant' ? 'selected' : '' }}>Instant (Immediate Review)</option>
                                        <option value="delayed" {{ old('feedback_type') == 'delayed' ? 'selected' : '' }}>Delayed (Manual Release)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Random Subset Count</label>
                                    <input type="number" name="random_subset_count" value="{{ old('random_subset_count') }}" placeholder="All questions"
                                        class="w-full rounded-xl form-input-premium text-sm py-3 px-4 outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pt-6 border-t border-slate-200/60">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Maximum Score</label>
                                    <input type="number" name="max_score" id="max_score_input" value="{{ old('max_score', 100) }}" min="1"
                                        class="w-full rounded-xl border border-indigo-100 bg-indigo-50/50 text-indigo-900 font-extrabold text-lg py-2.5 px-4 outline-none shadow-inner" readonly>
                                    <span class="text-xs text-slate-500 mt-2 block font-medium">Calculated dynamically from questions.</span>
                                </div>
                                <div class="flex items-center pt-5">
                                    <label class="flex items-start gap-4 cursor-pointer group p-3 rounded-xl hover:bg-white/50 transition-colors w-full">
                                        <div class="relative flex items-center justify-center mt-0.5">
                                            <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions') ? 'checked' : '' }} 
                                                class="peer appearance-none w-6 h-6 border-2 border-slate-300 rounded-lg checked:bg-indigo-500 checked:border-indigo-500 transition-all outline-none focus:ring-4 focus:ring-indigo-500/20">
                                            <i data-lucide="check" class="absolute h-4 w-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity"></i>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Shuffle Order</span>
                                            <span class="block text-xs text-slate-500 mt-0.5">Randomizes question sequence</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="flex items-center pt-5">
                                    <label class="flex items-start gap-4 cursor-pointer group p-3 rounded-xl hover:bg-white/50 transition-colors w-full">
                                        <div class="relative flex items-center justify-center mt-0.5">
                                            <input type="checkbox" name="show_results" value="1" {{ old('show_results', '1') ? 'checked' : '' }} 
                                                class="peer appearance-none w-6 h-6 border-2 border-slate-300 rounded-lg checked:bg-indigo-500 checked:border-indigo-500 transition-all outline-none focus:ring-4 focus:ring-indigo-500/20">
                                            <i data-lucide="check" class="absolute h-4 w-4 text-white opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity"></i>
                                        </div>
                                        <div>
                                            <span class="block text-sm font-bold text-slate-700 group-hover:text-indigo-700 transition-colors">Show Results</span>
                                            <span class="block text-xs text-slate-500 mt-0.5">Reveal score after submission</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ==================== STEP 2: QUESTIONS ==================== --}}
                <div id="step-content-2" class="space-y-6 hidden">
                    {{-- Inline Question Builder Card --}}
                    <div class="glass-panel rounded-3xl overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <div class="px-8 py-5 border-b border-white/20 bg-white/40 flex items-center justify-between backdrop-blur-md">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                                    <i data-lucide="help-circle" class="h-6 w-6 text-white"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-800">Direct Questions Builder</h3>
                                    <p class="text-sm text-slate-500 font-medium mt-0.5">Draft custom questions specific to this exam.</p>
                                </div>
                            </div>
                            <button type="button" onclick="addQuestionField()" 
                                class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 hover:shadow-lg hover:-translate-y-0.5 transition-all focus:ring-4 focus:ring-indigo-500/30">
                                <i data-lucide="plus" class="h-5 w-5 mr-1.5"></i>Add Question
                            </button>
                        </div>

                        <div class="p-8 bg-white/60 min-h-[400px]">
                            <div id="questions-container" class="space-y-10">
                                {{-- Injected dynamically via Javascript --}}
                            </div>

                            {{-- Elegant Empty Placeholder --}}
                            <div id="no-questions-msg" class="flex flex-col items-center justify-center py-20 animate-fade-in-up">
                                <div class="relative">
                                    <div class="absolute inset-0 bg-indigo-400/20 blur-2xl rounded-full"></div>
                                    <div class="h-16 w-16 rounded-full bg-white flex items-center justify-center shadow-xl border border-indigo-50 relative z-10 mb-4 group-hover:scale-110 transition-transform">
                                        <i data-lucide="layers" class="h-8 w-8 text-indigo-400"></i>
                                    </div>
                                </div>
                                <h4 class="text-xl font-extrabold text-slate-800 mb-2">No questions created yet</h4>
                                <p class="text-sm text-slate-500 max-w-sm text-center mb-6 leading-relaxed">Start building your exam by adding custom questions. Click the button above to begin.</p>
                                <button type="button" onclick="addQuestionField()" class="text-indigo-600 font-bold hover:text-indigo-700 flex items-center gap-1.5 text-sm bg-indigo-50 px-4 py-2 rounded-lg hover:bg-indigo-100 transition-colors">
                                    <i data-lucide="plus-circle" class="h-4 w-4"></i> Add First Question
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="sticky bottom-6 z-50 mt-10">
                    <div class="glass-panel rounded-2xl shadow-2xl p-4 sm:p-5 flex flex-wrap items-center justify-between gap-4 bg-white/95 backdrop-blur-xl border border-indigo-50">
                        <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" 
                            class="text-sm font-bold text-slate-500 hover:text-rose-600 transition-colors px-4 py-2 rounded-lg hover:bg-rose-50 order-2 sm:order-1">
                            Cancel Draft
                        </a>
                        
                        <div class="flex flex-wrap items-center gap-3 order-1 sm:order-2 w-full sm:w-auto">
                            <button type="button" onclick="goToStep(1)" id="prev-btn" 
                                class="hidden flex-1 sm:flex-none inline-flex items-center justify-center h-12 px-6 rounded-xl border-2 border-indigo-100 bg-white text-indigo-700 text-sm font-bold hover:bg-indigo-50 hover:border-indigo-200 transition-all focus:ring-4 focus:ring-indigo-500/20">
                                <i data-lucide="chevron-left" class="h-5 w-5 mr-1"></i> Back
                            </button>

                            <button type="button" onclick="goToStep(2)" id="next-btn" 
                                class="flex-1 sm:flex-none inline-flex items-center justify-center h-12 px-8 rounded-xl btn-primary text-white text-sm font-bold shadow-lg shadow-indigo-500/30 focus:ring-4 focus:ring-indigo-500/40">
                                Proceed to Questions <i data-lucide="arrow-right" class="h-5 w-5 ml-2"></i>
                            </button>

                            <button type="submit" id="submit-btn" 
                                class="hidden flex-1 sm:flex-none inline-flex items-center justify-center h-12 px-8 rounded-xl btn-success text-white text-sm font-bold shadow-lg shadow-emerald-500/30 focus:ring-4 focus:ring-emerald-500/40">
                                <i data-lucide="check-circle" class="h-5 w-5 mr-2"></i> Publish Exam
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Interactive Multi-Step Form Logic and Dynamic Question Layout --}}
    <script>
        let currentStep = 1;
        let questionCounter = 0;

        function goToStep(step) {
            if (step === 2) {
                const title = document.querySelector('input[name="title"]');
                if (!title || !title.value.trim()) {
                    title.classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
                    setTimeout(() => title.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200'), 2000);
                    title.focus();
                    return;
                }
            }

            currentStep = step;

            // Content Visibility with animation classes
            const step1 = document.getElementById('step-content-1');
            const step2 = document.getElementById('step-content-2');
            
            if (step === 1) {
                step2.classList.add('hidden');
                step2.classList.remove('animate-fade-in-up');
                step1.classList.remove('hidden');
                // Force reflow
                void step1.offsetWidth;
                step1.classList.add('animate-fade-in-up');
            } else {
                step1.classList.add('hidden');
                step1.classList.remove('animate-fade-in-up');
                step2.classList.remove('hidden');
                void step2.offsetWidth;
                step2.classList.add('animate-fade-in-up');
            }

            // Buttons
            document.getElementById('prev-btn').classList.toggle('hidden', step !== 2);
            document.getElementById('next-btn').classList.toggle('hidden', step !== 1);
            document.getElementById('submit-btn').classList.toggle('hidden', step !== 2);

            // Indicators
            const badge1 = document.getElementById('step-badge-1');
            const badge2 = document.getElementById('step-badge-2');
            const title1 = document.getElementById('step-title-1');
            const title2 = document.getElementById('step-title-2');
            const progress = document.getElementById('connector-line-progress');

            if (step === 1) {
                badge1.className = 'h-12 w-12 rounded-2xl step-active flex items-center justify-center text-lg font-bold transition-all duration-300 transform group-hover:scale-105';
                title1.className = 'text-sm font-bold text-indigo-700 transition-all';
                badge2.className = 'h-12 w-12 rounded-2xl step-inactive border-2 flex items-center justify-center text-lg font-bold transition-all duration-300 transform group-hover:scale-105';
                title2.className = 'text-sm font-bold text-slate-400 transition-all';
                if(progress) progress.style.width = '0%';
            } else {
                badge1.className = 'h-12 w-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-lg font-bold shadow-lg shadow-emerald-500/30 transition-all duration-300 transform group-hover:scale-105';
                badge1.innerHTML = '<i data-lucide="check" class="h-6 w-6"></i>';
                title1.className = 'text-sm font-bold text-emerald-600 transition-all';
                badge2.className = 'h-12 w-12 rounded-2xl step-active flex items-center justify-center text-lg font-bold transition-all duration-300 transform group-hover:scale-105';
                title2.className = 'text-sm font-bold text-indigo-700 transition-all';
                if(progress) progress.style.width = '100%';
                if (typeof window.lucide !== 'undefined') {
                    window.lucide.createIcons();
                }
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function addQuestionField() {
            questionCounter++;
            const container = document.getElementById('questions-container');
            const noQuestionsMsg = document.getElementById('no-questions-msg');

            if (noQuestionsMsg) noQuestionsMsg.classList.add('hidden');

            const questionDiv = document.createElement('div');
            questionDiv.className = 'bg-white rounded-2xl border border-slate-200/60 p-6 sm:p-8 shadow-sm hover:shadow-md transition-shadow duration-300 relative group animate-fade-in-up';
            questionDiv.id = `question-${questionCounter}`;
            questionDiv.innerHTML = `
                <div class="absolute -top-4 -left-4 h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-sm font-extrabold shadow-lg shadow-indigo-500/30 question-number z-10 border-2 border-white">
                    ${questionCounter}
                </div>
                
                <div class="flex items-start justify-end mb-4 absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button type="button" onclick="removeQuestion(${questionCounter})" class="h-8 w-8 bg-rose-50 text-rose-500 rounded-lg flex items-center justify-center hover:bg-rose-500 hover:text-white transition-colors" title="Delete Question">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="space-y-6 mt-2">
                    {{-- Question Text --}}
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Question Text <span class="text-rose-500">*</span></label>
                        <textarea name="questions[${questionCounter}][text]" rows="2" placeholder="What is the main concept of..." 
                            class="w-full rounded-xl form-input-premium text-base resize-none py-3 px-4" required></textarea>
                    </div>

                    {{-- Type & Points Row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Format</label>
                            <select name="questions[${questionCounter}][type]" onchange="updateQuestionType(${questionCounter})" 
                                class="w-full rounded-xl form-input-premium text-sm py-2.5 px-3">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True / False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Points</label>
                            <div class="relative">
                                <input type="number" name="questions[${questionCounter}][points]" value="1" min="1" max="100" 
                                    class="w-full rounded-xl form-input-premium text-sm py-2.5 px-3 pr-12 font-bold text-indigo-700" 
                                    onchange="updateTotalPoints()" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-bold">PTS</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Answers Selector Container --}}
                    <div id="options-${questionCounter}" class="space-y-3">
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Answer Choices &amp; Key</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="A" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5" required>
                                <span class="text-sm font-extrabold text-slate-400">A.</span>
                                <input type="text" name="questions[${questionCounter}][options][A]" placeholder="First choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                            </label>
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="B" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                                <span class="text-sm font-extrabold text-slate-400">B.</span>
                                <input type="text" name="questions[${questionCounter}][options][B]" placeholder="Second choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                            </label>
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="C" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                                <span class="text-sm font-extrabold text-slate-400">C.</span>
                                <input type="text" name="questions[${questionCounter}][options][C]" placeholder="Third choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                            </label>
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="D" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                                <span class="text-sm font-extrabold text-slate-400">D.</span>
                                <input type="text" name="questions[${questionCounter}][options][D]" placeholder="Fourth choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                            </label>
                        </div>
                    </div>

                    {{-- Detailed Explanation Field --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <i data-lucide="message-square-dashed" class="h-4 w-4 text-indigo-500"></i>
                            Explanation (Optional)
                        </label>
                        <input type="text" name="questions[${questionCounter}][explanation]" 
                            placeholder="Provide context for the correct answer to help students learn..." 
                            class="w-full rounded-xl form-input-premium text-sm py-3 px-4 bg-slate-50/50">
                    </div>
                </div>
            `;

            container.appendChild(questionDiv);

            if (typeof window.lucide !== 'undefined') {
                window.lucide.createIcons();
            }

            updateTotalPoints();
            renumberQuestions();
        }

        function removeQuestion(id) {
            const question = document.getElementById(`question-${id}`);
            if (question) {
                question.style.opacity = '0';
                question.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    question.remove();
                    updateTotalPoints();
                    renumberQuestions();

                    const container = document.getElementById('questions-container');
                    const noQuestionsMsg = document.getElementById('no-questions-msg');
                    if (container.children.length === 0 && noQuestionsMsg) {
                        noQuestionsMsg.classList.remove('hidden');
                    }
                }, 300);
            }
        }

        function renumberQuestions() {
            const questions = document.querySelectorAll('#questions-container > div');
            questions.forEach((q, index) => {
                const numEl = q.querySelector('.question-number');
                if (numEl) {
                    numEl.textContent = index + 1;
                }
            });
        }

        function updateTotalPoints() {
            let total = 0;
            const inputs = document.querySelectorAll('input[name^="questions["][name$="[points]"]');
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            const maxScoreField = document.getElementById('max_score_input');
            if (maxScoreField) {
                maxScoreField.value = total > 0 ? total : 100;
            }
        }

        function updateQuestionType(id) {
            const select = document.querySelector(`select[name="questions[${id}][type]"]`);
            const optionsDiv = document.getElementById(`options-${id}`);

            if (!select || !optionsDiv) return;

            if (select.value === 'true_false') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-3">Mark Correct Option</label>
                    <div class="flex gap-4">
                        <label class="flex-1 flex items-center justify-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-all focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-500">
                            <input type="radio" name="questions[${id}][correct]" value="true" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5" required>
                            <span class="text-sm font-bold text-slate-700">True</span>
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-all focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10 has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-500">
                            <input type="radio" name="questions[${id}][correct]" value="false" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                            <span class="text-sm font-bold text-slate-700">False</span>
                        </label>
                    </div>
                `;
            } else if (select.value === 'multiple_choice') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-3">Answer Choices &amp; Key</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                            <input type="radio" name="questions[${id}][correct]" value="A" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5" required>
                            <span class="text-sm font-extrabold text-slate-400">A.</span>
                            <input type="text" name="questions[${id}][options][A]" placeholder="First choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                        </label>
                        <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                            <input type="radio" name="questions[${id}][correct]" value="B" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                            <span class="text-sm font-extrabold text-slate-400">B.</span>
                            <input type="text" name="questions[${id}][options][B]" placeholder="Second choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                        </label>
                        <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                            <input type="radio" name="questions[${id}][correct]" value="C" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                            <span class="text-sm font-extrabold text-slate-400">C.</span>
                            <input type="text" name="questions[${id}][options][C]" placeholder="Third choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                        </label>
                        <label class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-100 bg-white cursor-pointer hover:border-indigo-300 transition-colors focus-within:border-indigo-500 focus-within:ring-4 focus-within:ring-indigo-500/10">
                            <input type="radio" name="questions[${id}][correct]" value="D" class="text-indigo-600 focus:ring-indigo-500 h-5 w-5">
                            <span class="text-sm font-extrabold text-slate-400">D.</span>
                            <input type="text" name="questions[${id}][options][D]" placeholder="Fourth choice" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 font-medium text-slate-700" required>
                        </label>
                    </div>
                `;
            } else {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider block mb-2">Expected Correct Answer Text</label>
                    <input type="text" name="questions[${id}][correct_answer]" placeholder="e.g. Paris (Used for auto-grading)" 
                        class="w-full rounded-xl form-input-premium text-sm py-3 px-4" />
                `;
            }
        }
    </script>
@endsection
