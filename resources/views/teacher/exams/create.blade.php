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
        .glass-footer {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
        }
    </style>

    @php
        $oldMethod = old('exam_type', 'face_to_face');
    @endphp

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="h-11 w-11 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100 shadow-sm">
                    <i data-lucide="file-pen-line" class="h-6 w-6 text-[#0b2d6b]"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Create New Exam</h1>
                    <p class="text-sm text-slate-500 mt-0.5">{{ $course->course_number }} &bull; {{ $course->title }}</p>
                </div>
            </div>
        </div>
        <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}"
            class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Course
        </a>
    </div>

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

    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-8 shadow-sm">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 max-w-3xl mx-auto">
            <button type="button" onclick="goToStep(1)" id="step-indicator-1" class="flex items-center gap-3 group focus:outline-none">
                <span id="step-badge-1" class="h-9 w-9 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-bold shadow-sm transition-all">1</span>
                <div class="text-left">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Step 1</span>
                    <span id="step-title-1" class="text-sm font-bold text-[#0b2d6b] transition-all">Exam Details & Rules</span>
                </div>
            </button>

            <div class="hidden sm:block flex-1 h-0.5 bg-slate-100 rounded-full"></div>

            <button type="button" onclick="goToStep(2)" id="step-indicator-2" class="flex items-center gap-3 group focus:outline-none">
                <span id="step-badge-2" class="h-9 w-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-sm font-bold transition-all">2</span>
                <div class="text-left">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Step 2</span>
                    <span id="step-title-2" class="text-sm font-bold text-slate-400 transition-all">Configure Questions</span>
                </div>
            </button>

            <div class="hidden sm:block flex-1 h-0.5 bg-slate-100 rounded-full"></div>

            <button type="button" onclick="goToStep(3)" id="step-indicator-3" class="flex items-center gap-3 group focus:outline-none">
                <span id="step-badge-3" class="h-9 w-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-sm font-bold transition-all">3</span>
                <div class="text-left">
                    <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Step 3</span>
                    <span id="step-title-3" class="text-sm font-bold text-slate-400 transition-all">Review & Save</span>
                </div>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('teacher.exams.store', $course) }}" id="exam-creation-form" class="space-y-6">
        @csrf
        <input type="hidden" name="exam_type" id="exam_type_input" value="{{ $oldMethod }}">

        <div id="step-content-1" class="space-y-6 animate-slide-in">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="layout-template" class="h-5 w-5 text-[#0b2d6b]"></i>
                    <h3 class="text-sm font-bold text-slate-800">Exam Method</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button type="button" id="method-face-to-face"
                            class="text-left rounded-2xl border p-5 transition-all"
                            onclick="selectMethod('face_to_face')">
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                                    <i data-lucide="users" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">Face-to-Face</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700">Face-to-Face Exam</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Students only view the exam details. No online answering or submit button will appear.</p>
                                </div>
                            </div>
                        </button>

                        <button type="button" id="method-online"
                            class="text-left rounded-2xl border p-5 transition-all"
                            onclick="selectMethod('online')">
                            <div class="flex items-start gap-3">
                                <div class="h-10 w-10 rounded-xl bg-indigo-100 text-[#0b2d6b] flex items-center justify-center">
                                    <i data-lucide="laptop" class="h-5 w-5"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900">Online</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700">Online Exam</span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Students answer online. Use Question Bank items, inline questions, or both.</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="file-text" class="h-5 w-5 text-indigo-600"></i>
                    <h3 class="text-sm font-bold text-slate-800">General Specifications</h3>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Exam Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. Final Examination in Programming 1"
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm py-2.5"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Short Description</label>
                        <textarea name="description" rows="3" placeholder="Optional overview or exam summary..."
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm resize-none py-2">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Instructions <span id="instructions-required" class="text-red-500">*</span></label>
                        <textarea name="instructions" rows="4" placeholder="Explain the exam rules, reminders, and instructions for students..."
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm resize-none py-2">{{ old('instructions') }}</textarea>
                    </div>

                    <div id="location-wrapper">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Location</label>
                        <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Room 204, Main Building"
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm py-2.5">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="clock" class="h-5 w-5 text-amber-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Timing & Schedule</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="exam_date" value="{{ old('exam_date') }}"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Due Date <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        </div>
                        <div id="duration-wrapper">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Time Limit <span id="duration-required" class="text-red-500">*</span></label>
                            <div class="relative rounded-xl shadow-sm">
                                <input type="number" name="duration" id="duration_input" value="{{ old('duration', 60) }}" min="1" max="480"
                                    class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm pr-12">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-semibold">min</span>
                                </div>
                            </div>
                            <span class="text-xs text-slate-400 mt-1 block">Required for online exams only.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="shield-check" class="h-5 w-5 text-emerald-600"></i>
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
                                <option value="instant" {{ old('feedback_type') === 'instant' ? 'selected' : '' }}>Instant (Immediate Review)</option>
                                <option value="delayed" {{ old('feedback_type') === 'delayed' ? 'selected' : '' }}>Delayed (Manual Release)</option>
                            </select>
                        </div>
                        <div id="random-subset-wrapper">
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Random Subset Count</label>
                            <input type="number" name="random_subset_count" value="{{ old('random_subset_count') }}" placeholder="All questions"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Maximum Score <span class="text-red-500">*</span></label>
                            <input type="number" name="max_score" id="max_score_input" value="{{ old('max_score', 100) }}" min="1"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm font-bold text-slate-700">
                            <span id="max-score-help" class="text-xs text-slate-400 mt-1 block">Set the total score for face-to-face exams. Online total updates from question points.</span>
                        </div>
                        <div class="flex items-center h-full pt-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions') ? 'checked' : '' }}
                                    class="rounded-lg border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5">
                                <div class="text-sm">
                                    <span class="font-semibold text-slate-700 group-hover:text-slate-900">Shuffle Question Order</span>
                                    <p class="text-xs text-slate-400">Randomizes question order per attempt.</p>
                                </div>
                            </label>
                        </div>
                        <div class="flex items-center h-full pt-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="show_results" value="1" {{ old('show_results', '1') ? 'checked' : '' }}
                                    class="rounded-lg border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5">
                                <div class="text-sm">
                                    <span class="font-semibold text-slate-700 group-hover:text-slate-900">Show Results to Students</span>
                                    <p class="text-xs text-slate-400">Displays score and review after submission when released.</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="step-content-2" class="space-y-6 hidden animate-slide-in">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i data-lucide="database" class="h-4.5 w-4.5 text-purple-600"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">1. Import from Question Bank</h3>
                            <p class="text-xs text-slate-500">Select reusable questions, search them, and mix them with inline items.</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">
                        <span id="selected-bank-count">0</span>&nbsp;selected
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    @if($questionBanks->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-1">
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Question Bank</label>
                                <select name="question_bank_id" id="bank_selector" onchange="showBankQuestions(this.value)"
                                    class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                                    <option value="">-- Click to choose a bank --</option>
                                    @foreach($questionBanks as $bank)
                                        <option value="{{ $bank->id }}" {{ old('question_bank_id') == $bank->id ? 'selected' : '' }}>
                                            {{ $bank->name }} ({{ $bank->questions->count() }} questions)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Search</label>
                                <input type="text" id="bank-search" placeholder="Search question text..."
                                    class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Question Type</label>
                                <select id="bank-type-filter"
                                    class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm">
                                    <option value="">All types</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                    <option value="true_false">True / False</option>
                                    <option value="short_answer">Identification / Short Answer</option>
                                    <option value="essay">Essay</option>
                                </select>
                            </div>
                        </div>

                        <div class="border border-slate-100 rounded-xl overflow-hidden hidden" id="bank-questions-container">
                            <div class="bg-slate-50 px-4 py-3 border-b border-slate-100 text-xs font-bold text-slate-600 flex items-center justify-between">
                                <span class="flex items-center gap-1.5"><i data-lucide="list-checks" class="h-4 w-4"></i>Select Questions</span>
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="selectAllImportQuestions(true)" class="text-[#0b2d6b] hover:underline">Select All</button>
                                    <span class="text-slate-300">|</span>
                                    <button type="button" onclick="selectAllImportQuestions(false)" class="text-slate-400 hover:underline">Deselect All</button>
                                </div>
                            </div>
                            <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto" id="bank-questions-list">
                                @foreach($questionBanks as $bank)
                                    <div class="bank-group hidden" id="bank-group-{{ $bank->id }}">
                                        @forelse($bank->questions as $q)
                                            <label class="bank-item flex items-start gap-3 p-3.5 hover:bg-slate-50 cursor-pointer transition-colors"
                                                data-question-text="{{ strtolower($q->question_text) }}"
                                                data-question-type="{{ $q->question_type }}">
                                                <input type="checkbox" name="question_ids[]" value="{{ $q->id }}"
                                                    class="mt-1 rounded-md border-slate-200 text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5 bank-checkbox"
                                                    data-points="{{ $q->points }}"
                                                    data-type="{{ $q->question_type }}"
                                                    data-text="{{ $q->question_text }}"
                                                    data-source="Question Bank"
                                                    onchange="handleBankSelectionChange()"
                                                    {{ in_array($q->id, old('question_ids', [])) ? 'checked' : '' }}>
                                                <div class="text-sm flex-1">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <p class="font-semibold text-slate-800 tracking-tight">{{ $q->question_text }}</p>
                                                        <span class="bank-points text-xs font-semibold text-slate-500 whitespace-nowrap" data-points="{{ $q->points }}">{{ $q->points }} pts</span>
                                                    </div>
                                                    <div class="flex items-center gap-2.5 text-xs text-slate-500 mt-1.5 flex-wrap">
                                                        <span class="px-2 py-0.5 rounded-full font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                                            {{ str_replace('_', ' ', $q->question_type) }}
                                                        </span>
                                                        <span>Source: Question Bank</span>
                                                    </div>
                                                </div>
                                            </label>
                                        @empty
                                            <div class="p-4 text-sm text-slate-500">No questions available in this bank.</div>
                                        @endforelse
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                            No question banks found yet. You can still add inline questions below.
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="square-pen" class="h-5 w-5 text-emerald-600"></i>
                        <h3 class="text-sm font-bold text-slate-800">2. Add Inline Questions</h3>
                    </div>
                    <button type="button" onclick="addQuestionField()"
                        class="inline-flex items-center justify-center h-9 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Add Question
                    </button>
                </div>
                <div class="p-6">
                    <div id="no-questions-msg" class="text-sm text-slate-500 text-center py-8 border border-dashed border-slate-200 rounded-2xl bg-slate-50">
                        No inline questions added yet. Use Question Bank, add questions manually, or combine both.
                    </div>
                    <div id="questions-container" class="space-y-4"></div>
                </div>
            </div>
        </div>

        <div id="step-content-3" class="space-y-6 hidden animate-slide-in">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="h-5 w-5 text-[#0b2d6b]"></i>
                    <h3 class="text-sm font-bold text-slate-800">Review Exam Summary</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <div class="text-xs text-slate-500 mb-1">Method</div>
                            <div class="font-semibold text-slate-900" id="review-method">Face-to-Face</div>
                        </div>
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <div class="text-xs text-slate-500 mb-1">Questions</div>
                            <div class="font-semibold text-slate-900" id="review-question-count">0</div>
                        </div>
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <div class="text-xs text-slate-500 mb-1">Max Score</div>
                            <div class="font-semibold text-slate-900" id="review-max-score">{{ old('max_score', 100) }}</div>
                        </div>
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <div class="text-xs text-slate-500 mb-1">Time Limit</div>
                            <div class="font-semibold text-slate-900" id="review-duration">{{ old('duration', 60) }} min</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div class="bg-white rounded-xl border border-slate-200 p-4">
                            <div class="text-xs text-slate-500 mb-1">Title</div>
                            <div class="font-semibold text-slate-900" id="review-title">Not set</div>
                        </div>
                        <div class="bg-white rounded-xl border border-slate-200 p-4">
                            <div class="text-xs text-slate-500 mb-1">Schedule</div>
                            <div class="font-semibold text-slate-900" id="review-schedule">Not set</div>
                        </div>
                    </div>

                    <div class="mt-4 bg-white rounded-xl border border-slate-200 p-4">
                        <div class="text-xs text-slate-500 mb-1">Instructions</div>
                        <div class="text-sm text-slate-700 whitespace-pre-wrap" id="review-instructions">No instructions provided.</div>
                    </div>
                </div>
            </div>

            <div id="review-questions-card" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="list-checks" class="h-5 w-5 text-slate-500"></i>
                        <h3 class="text-sm font-bold text-slate-800">Final Question List</h3>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">Question Bank + Inline</span>
                </div>
                <div id="review-question-list" class="divide-y divide-slate-100">
                    <div class="p-6 text-sm text-slate-500">No online questions selected yet.</div>
                </div>
            </div>
        </div>

        <div class="sticky bottom-0 z-40 pt-2">
            <div class="glass-footer border border-slate-200 rounded-2xl shadow-lg px-5 py-4 flex items-center justify-between gap-4">
                <div class="text-sm text-slate-500">
                    Draft status:
                    <span class="font-semibold text-slate-800">Unpublished</span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" id="prev-btn" onclick="goToStep(currentStep - 1)"
                        class="hidden inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all shadow-sm">
                        <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Previous
                    </button>
                    <button type="button" id="next-btn" onclick="goToStep(currentStep + 1)"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-all shadow-sm">
                        Next<i data-lucide="arrow-right" class="h-4 w-4 ml-2"></i>
                    </button>
                    <button type="submit" id="submit-btn"
                        class="hidden inline-flex items-center justify-center h-10 px-5 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-all shadow-sm">
                        <i data-lucide="save" class="h-4 w-4 mr-2"></i>Create Exam
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="pb-24"></div>

    <script>
        let currentStep = 1;
        let currentMethod = @json($oldMethod);
        let questionCounter = 0;
        const oldQuestions = @json(array_values(old('questions', [])));

        function selectMethod(method) {
            currentMethod = method;
            document.getElementById('exam_type_input').value = method;

            const faceCard = document.getElementById('method-face-to-face');
            const onlineCard = document.getElementById('method-online');
            const durationInput = document.getElementById('duration_input');
            const maxScoreInput = document.getElementById('max_score_input');
            const locationWrapper = document.getElementById('location-wrapper');
            const randomSubsetWrapper = document.getElementById('random-subset-wrapper');
            const instructionsRequired = document.getElementById('instructions-required');
            const durationRequired = document.getElementById('duration-required');
            const reviewQuestionsCard = document.getElementById('review-questions-card');

            faceCard.className = method === 'face_to_face'
                ? 'text-left rounded-2xl border p-5 transition-all border-amber-300 bg-amber-50 shadow-sm'
                : 'text-left rounded-2xl border p-5 transition-all border-slate-200 bg-white hover:border-amber-200';
            onlineCard.className = method === 'online'
                ? 'text-left rounded-2xl border p-5 transition-all border-indigo-300 bg-indigo-50 shadow-sm'
                : 'text-left rounded-2xl border p-5 transition-all border-slate-200 bg-white hover:border-indigo-200';

            if (method === 'online') {
                durationInput.removeAttribute('disabled');
                durationInput.setAttribute('required', 'required');
                maxScoreInput.setAttribute('readonly', 'readonly');
                maxScoreInput.classList.add('bg-slate-50');
                locationWrapper.classList.add('hidden');
                randomSubsetWrapper.classList.remove('hidden');
                instructionsRequired.classList.remove('hidden');
                durationRequired.classList.remove('hidden');
                reviewQuestionsCard.classList.remove('hidden');
            } else {
                durationInput.removeAttribute('required');
                maxScoreInput.removeAttribute('readonly');
                maxScoreInput.classList.remove('bg-slate-50');
                locationWrapper.classList.remove('hidden');
                randomSubsetWrapper.classList.add('hidden');
                instructionsRequired.classList.remove('hidden');
                durationRequired.classList.add('hidden');
                reviewQuestionsCard.classList.add('hidden');
                if (currentStep === 2) {
                    goToStep(3);
                }
            }

            updateTotalPoints();
            updateReview();
            updateStepper();
        }

        function goToStep(step) {
            const maxStep = currentMethod === 'online' ? 3 : 3;

            if (step < 1 || step > maxStep) {
                return;
            }

            if (step === 2 && currentMethod !== 'online') {
                step = 3;
            }

            if (step > currentStep && !validateStep(currentStep)) {
                return;
            }

            currentStep = step;

            document.getElementById('step-content-1').classList.toggle('hidden', step !== 1);
            document.getElementById('step-content-2').classList.toggle('hidden', step !== 2);
            document.getElementById('step-content-3').classList.toggle('hidden', step !== 3);

            document.getElementById('prev-btn').classList.toggle('hidden', step === 1);
            document.getElementById('next-btn').classList.toggle('hidden', step === 3);
            document.getElementById('submit-btn').classList.toggle('hidden', step !== 3);

            updateReview();
            updateStepper();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            if (step === 1) {
                const title = document.querySelector('input[name="title"]');
                const examDate = document.querySelector('input[name="exam_date"]');
                const dueDate = document.querySelector('input[name="due_date"]');
                const instructions = document.querySelector('textarea[name="instructions"]');
                const maxScore = document.getElementById('max_score_input');
                const duration = document.getElementById('duration_input');

                if (!title.value.trim()) {
                    alert('Please enter the exam title first.');
                    title.focus();
                    return false;
                }
                if (!examDate.value) {
                    alert('Please set the exam start date.');
                    examDate.focus();
                    return false;
                }
                if (!dueDate.value) {
                    alert('Please set the exam due date.');
                    dueDate.focus();
                    return false;
                }
                if (new Date(dueDate.value) < new Date(examDate.value)) {
                    alert('Due date cannot be earlier than the start date.');
                    dueDate.focus();
                    return false;
                }
                if (!instructions.value.trim()) {
                    alert('Please provide exam instructions.');
                    instructions.focus();
                    return false;
                }
                if (!maxScore.value || parseInt(maxScore.value, 10) < 1) {
                    alert('Maximum score must be at least 1.');
                    maxScore.focus();
                    return false;
                }
                if (currentMethod === 'online' && (!duration.value || parseInt(duration.value, 10) < 1)) {
                    alert('Time limit is required for online exams.');
                    duration.focus();
                    return false;
                }
            }

            if (step === 2 && currentMethod === 'online') {
                if (getSelectedBankQuestionCount() + getInlineQuestionCount() === 0) {
                    alert('Add at least one question for this online exam.');
                    return false;
                }
            }

            return true;
        }

        function updateStepper() {
            const states = [
                { badge: document.getElementById('step-badge-1'), title: document.getElementById('step-title-1') },
                { badge: document.getElementById('step-badge-2'), title: document.getElementById('step-title-2') },
                { badge: document.getElementById('step-badge-3'), title: document.getElementById('step-title-3') },
            ];

            states.forEach((state, index) => {
                const stepNumber = index + 1;
                const isDisabled = currentMethod !== 'online' && stepNumber === 2;

                if (stepNumber < currentStep && !isDisabled) {
                    state.badge.className = 'h-9 w-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm font-bold shadow-sm transition-all';
                    state.title.className = 'text-sm font-bold text-emerald-800 transition-all';
                } else if (stepNumber === currentStep) {
                    state.badge.className = 'h-9 w-9 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-bold shadow-sm transition-all';
                    state.title.className = 'text-sm font-bold text-[#0b2d6b] transition-all';
                } else {
                    state.badge.className = 'h-9 w-9 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-sm font-bold transition-all';
                    state.title.className = 'text-sm font-bold text-slate-400 transition-all';
                }

                if (isDisabled) {
                    state.badge.className = 'h-9 w-9 rounded-xl bg-slate-100 text-slate-300 flex items-center justify-center text-sm font-bold transition-all';
                    state.title.className = 'text-sm font-bold text-slate-300 transition-all';
                }
            });
        }

        function showBankQuestions(bankId) {
            const container = document.getElementById('bank-questions-container');
            if (!container) return;

            document.querySelectorAll('.bank-group').forEach((group) => group.classList.add('hidden'));

            if (bankId) {
                container.classList.remove('hidden');
                const activeGroup = document.getElementById(`bank-group-${bankId}`);
                if (activeGroup) {
                    activeGroup.classList.remove('hidden');
                }
            } else {
                container.classList.add('hidden');
            }

            filterBankQuestions();
            handleBankSelectionChange();
        }

        function filterBankQuestions() {
            const search = (document.getElementById('bank-search')?.value || '').toLowerCase();
            const type = document.getElementById('bank-type-filter')?.value || '';
            const activeGroup = document.querySelector('.bank-group:not(.hidden)');
            if (!activeGroup) return;

            activeGroup.querySelectorAll('.bank-item').forEach((item) => {
                const matchesSearch = item.dataset.questionText.includes(search);
                const matchesType = !type || item.dataset.questionType === type;
                item.classList.toggle('hidden', !(matchesSearch && matchesType));
            });
        }

        function selectAllImportQuestions(checked) {
            const activeGroup = document.querySelector('.bank-group:not(.hidden)');
            if (!activeGroup) return;

            activeGroup.querySelectorAll('.bank-item:not(.hidden) .bank-checkbox').forEach((checkbox) => {
                checkbox.checked = checked;
            });

            handleBankSelectionChange();
        }

        function handleBankSelectionChange() {
            document.getElementById('selected-bank-count').textContent = getSelectedBankQuestionCount();
            updateTotalPoints();
            updateReview();
        }

        function getSelectedBankQuestionCount() {
            return document.querySelectorAll('.bank-checkbox:checked').length;
        }

        function getInlineQuestionCount() {
            return document.querySelectorAll('#questions-container > [data-inline-question]').length;
        }

        function addQuestionField(question = null) {
            questionCounter++;
            const container = document.getElementById('questions-container');
            const noQuestionsMsg = document.getElementById('no-questions-msg');
            noQuestionsMsg.classList.add('hidden');

            const data = {
                text: question?.text || '',
                type: question?.type || 'multiple_choice',
                points: question?.points || 1,
                explanation: question?.explanation || '',
                correct: question?.correct || '',
                correct_answer: question?.correct_answer || '',
                options: question?.options || { A: '', B: '', C: '', D: '' },
            };

            const questionDiv = document.createElement('div');
            questionDiv.className = 'bg-slate-50 rounded-2xl border border-slate-200 p-5 space-y-4 animate-slide-in';
            questionDiv.id = `question-${questionCounter}`;
            questionDiv.dataset.inlineQuestion = 'true';
            questionDiv.innerHTML = `
                <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                    <div class="flex items-center gap-2.5">
                        <span class="h-7 w-7 rounded-xl bg-[#0b2d6b] text-white flex items-center justify-center text-xs font-bold question-number">${questionCounter}</span>
                        <span class="text-sm font-bold text-slate-800">Inline Question Details</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">Inline</span>
                    </div>
                    <button type="button" onclick="removeQuestion(${questionCounter})" class="text-slate-400 hover:text-red-600 transition-colors p-1">
                        <i data-lucide="trash-2" class="h-4.5 w-4.5"></i>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Question Text *</label>
                        <textarea name="questions[${questionCounter}][text]" rows="2" placeholder="Write the question..."
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none py-2" required>${escapeHtml(data.text)}</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Question Type</label>
                            <select name="questions[${questionCounter}][type]" onchange="updateQuestionType(${questionCounter})"
                                class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                                <option value="multiple_choice" ${data.type === 'multiple_choice' ? 'selected' : ''}>Multiple Choice</option>
                                <option value="true_false" ${data.type === 'true_false' ? 'selected' : ''}>True / False</option>
                                <option value="short_answer" ${data.type === 'short_answer' ? 'selected' : ''}>Identification / Short Answer</option>
                                <option value="essay" ${data.type === 'essay' ? 'selected' : ''}>Essay</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Points *</label>
                            <div class="relative">
                                <input type="number" name="questions[${questionCounter}][points]" value="${escapeHtml(String(data.points))}" min="1" max="100"
                                    class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm pr-12"
                                    onchange="updateTotalPoints()" required>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-400 text-xs font-semibold">pts</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="options-${questionCounter}" class="space-y-2"></div>

                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">
                            <span class="flex items-center gap-1.5">
                                <i data-lucide="info" class="h-3.5 w-3.5 text-[#0b2d6b]"></i>
                                Explanation / Feedback Note
                            </span>
                        </label>
                        <input type="text" name="questions[${questionCounter}][explanation]"
                            value="${escapeHtml(data.explanation)}"
                            placeholder="Optional explanation shown during review..."
                            class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-xs py-2">
                    </div>
                </div>
            `;

            container.appendChild(questionDiv);
            updateQuestionType(questionCounter, data);
            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
            updateTotalPoints();
            renumberQuestions();
            updateReview();
        }

        function removeQuestion(id) {
            const question = document.getElementById(`question-${id}`);
            if (question) {
                question.remove();
                renumberQuestions();
                updateTotalPoints();
                updateReview();
            }

            if (getInlineQuestionCount() === 0) {
                document.getElementById('no-questions-msg').classList.remove('hidden');
            }
        }

        function renumberQuestions() {
            document.querySelectorAll('#questions-container > [data-inline-question]').forEach((question, index) => {
                const numEl = question.querySelector('.question-number');
                if (numEl) numEl.textContent = index + 1;
            });
        }

        function updateQuestionType(id, initialData = null) {
            const select = document.querySelector(`select[name="questions[${id}][type]"]`);
            const optionsDiv = document.getElementById(`options-${id}`);
            if (!select || !optionsDiv) return;

            const data = initialData || {};
            const optionA = data.options?.A || '';
            const optionB = data.options?.B || '';
            const optionC = data.options?.C || '';
            const optionD = data.options?.D || '';
            const correct = data.correct || '';
            const correctAnswer = data.correct_answer || '';

            if (select.value === 'true_false') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 block mb-1.5">Mark Correct Option</label>
                    <div class="flex gap-6 p-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="questions[${id}][correct]" value="true" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5" ${correct === 'true' ? 'checked' : ''} required>
                            <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">True</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="radio" name="questions[${id}][correct]" value="false" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-5 w-5" ${correct === 'false' ? 'checked' : ''}>
                            <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">False</span>
                        </label>
                    </div>
                `;
            } else if (select.value === 'multiple_choice') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 block">Answer Choices &amp; Selection</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        ${buildMultipleChoiceOption(id, 'A', optionA, correct === 'A', true)}
                        ${buildMultipleChoiceOption(id, 'B', optionB, correct === 'B', true)}
                        ${buildMultipleChoiceOption(id, 'C', optionC, correct === 'C', false)}
                        ${buildMultipleChoiceOption(id, 'D', optionD, correct === 'D', false)}
                    </div>
                `;
            } else if (select.value === 'short_answer') {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 block mb-1.5">Expected Correct Text Answer</label>
                    <input type="text" name="questions[${id}][correct_answer]" value="${escapeHtml(correctAnswer)}" placeholder="e.g. CPU"
                        class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm py-2">
                `;
            } else {
                optionsDiv.innerHTML = `
                    <label class="text-xs font-bold text-slate-600 block mb-1.5">Reference Answer / Rubric Notes</label>
                    <textarea name="questions[${id}][correct_answer]" rows="3" placeholder="Optional model answer or manual grading notes..."
                        class="w-full rounded-xl border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none py-2">${escapeHtml(correctAnswer)}</textarea>
                    <p class="text-[11px] text-slate-400">Essay responses require manual grading.</p>
                `;
            }

            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
        }

        function buildMultipleChoiceOption(id, key, value, checked, required) {
            return `
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 bg-white cursor-pointer hover:bg-slate-50 transition-colors">
                    <input type="radio" name="questions[${id}][correct]" value="${key}" class="text-[#0b2d6b] focus:ring-[#0b2d6b] h-4.5 w-4.5" ${checked ? 'checked' : ''} ${required && key === 'A' ? 'required' : ''}>
                    <span class="text-sm font-bold text-slate-600">${key}.</span>
                    <input type="text" name="questions[${id}][options][${key}]" value="${escapeHtml(value)}" placeholder="Choice ${key}" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0 ml-1" ${required ? 'required' : ''}>
                </label>
            `;
        }

        function updateTotalPoints() {
            let total = 0;

            document.querySelectorAll('.bank-checkbox:checked').forEach((checkbox) => {
                total += parseInt(checkbox.dataset.points || '0', 10);
            });

            document.querySelectorAll('input[name^="questions["][name$="[points]"]').forEach((input) => {
                total += parseInt(input.value || '0', 10);
            });

            const maxScoreField = document.getElementById('max_score_input');
            const help = document.getElementById('max-score-help');
            if (currentMethod === 'online') {
                maxScoreField.value = total > 0 ? total : '';
                help.textContent = 'Calculated from the selected Question Bank and inline question points.';
            } else {
                if (!maxScoreField.value) {
                    maxScoreField.value = 100;
                }
                help.textContent = 'Set the total score for face-to-face exams. Online total updates from question points.';
            }

            updateReview();
        }

        function collectQuestionPreview() {
            const items = [];

            document.querySelectorAll('.bank-checkbox:checked').forEach((checkbox) => {
                items.push({
                    type: checkbox.dataset.type,
                    text: checkbox.dataset.text,
                    points: checkbox.dataset.points,
                    source: 'Question Bank',
                });
            });

            document.querySelectorAll('#questions-container > [data-inline-question]').forEach((question) => {
                const text = question.querySelector('textarea[name$="[text]"]')?.value || '';
                const type = question.querySelector('select[name$="[type]"]')?.value || 'multiple_choice';
                const points = question.querySelector('input[name$="[points]"]')?.value || '0';
                items.push({
                    type,
                    text,
                    points,
                    source: 'Inline',
                });
            });

            return items;
        }

        function updateReview() {
            const title = document.querySelector('input[name="title"]')?.value || 'Not set';
            const examDate = document.querySelector('input[name="exam_date"]')?.value || 'Not set';
            const dueDate = document.querySelector('input[name="due_date"]')?.value || 'Not set';
            const instructions = document.querySelector('textarea[name="instructions"]')?.value || 'No instructions provided.';
            const duration = document.getElementById('duration_input')?.value || '0';
            const maxScore = document.getElementById('max_score_input')?.value || '0';
            const questions = collectQuestionPreview();

            document.getElementById('review-title').textContent = title;
            document.getElementById('review-method').textContent = currentMethod === 'online' ? 'Online Exam' : 'Face-to-Face Exam';
            document.getElementById('review-schedule').textContent = `${examDate} to ${dueDate}`;
            document.getElementById('review-instructions').textContent = instructions.trim() || 'No instructions provided.';
            document.getElementById('review-duration').textContent = currentMethod === 'online' ? `${duration || 0} min` : 'Not required';
            document.getElementById('review-max-score').textContent = maxScore || '0';
            document.getElementById('review-question-count').textContent = currentMethod === 'online' ? String(questions.length) : '0';

            const list = document.getElementById('review-question-list');
            if (currentMethod !== 'online') {
                list.innerHTML = '<div class="p-6 text-sm text-slate-500">Face-to-face exams do not require online questions.</div>';
                return;
            }

            if (questions.length === 0) {
                list.innerHTML = '<div class="p-6 text-sm text-slate-500">No online questions selected yet.</div>';
                return;
            }

            list.innerHTML = questions.map((question, index) => `
                <div class="p-5 flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="h-6 w-6 rounded-full bg-[#0b2d6b] text-white text-[11px] font-bold inline-flex items-center justify-center">${index + 1}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">${question.type.replaceAll('_', ' ')}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold ${question.source === 'Question Bank' ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700'}">${question.source}</span>
                        </div>
                        <div class="text-sm font-semibold text-slate-900">${escapeHtml(question.text || 'Untitled question')}</div>
                    </div>
                    <div class="text-sm font-semibold text-slate-600 whitespace-nowrap">${question.points} pts</div>
                </div>
            `).join('');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        document.getElementById('bank-search')?.addEventListener('input', filterBankQuestions);
        document.getElementById('bank-type-filter')?.addEventListener('change', filterBankQuestions);

        document.addEventListener('DOMContentLoaded', () => {
            oldQuestions.forEach((question) => addQuestionField(question));

            if (getInlineQuestionCount() === 0) {
                document.getElementById('no-questions-msg').classList.remove('hidden');
            }

            const bankSelector = document.getElementById('bank_selector');
            if (bankSelector?.value) {
                showBankQuestions(bankSelector.value);
            }

            selectMethod(currentMethod);
            handleBankSelectionChange();

            const shouldOpenQuestions = @json($errors->has('questions') || $errors->has('question_ids') || $errors->has('duration'));
            goToStep(shouldOpenQuestions && currentMethod === 'online' ? 2 : 1);
        });
    </script>
@endsection
