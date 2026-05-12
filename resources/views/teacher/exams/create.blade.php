@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900" id="page-title">
                <i data-lucide="calendar-plus" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Schedule Exam</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
        </a>
    </div>

    <form method="POST" action="{{ route('teacher.exams.store', $course) }}">
        @csrf

        {{-- Exam Type Selector - MOVED TO TOP for visibility --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="monitor" class="h-4 w-4 text-purple-500"></i>
                    Select Exam Type <span class="text-red-500">*</span>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Scheduled Exam Option --}}
                    <label class="relative flex cursor-pointer group" id="label-scheduled" onclick="updateFormMode()">
                        <input type="radio" name="exam_type" value="scheduled" id="type-scheduled" class="peer sr-only" {{ old('exam_type', 'scheduled') === 'scheduled' ? 'checked' : '' }} onclick="updateFormMode()" />
                        <div class="flex-1 rounded-xl border-2 border-slate-200 p-5 peer-checked:border-[#0b2d6b] peer-checked:bg-[#0b2d6b]/5 hover:border-slate-300 transition-all">
                            {{-- Selected Badge --}}
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#0b2d6b] text-white opacity-0 peer-checked:opacity-100 transition-opacity">
                                    <i data-lucide="check" class="h-3 w-3 mr-1"></i>Selected
                                </span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-xl bg-amber-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                                    <i data-lucide="calendar" class="h-6 w-6 text-amber-600"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 peer-checked:text-[#0b2d6b]">Scheduled Exam</div>
                                    <div class="text-sm text-slate-500">Physical exam or external online platform</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <ul class="text-xs text-slate-500 space-y-1">
                                    <li class="flex items-center gap-1"><i data-lucide="check" class="h-3 w-3 text-green-500"></i> Set date, time & location</li>
                                    <li class="flex items-center gap-1"><i data-lucide="check" class="h-3 w-3 text-green-500"></i> Students notified of schedule</li>
                                    <li class="flex items-center gap-1"><i data-lucide="x" class="h-3 w-3 text-slate-300"></i> No system questions</li>
                                </ul>
                            </div>
                        </div>
                    </label>

                    {{-- Online Exam Option --}}
                    <label class="relative flex cursor-pointer group" id="label-online" onclick="updateFormMode()">
                        <input type="radio" name="exam_type" value="online" id="type-online" class="peer sr-only" {{ old('exam_type') === 'online' ? 'checked' : '' }} onclick="updateFormMode()" />
                        <div class="flex-1 rounded-xl border-2 border-slate-200 p-5 peer-checked:border-purple-600 peer-checked:bg-purple-50 hover:border-slate-300 transition-all">
                            {{-- Selected Badge --}}
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-600 text-white opacity-0 peer-checked:opacity-100 transition-opacity">
                                    <i data-lucide="check" class="h-3 w-3 mr-1"></i>Selected
                                </span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 rounded-xl bg-purple-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                                    <i data-lucide="laptop" class="h-6 w-6 text-purple-600"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900 peer-checked:text-purple-700">Online Exam</div>
                                    <div class="text-sm text-slate-500">System-hosted with questions</div>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <ul class="text-xs text-slate-500 space-y-1">
                                    <li class="flex items-center gap-1"><i data-lucide="check" class="h-3 w-3 text-green-500"></i> Create questions in system</li>
                                    <li class="flex items-center gap-1"><i data-lucide="check" class="h-3 w-3 text-green-500"></i> Auto-grading for MC/TF</li>
                                    <li class="flex items-center gap-1"><i data-lucide="check" class="h-3 w-3 text-green-500"></i> Students take exam here</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
                @error('exam_type')
                    <p class="mt-3 text-xs text-red-600 flex items-center gap-1">
                        <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        {{-- Exam Details Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="clipboard-list" class="h-4 w-4 text-slate-500"></i>
                    Exam Details
                </div>
            </div>
            <div class="p-5 space-y-5">
                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="type" class="h-4 w-4 text-slate-400"></i>
                            Exam Title <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g., Midterm Examination" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required />
                    @error('title')
                        <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="align-left" class="h-4 w-4 text-slate-400"></i>
                            Description
                        </span>
                    </label>
                    <textarea name="description" rows="3" placeholder="Brief description of the exam..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Date/Time & Duration Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Exam Date --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="calendar-clock" class="h-4 w-4 text-amber-500"></i>
                                Exam Date & Time <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <input type="datetime-local" name="exam_date" value="{{ old('exam_date') }}" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required />
                        @error('exam_date')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Duration --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="clock" class="h-4 w-4 text-blue-500"></i>
                                Duration (minutes) <span class="text-red-500">*</span>
                            </span>
                        </label>
                        <div class="relative">
                            <input type="number" name="duration" value="{{ old('duration', 60) }}" min="15" max="480" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] pl-3 pr-16" required />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">min</span>
                        </div>
                        @error('duration')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Max Score & Location Row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Max Score --}}
                    <div id="max-score-section">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="target" class="h-4 w-4 text-emerald-500"></i>
                                <span id="max-score-label">Maximum Score</span>
                            </span>
                        </label>
                        <div class="relative">
                            <input type="number" name="max_score" id="max_score_input" value="{{ old('max_score', 100) }}" min="1" max="1000" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] pl-3 pr-12" />
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">pts</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500" id="max-score-hint">For online exams, this will auto-update based on question points.</p>
                        @error('max_score')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Location - Only for Scheduled --}}
                    <div id="location-section">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="h-4 w-4 text-rose-500"></i>
                                Location / Venue
                            </span>
                        </label>
                        <input type="text" name="location" id="location_input" value="{{ old('location') }}" placeholder="e.g., Room 101, Main Hall, Online via Zoom" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                        @error('location')
                            <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                                <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Instructions --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="info" class="h-4 w-4 text-slate-400"></i>
                            Instructions for Students
                        </span>
                    </label>
                    <textarea name="instructions" rows="3" placeholder="Special instructions, allowed materials, exam rules, etc." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none">{{ old('instructions') }}</textarea>
                    @error('instructions')
                        <p class="mt-1 text-xs text-red-600 flex items-center gap-1">
                            <i data-lucide="alert-circle" class="h-3 w-3"></i>{{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Question Builder Section - Only for Online Exams --}}
        <div id="question-builder-section" style="display: none;">
            <div class="bg-purple-50 rounded-xl border border-purple-200 overflow-hidden mb-6">
                <div class="px-5 py-4 border-b border-purple-200 bg-purple-100/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm font-semibold text-purple-900">
                            <i data-lucide="help-circle" class="h-4 w-4"></i>
                            Questions
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-200 text-purple-800" id="question-count">0 questions</span>
                        </div>
                        <button type="button" onclick="addQuestionField()" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-purple-600 text-white text-xs font-medium hover:bg-purple-700">
                            <i data-lucide="plus" class="h-3 w-3 mr-1"></i>Add Question
                        </button>
                    </div>
                </div>
                <div class="p-5">
                    <div id="questions-container" class="space-y-4">
                        {{-- Questions will be added here dynamically --}}
                    </div>
                    <div id="no-questions-msg" class="text-center py-8">
                        <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mx-auto mb-3">
                            <i data-lucide="help-circle" class="h-6 w-6 text-purple-400"></i>
                        </div>
                        <p class="text-sm text-slate-500">No questions added yet.</p>
                        <p class="text-xs text-slate-400 mt-1">Click "Add Question" to start building your exam.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-11 px-6 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                <i data-lucide="x" class="h-4 w-4 mr-2"></i>Cancel
            </a>
            <button type="submit" id="submit-btn" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                <i data-lucide="calendar-plus" class="h-4 w-4 mr-2"></i>
                <span id="submit-text">Schedule Exam</span>
            </button>
        </div>
    </form>

    {{-- JavaScript for dynamic form behavior --}}
    <script>
        let questionCounter = 0;
        
        function updateFormMode() {
            const isScheduled = document.getElementById('type-scheduled').checked;
            const isOnline = document.getElementById('type-online').checked;
            
            console.log('updateFormMode called. isOnline:', isOnline, 'isScheduled:', isScheduled);
            
            // Update page title
            const titleIcon = document.querySelector('#page-title i');
            const titleText = document.querySelector('#page-title span');
            
            if (isOnline) {
                titleText.textContent = 'Create Online Exam';
                if (titleIcon) titleIcon.setAttribute('data-lucide', 'laptop');
            } else {
                titleText.textContent = 'Schedule Exam';
                if (titleIcon) titleIcon.setAttribute('data-lucide', 'calendar-plus');
            }
            
            // Update submit button
            const submitText = document.getElementById('submit-text');
            const submitBtn = document.getElementById('submit-btn');
            if (isOnline) {
                submitText.textContent = 'Create Online Exam';
                submitBtn.style.backgroundColor = '#9333ea'; // purple-600
            } else {
                submitText.textContent = 'Schedule Exam';
                submitBtn.style.backgroundColor = '#0b2d6b';
            }
            
            // Show/hide location field using display style
            const locationSection = document.getElementById('location-section');
            const locationInput = document.getElementById('location_input');
            if (isOnline) {
                locationSection.style.display = 'none';
                if (locationInput) locationInput.removeAttribute('required');
            } else {
                locationSection.style.display = 'block';
            }
            
            // Show/hide question builder using display style
            const questionBuilder = document.getElementById('question-builder-section');
            console.log('Question builder element:', questionBuilder);
            if (isOnline) {
                questionBuilder.style.display = 'block';
                console.log('Showing question builder');
            } else {
                questionBuilder.style.display = 'none';
                console.log('Hiding question builder');
            }
            
            // Update max score label
            const maxScoreLabel = document.getElementById('max-score-label');
            const maxScoreHint = document.getElementById('max-score-hint');
            const maxScoreInput = document.getElementById('max_score_input');
            if (isOnline) {
                if (maxScoreLabel) maxScoreLabel.textContent = 'Total Points (Auto-calculated)';
                if (maxScoreHint) maxScoreHint.textContent = 'This will be automatically calculated from your question points.';
                if (maxScoreInput) {
                    maxScoreInput.readOnly = true;
                    maxScoreInput.style.backgroundColor = '#f1f5f9'; // slate-100
                }
            } else {
                if (maxScoreLabel) maxScoreLabel.textContent = 'Maximum Score';
                if (maxScoreHint) maxScoreHint.textContent = 'Set the maximum score for this exam.';
                if (maxScoreInput) {
                    maxScoreInput.readOnly = false;
                    maxScoreInput.style.backgroundColor = '';
                }
            }
            
            // Re-initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            updateTotalPoints();
        }
        
        function addQuestionField() {
            questionCounter++;
            const container = document.getElementById('questions-container');
            const noQuestionsMsg = document.getElementById('no-questions-msg');
            
            noQuestionsMsg.classList.add('hidden');
            
            const questionDiv = document.createElement('div');
            questionDiv.className = 'bg-white rounded-lg border border-purple-200 p-4';
            questionDiv.id = `question-${questionCounter}`;
            questionDiv.innerHTML = `
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs font-semibold question-number">${questionCounter}</span>
                        <span class="text-sm font-medium text-slate-700">Question</span>
                    </div>
                    <button type="button" onclick="removeQuestion(${questionCounter})" class="text-red-500 hover:text-red-700 p-1">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <textarea name="questions[${questionCounter}][text]" rows="2" placeholder="Enter question text..." class="w-full rounded-lg border-slate-200 focus:border-purple-500 focus:ring-purple-500 text-sm resize-none" required></textarea>
                    <div class="grid grid-cols-2 gap-3">
                        <select name="questions[${questionCounter}][type]" onchange="updateQuestionType(${questionCounter})" class="rounded-lg border-slate-200 focus:border-purple-500 focus:ring-purple-500 text-sm">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                            <option value="short_answer">Short Answer</option>
                            <option value="essay">Essay</option>
                        </select>
                        <div class="relative">
                            <input type="number" name="questions[${questionCounter}][points]" value="1" min="1" max="100" class="w-full rounded-lg border-slate-200 focus:border-purple-500 focus:ring-purple-500 text-sm pr-8" onchange="updateTotalPoints()" required />
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">pts</span>
                        </div>
                    </div>
                    <div id="options-${questionCounter}" class="space-y-2">
                        <label class="text-xs text-slate-500">Answer Options (select correct answer)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="A" class="text-purple-600" required>
                                <span class="text-sm font-medium">A.</span>
                                <input type="text" name="questions[${questionCounter}][options][A]" placeholder="Option A" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="B" class="text-purple-600">
                                <span class="text-sm font-medium">B.</span>
                                <input type="text" name="questions[${questionCounter}][options][B]" placeholder="Option B" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="C" class="text-purple-600">
                                <span class="text-sm font-medium">C.</span>
                                <input type="text" name="questions[${questionCounter}][options][C]" placeholder="Option C" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="D" class="text-purple-600">
                                <span class="text-sm font-medium">D.</span>
                                <input type="questions[${questionCounter}][options][D]" placeholder="Option D" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                        </div>
                    </div>
                </div>
            `;
            
            container.appendChild(questionDiv);
            
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
            
            updateQuestionCount();
            updateTotalPoints();
            renumberQuestions();
        }
        
        function removeQuestion(id) {
            const question = document.getElementById(`question-${id}`);
            if (question) {
                question.remove();
                updateQuestionCount();
                updateTotalPoints();
                renumberQuestions();
                
                const container = document.getElementById('questions-container');
                const noQuestionsMsg = document.getElementById('no-questions-msg');
                if (container.children.length === 0) {
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
        
        function updateQuestionCount() {
            const count = document.getElementById('questions-container').children.length;
            document.getElementById('question-count').textContent = `${count} question${count !== 1 ? 's' : ''}`;
        }
        
        function updateTotalPoints() {
            const isOnline = document.getElementById('type-online').checked;
            if (!isOnline) return;
            
            const inputs = document.querySelectorAll('input[name^="questions["][name$="[points]"]');
            let total = 0;
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            
            document.getElementById('max_score_input').value = total || 100;
        }
        
        function updateQuestionType(id) {
            const select = document.querySelector(`select[name="questions[${id}][type]"]`);
            const optionsDiv = document.getElementById(`options-${id}`);
            
            if (!select || !optionsDiv) return;
            
            if (select.value === 'true_false') {
                optionsDiv.innerHTML = `
                    <label class="text-xs text-slate-500">Correct Answer</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="questions[${id}][correct]" value="true" class="text-purple-600" required>
                            <span class="text-sm">True</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="questions[${id}][correct]" value="false" class="text-purple-600">
                            <span class="text-sm">False</span>
                        </label>
                    </div>
                `;
            } else if (select.value === 'multiple_choice') {
                optionsDiv.innerHTML = `
                    <label class="text-xs text-slate-500">Answer Options (select correct answer)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="A" class="text-purple-600" required>
                            <span class="text-sm font-medium">A.</span>
                            <input type="text" name="questions[${id}][options][A]" placeholder="Option A" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="B" class="text-purple-600">
                            <span class="text-sm font-medium">B.</span>
                            <input type="text" name="questions[${id}][options][B]" placeholder="Option B" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="C" class="text-purple-600">
                            <span class="text-sm font-medium">C.</span>
                            <input type="text" name="questions[${id}][options][C]" placeholder="Option C" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="D" class="text-purple-600">
                            <span class="text-sm font-medium">D.</span>
                            <input type="text" name="questions[${id}][options][D]" placeholder="Option D" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                    </div>
                `;
            } else {
                optionsDiv.innerHTML = `
                    <label class="text-xs text-slate-500">Correct Answer (for auto-grading)</label>
                    <input type="text" name="questions[${id}][correct_answer]" placeholder="Enter expected answer (optional)" class="w-full rounded-lg border-slate-200 focus:border-purple-500 focus:ring-purple-500 text-sm" />
                    <p class="text-xs text-slate-400 mt-1">Leave blank to grade manually later.</p>
                `;
            }
        }
        
        // Initialize on page load
        function initForm() {
            console.log('Initializing form...');
            updateFormMode();
        }
        
        // Try multiple ways to ensure it runs
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initForm);
        } else {
            // DOM already loaded
            initForm();
        }
        
        // Fallback: also run after a short delay
        setTimeout(initForm, 100);
    </script>
@endsection
