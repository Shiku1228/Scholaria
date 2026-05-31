@extends('layouts.teacher')

@section('content')
    <style>
        .step-active {
            color: #0b2d6b;
            border-color: #0b2d6b;
        }
        .step-inactive {
            color: #94a3b8;
            border-color: #e2e8f0;
        }
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
            box-shadow: 0 0 15px rgba(11, 45, 107, 0.1);
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
        .btn-brand-success {
            background-color: #047857 !important; /* Solid premium emerald green for high contrast (WCAG AA compliant) */
            color: #ffffff !important;
        }
        .btn-brand-success:hover {
            background-color: #047857 !important; /* Dark emerald on hover */
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
                    <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create New Quiz</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $course->course_number }} &bull; {{ $course->title }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" 
            class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Course
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

    {{-- Interactive Multi-Step Indicator Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-8 shadow-sm">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 max-w-2xl mx-auto">
            <!-- Step 1 Indicator -->
            <button type="button" onclick="goToStep(1)" id="step-indicator-1" class="flex items-center gap-3 group focus:outline-none">
                <span id="step-badge-1" class="h-9 w-9 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-bold shadow-sm transition-all">1</span>
                <div class="text-left">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Step 1</span>
                    <span id="step-title-1" class="text-sm font-bold text-[#0b2d6b] transition-all">Quiz Details & Rules</span>
                </div>
            </button>

            <!-- Connector Line -->
            <div class="hidden sm:block flex-1 h-0.5 bg-slate-100 rounded-full" id="connector-line"></div>

            <!-- Step 2 Indicator -->
            <button type="button" onclick="goToStep(2)" id="step-indicator-2" class="flex items-center gap-3 group focus:outline-none">
                <span id="step-badge-2" class="h-9 w-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-sm font-bold transition-all">2</span>
                <div class="text-left">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Step 2</span>
                    <span id="step-title-2" class="text-sm font-bold text-slate-400 transition-all">Configure Questions</span>
                </div>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('teacher.quizzes.store', $course) }}" id="quiz-creation-form" class="space-y-6">
        @csrf

        {{-- ==================== STEP 1: QUIZ DETAILS ==================== --}}
        <div id="step-content-1" class="space-y-6 animate-slide-in">
            {{-- General Configuration Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="file-text" class="h-5 w-5 text-indigo-650 text-indigo-600"></i>
                    <h3 class="text-sm font-bold text-slate-800">General Specifications</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Quiz Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Midterm Examination in Algebra"
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm py-2.5" 
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Instructions & Description</label>
                        <textarea name="description" rows="3" placeholder="Explain the quiz rules, allowed topics, or reminders..."
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm resize-none py-2">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Scheduling & Duration Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="clock" class="h-5 w-5 text-amber-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Timing & Schedule</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date</label>
                            <input type="datetime-local" name="start_date" value="{{ old('start_date') }}" 
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                            <span class="text-xs text-slate-400 mt-1 block">Leave empty for instant access.</span>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Due Date</label>
                            <input type="datetime-local" name="due_date" value="{{ old('due_date') }}" 
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                            <span class="text-xs text-slate-400 mt-1 block">Time window lock.</span>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Time Limit <span class="text-red-500">*</span></label>
                            <div class="relative rounded-xl shadow-sm">
                                <input type="number" name="time_limit" value="{{ old('time_limit', 15) }}" min="1" max="480"
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

            {{-- Sophisticated Reveal Rules Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="shield-check" class="h-5 w-5 text-emerald-555 text-emerald-600"></i>
                    <h3 class="text-sm font-bold text-slate-800">Advanced Rules & Feedback Release</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Attempts Allowed</label>
                            <input type="number" name="attempts_allowed" value="{{ old('attempts_allowed', 1) }}" min="1" max="10"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Feedback Mode</label>
                            <select name="feedback_type" class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                                <option value="instant" {{ old('feedback_type') == 'instant' ? 'selected' : '' }}>Instant (Immediate Review)</option>
                                <option value="delayed" {{ old('feedback_type') == 'delayed' ? 'selected' : '' }}>Delayed (Manual Release)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Random Subset Count</label>
                            <input type="number" name="random_subset_count" value="{{ old('random_subset_count') }}" placeholder="All questions"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Maximum Score</label>
                            <input type="number" name="max_score" id="max_score_input" value="{{ old('max_score', 100) }}" min="1"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm bg-slate-50 font-bold text-slate-700" readonly>
                            <span class="text-xs text-slate-400 mt-1 block">Calculated from total question points.</span>
                        </div>
                        <div class="flex items-center h-full pt-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions') ? 'checked' : '' }} 
                                    class="rounded-lg border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5">
                                <div class="text-sm">
                                    <span class="font-semibold text-slate-700 group-hover:text-slate-900">Shuffle Question Order</span>
                                    <p class="text-xs text-slate-400">Randomizes layout for each attempt.</p>
                                </div>
                            </label>
                        </div>
                        <div class="flex items-center h-full pt-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="show_results" value="1" {{ old('show_results', '1') ? 'checked' : '' }} 
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
        </div>

        {{-- ==================== STEP 2: QUESTIONS ==================== --}}
        <div id="step-content-2" class="space-y-6 hidden animate-slide-in">
            {{-- Question Bank Selection Card --}}
            @if($questionBanks->count() > 0)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden glow-hover transition-all">
                    <div class="px-6 py-4 border-b border-purple-100 bg-purple-50/40 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i data-lucide="database" class="h-4.5 w-4.5 text-purple-655 text-purple-650 text-purple-600"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800">1. Import from Question Bank</h3>
                                <p class="text-xs text-slate-500">Pick reusable questions from your personal catalogs.</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Select Catalog / Question Bank</label>
                            <select name="question_bank_id" id="bank_selector" onchange="showBankQuestions(this.value)" 
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                                <option value="">-- Click to choose a bank --</option>
                                @foreach($questionBanks as $bank)
                                    <option value="{{ $bank->id }}" {{ old('question_bank_id') == $bank->id ? 'selected' : '' }}>
                                        {{ $bank->name }} ({{ $bank->questions->count() }} questions available)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Collapsible Bank Questions Checklist --}}
                        <div class="border border-slate-100 rounded-xl overflow-hidden hidden" id="bank-questions-container">
                            <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 text-xs font-bold text-slate-655 text-slate-600 flex items-center justify-between">
                                <span class="flex items-center gap-1.5"><i data-lucide="list-checks" class="h-4 w-4"></i>Select Questions</span>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="selectAllImportQuestions(true)" class="text-[#0b2d6b] hover:underline">Select All</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" onclick="selectAllImportQuestions(false)" class="text-slate-400 hover:underline">Deselect All</button>
                                </div>
                            </div>
                            <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto" id="bank-questions-list">
                                @foreach($questionBanks as $bank)
                                    <div class="bank-group hidden" id="bank-group-{{ $bank->id }}">
                                        @forelse($bank->questions as $q)
                                            <label class="flex items-start gap-3 p-3.5 hover:bg-slate-50 cursor-pointer transition-colors">
                                                <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" 
                                                    class="mt-1 rounded-md border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5" 
                                                    onchange="updateTotalPoints()">
                                                <div class="text-sm flex-1">
                                                    <p class="font-semibold text-slate-800 tracking-tight">{{ $q->question_text }}</p>
                                                    <div class="flex items-center gap-2.5 text-xs text-slate-500 mt-1.5">
                                                        <span class="px-2 py-0.5 rounded-full font-bold text-[10px] uppercase tracking-wider bg-slate-100 text-slate-600">
                                                            @if($q->question_type === 'multiple_choice')
                                                                <span class="text-blue-600 bg-blue-50 px-1 py-0.5 rounded-md">Multiple Choice</span>
                                                            @elseif($q->question_type === 'true_false')
                                                                <span class="text-emerald-600 bg-emerald-50 px-1 py-0.5 rounded-md">True / False</span>
                                                            @elseif($q->question_type === 'essay')
                                                                <span class="text-violet-600 bg-violet-50 px-1 py-0.5 rounded-md">Essay</span>
                                                            @else
                                                                <span class="text-amber-600 bg-amber-50 px-1 py-0.5 rounded-md">Short Answer</span>
                                                            @endif
                                                        </span>
                                                        <span class="bank-points text-slate-400" data-points="{{ $q->points }}">&bull; <strong class="text-slate-600">{{ $q->points }}</strong> pts</span>
                                                    </div>
                                                </div>
                                            </label>
                                        @empty
                                            <div class="p-6 text-center text-sm text-slate-400">This question bank is empty.</div>
                                        @endforelse
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Inline Question Builder Card --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center">
                            <i data-lucide="plus-circle" class="h-4.5 w-4.5 text-[#0b2d6b]"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">2. Direct Questions Builder</h3>
                            <p class="text-xs text-slate-500">Draft custom, non-bank questions specific to this quiz.</p>
                        </div>
                    </div>
                    <button type="button" onclick="addQuestionField()" 
                        class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#0b2d6b] text-white text-xs font-bold hover:bg-[#0a275c] transition-all shadow-sm">
                        <i data-lucide="plus" class="h-4 w-4 mr-1"></i>Add Inline Question
                    </button>
                </div>

                <div class="p-6">
                    <div id="questions-container" class="space-y-6">
                        {{-- Injected dynamically via Javascript --}}
                    </div>

                    {{-- Elegant Empty Placeholder --}}
                    <div id="no-questions-msg" class="text-center py-10">
                        <div class="h-14 w-14 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-3 border border-slate-100">
                            <i data-lucide="help-circle" class="h-6 w-6 text-slate-400"></i>
                        </div>
                        <h4 class="text-sm font-bold text-slate-800">No inline questions created yet</h4>
                        <p class="text-xs text-slate-400 max-w-xs mx-auto mt-1">Want to construct specific questions? Click the "+ Add Inline Question" button in the card header.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Premium Contained Navigation Bar --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm py-4 px-6 mt-6 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" 
                        class="text-xs font-semibold text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-wider block">
                        Cancel Quiz Draft
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="goToStep(1)" id="prev-btn" 
                        class="hidden inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all btn-brand-secondary">
                        <i data-lucide="chevron-left" class="h-4 w-4 mr-1"></i>Previous Setup
                    </button>

                    <button type="button" onclick="goToStep(2)" id="next-btn" 
                        class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-all shadow-sm btn-brand-primary">
                        Configure Questions &amp; Items<i data-lucide="chevron-right" class="h-4 w-4 ml-1"></i>
                    </button>

                    <button type="submit" id="submit-btn" 
                        class="hidden inline-flex items-center justify-center h-10 px-6 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 transition-all shadow-sm btn-brand-success">
                        <i data-lucide="check" class="h-4.5 w-4.5 mr-1.5"></i>Create &amp; Save Quiz
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Interactive Multi-Step Form Logic and Dynamic Question Layout --}}
    <script>
        let currentStep = 1;
        let questionCounter = 0;

        function goToStep(step) {
            if (step === 2) {
                // Quick client-side validator for Step 1
                const title = document.querySelector('input[name="title"]');
                if (!title || !title.value.trim()) {
                    alert('Please enter a Quiz Title first.');
                    title.focus();
                    return;
                }
            }

            currentStep = step;

            // Handle content block visibility
            document.getElementById('step-content-1').classList.toggle('hidden', step !== 1);
            document.getElementById('step-content-2').classList.toggle('hidden', step !== 2);

            // Handle back/forward footer buttons
            document.getElementById('prev-btn').classList.toggle('hidden', step !== 2);
            document.getElementById('next-btn').classList.toggle('hidden', step !== 1);
            document.getElementById('submit-btn').classList.toggle('hidden', step !== 2);

            // Update top progress indicators
            const badge1 = document.getElementById('step-badge-1');
            const badge2 = document.getElementById('step-badge-2');
            const title1 = document.getElementById('step-title-1');
            const title2 = document.getElementById('step-title-2');

            if (step === 1) {
                badge1.className = 'h-9 w-9 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-bold shadow-sm transition-all';
                title1.className = 'text-sm font-bold text-[#0b2d6b] transition-all';
                badge2.className = 'h-9 w-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-sm font-bold transition-all';
                title2.className = 'text-sm font-bold text-slate-400 transition-all';
            } else {
                badge1.className = 'h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold shadow-sm transition-all';
                title1.className = 'text-sm font-bold text-emerald-800 transition-all';
                badge2.className = 'h-9 w-9 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-bold shadow-sm transition-all';
                title2.className = 'text-sm font-bold text-[#0b2d6b] transition-all';
            }

            // Scroll smoothly to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function showBankQuestions(bankId) {
            const container = document.getElementById('bank-questions-container');
            if (!container) return;

            const groups = document.querySelectorAll('.bank-group');
            groups.forEach(g => g.classList.add('hidden'));

            if (bankId) {
                container.classList.remove('hidden');
                const activeGroup = document.getElementById(`bank-group-${bankId}`);
                if (activeGroup) {
                    activeGroup.classList.remove('hidden');
                }
            } else {
                container.classList.add('hidden');
                const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(c => c.checked = false);
            }
            updateTotalPoints();
        }

        function selectAllImportQuestions(checked) {
            const activeGroup = document.querySelector('.bank-group:not(.hidden)');
            if (activeGroup) {
                const checkboxes = activeGroup.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(c => c.checked = checked);
            }
            updateTotalPoints();
        }

        function addQuestionField() {
            questionCounter++;
            const container = document.getElementById('questions-container');
            const noQuestionsMsg = document.getElementById('no-questions-msg');

            if (noQuestionsMsg) noQuestionsMsg.classList.add('hidden');

            const questionDiv = document.createElement('div');
            questionDiv.className = 'bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4 animate-slide-in';
            questionDiv.id = `question-${questionCounter}`;
            questionDiv.innerHTML = `
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <div class="flex items-center gap-2.5">
                        <span class="h-7 w-7 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-xs font-bold question-number">${questionCounter}</span>
                        <span class="text-sm font-bold text-slate-800">Inline Question Details</span>
                    </div>
                    <button type="button" onclick="removeQuestion(${questionCounter})" class="text-slate-400 hover:text-red-600 transition-colors p-1">
                        <i data-lucide="trash-2" class="h-4.5 w-4.5"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    {{-- Question Text --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-655 text-slate-600 mb-1.5">Question Question Text *</label>
                        <textarea name="questions[${questionCounter}][text]" rows="2" placeholder="Write question details..." 
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none py-2" required></textarea>
                    </div>

                    {{-- Type & Points Row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-655 text-slate-600 mb-1.5">Question Type</label>
                            <select name="questions[${questionCounter}][type]" onchange="updateQuestionType(${questionCounter})" 
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True / False</option>
                                <option value="short_answer">Short Answer</option>
                                <option value="essay">Essay</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-655 text-slate-600 mb-1.5">Points *</label>
                            <div class="relative">
                                <input type="number" name="questions[${questionCounter}][points]" value="1" min="1" max="100" 
                                    class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm pr-12" 
                                    onchange="updateTotalPoints()" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-semibold">pts</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Answers Selector Container --}}
                    <div id="options-${questionCounter}" class="space-y-2">
                        <label class="text-xs font-bold text-slate-655 text-slate-600 block">Answer Choices &amp; Selection (Mark correct option)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="A" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5" required>
                                <span class="text-sm font-bold text-slate-655 text-slate-600">A.</span>
                                <input type="text" name="questions[${questionCounter}][options][A]" placeholder="Choice A" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1" required>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="B" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5">
                                <span class="text-sm font-bold text-slate-655 text-slate-600">B.</span>
                                <input type="text" name="questions[${questionCounter}][options][B]" placeholder="Choice B" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1" required>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="C" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5">
                                <span class="text-sm font-bold text-slate-655 text-slate-600">C.</span>
                                <input type="text" name="questions[${questionCounter}][options][C]" placeholder="Choice C (Optional)" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1">
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="D" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5">
                                <span class="text-sm font-bold text-slate-655 text-slate-600">D.</span>
                                <input type="text" name="questions[${questionCounter}][options][D]" placeholder="Choice D (Optional)" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1">
                            </label>
                        </div>
                    </div>

                    {{-- Detailed Explanation Field --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-655 text-slate-600 mb-1.5">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="info" class="h-3.5 w-3.5 text-[#0b2d6b]"></i>
                                Explanation for Correct Answer
                            </span>
                        </label>
                        <input type="text" name="questions[${questionCounter}][explanation]" 
                            placeholder="Explain why this choice is correct (shown to students as feedback review)..." 
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-xs py-2">
                    </div>
                </div>
            `;

            container.appendChild(questionDiv);

            if (typeof window.lucide !== 'undefined' && window.lucide.createIcons) {
                window.lucide.createIcons();
            }

            updateTotalPoints();
            renumberQuestions();
        }

        function removeQuestion(id) {
            const question = document.getElementById(`question-${id}`);
            if (question) {
                question.remove();
                updateTotalPoints();
                renumberQuestions();

                const container = document.getElementById('questions-container');
                const noQuestionsMsg = document.getElementById('no-questions-msg');
                if (container.children.length === 0 && noQuestionsMsg) {
                    noQuestionsMsg.classList.remove('hidden');
                }
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

            // 1. Point total from checked question bank items
            const container = document.getElementById('bank-questions-container');
            if (container && !container.classList.contains('hidden')) {
                const activeGroup = document.querySelector('.bank-group:not(.hidden)');
                if (activeGroup) {
                    const bankChecks = activeGroup.querySelectorAll('input[type="checkbox"]:checked');
                    bankChecks.forEach(cb => {
                        const label = cb.closest('label');
                        const ptsEl = label.querySelector('.bank-points');
                        if (ptsEl) {
                            total += parseInt(ptsEl.getAttribute('data-points')) || 0;
                        }
                    });
                }
            }

            // 2. Point total from inline question list
            const inputs = document.querySelectorAll('input[name^="questions["][name$="[points]"]');
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });

            // 3. Keep Maximum Score up-to-date
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
                    <label class="text-xs font-bold text-slate-655 text-slate-600 block mb-1.5">Mark Correct Option</label>
                    <div class="flex gap-6 p-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="questions[${id}][correct]" value="true" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5" required>
                            <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">True</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="questions[${id}][correct]" value="false" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5">
                            <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">False</span>
                        </label>
                    </div>
                `;
            } else if (select.value === 'multiple_choice') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-655 text-slate-600 block">Answer Choices &amp; Selection (Mark correct option)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                            <input type="radio" name="questions[${id}][correct]" value="A" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5" required>
                            <span class="text-sm font-bold text-slate-655 text-slate-600">A.</span>
                            <input type="text" name="questions[${id}][options][A]" placeholder="Choice A" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1" required>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                            <input type="radio" name="questions[${id}][correct]" value="B" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5">
                            <span class="text-sm font-bold text-slate-655 text-slate-600">B.</span>
                            <input type="text" name="questions[${id}][options][B]" placeholder="Choice B" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1" required>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                            <input type="radio" name="questions[${id}][correct]" value="C" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5">
                            <span class="text-sm font-bold text-slate-655 text-slate-600">C.</span>
                            <input type="text" name="questions[${id}][options][C]" placeholder="Choice C (Optional)" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1">
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                            <input type="radio" name="questions[${id}][correct]" value="D" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5">
                            <span class="text-sm font-bold text-slate-655 text-slate-600">D.</span>
                            <input type="text" name="questions[${id}][options][D]" placeholder="Choice D (Optional)" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1">
                        </label>
                    </div>
                `;
            } else if (select.value === 'short_answer') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-655 text-slate-600 block mb-1.5">Expected Correct Text Answer (for auto-grading match)</label>
                    <input type="text" name="questions[${id}][correct_answer]" placeholder="e.g. Paris" 
                        class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm py-2" />
                `;
            } else {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-655 text-slate-600 block mb-1.5">Reference Answer / Rubric Notes (optional)</label>
                    <textarea name="questions[${id}][correct_answer]" rows="3" placeholder="Optional model answer or grading notes for manual review..."
                        class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none py-2"></textarea>
                    <p class="text-[11px] text-slate-400">Essay responses are stored for manual review and are not auto-graded.</p>
                `;
            }
        }

        // Initialize question banks state on document load (handles Laravel validation redirects)
        document.addEventListener('DOMContentLoaded', () => {
            const bankSelector = document.getElementById('bank_selector');
            if (bankSelector && bankSelector.value) {
                showBankQuestions(bankSelector.value);
            }
        });
    </script>
@endsection
