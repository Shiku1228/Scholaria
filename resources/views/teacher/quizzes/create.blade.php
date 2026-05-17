@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Create Quiz</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</p>
        </div>
        <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
        </a>
    </div>

    <form method="POST" action="{{ route('teacher.quizzes.store', $course) }}" class="space-y-6">
        @csrf

        {{-- Quiz Settings --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-800">Quiz Settings</h2>
            </div>
            <div class="p-5 space-y-5">
                {{-- Title --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Quiz Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Enter quiz title" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required />
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Description</label>
                    <textarea name="description" rows="2" placeholder="Optional description for students" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none">{{ old('description') }}</textarea>
                </div>

                {{-- Settings Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Due Date --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Due Date</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date') }}" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                    </div>

                    {{-- Time Limit --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Time Limit (min) *</label>
                        <input type="number" name="time_limit" value="{{ old('time_limit', 15) }}" min="1" max="120" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required />
                    </div>

                    {{-- Max Score --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Max Score</label>
                        <input type="number" name="max_score" value="{{ old('max_score', 100) }}" min="1" max="1000" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                        <p class="mt-1 text-xs text-slate-500">Auto-updates from questions</p>
                    </div>

                    {{-- Attempts --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Attempts Allowed</label>
                        <input type="number" name="attempts_allowed" value="{{ old('attempts_allowed', 1) }}" min="1" max="10" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                    </div>
                </div>

                {{-- Options --}}
                <div class="flex flex-wrap gap-4 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions') ? 'checked' : '' }} class="rounded border-slate-300 text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm text-slate-700">Shuffle questions</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="show_results" value="1" {{ old('show_results', '1') ? 'checked' : '' }} class="rounded border-slate-300 text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm text-slate-700">Show results to students</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Questions Section --}}
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-slate-800">Questions</h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-200 text-slate-700" id="question-count">0</span>
                </div>
                <button type="button" onclick="addQuestionField()" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c]">
                    <i data-lucide="plus" class="h-4 w-4 mr-1"></i>Add Question
                </button>
            </div>
            <div class="p-5">
                <div id="questions-container" class="space-y-4">
                    {{-- Questions will be added here dynamically --}}
                </div>
                <div id="no-questions-msg" class="text-center py-10">
                    <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="help-circle" class="h-6 w-6 text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500">No questions yet</p>
                    <p class="text-xs text-slate-400 mt-1">Click "Add Question" above to start</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-10 px-5 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center h-10 px-5 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c]">
                Create Quiz
            </button>
        </div>
    </form>

    {{-- JavaScript for dynamic question builder --}}
    <script>
        let questionCounter = 0;

        function addQuestionField() {
            questionCounter++;
            const container = document.getElementById('questions-container');
            const noQuestionsMsg = document.getElementById('no-questions-msg');

            noQuestionsMsg.classList.add('hidden');

            const questionDiv = document.createElement('div');
            questionDiv.className = 'bg-slate-50 rounded-lg border border-slate-200 p-4';
            questionDiv.id = `question-${questionCounter}`;
            questionDiv.innerHTML = `
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-[#0b2d6b] text-white flex items-center justify-center text-xs font-semibold question-number">${questionCounter}</span>
                        <span class="text-sm font-medium text-slate-700">Question</span>
                    </div>
                    <button type="button" onclick="removeQuestion(${questionCounter})" class="text-red-500 hover:text-red-700 p-1">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-3">
                    <textarea name="questions[${questionCounter}][text]" rows="2" placeholder="Enter question text..." class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none" required></textarea>
                    <div class="grid grid-cols-2 gap-3">
                        <select name="questions[${questionCounter}][type]" onchange="updateQuestionType(${questionCounter})" class="rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                            <option value="short_answer">Short Answer</option>
                        </select>
                        <div class="relative">
                            <input type="number" name="questions[${questionCounter}][points]" value="1" min="1" max="100" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm pr-8" onchange="updateTotalPoints()" required />
                            <span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-slate-400">pts</span>
                        </div>
                    </div>
                    <div id="options-${questionCounter}" class="space-y-2">
                        <label class="text-xs text-slate-500">Answer Options (select correct answer)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="A" class="text-[#0b2d6b]" required>
                                <span class="text-sm font-medium">A.</span>
                                <input type="text" name="questions[${questionCounter}][options][A]" placeholder="Option A" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="B" class="text-[#0b2d6b]">
                                <span class="text-sm font-medium">B.</span>
                                <input type="text" name="questions[${questionCounter}][options][B]" placeholder="Option B" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="C" class="text-[#0b2d6b]">
                                <span class="text-sm font-medium">C.</span>
                                <input type="text" name="questions[${questionCounter}][options][C]" placeholder="Option C" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="questions[${questionCounter}][correct]" value="D" class="text-[#0b2d6b]">
                                <span class="text-sm font-medium">D.</span>
                                <input type="text" name="questions[${questionCounter}][options][D]" placeholder="Option D" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                            </label>
                        </div>
                    </div>
                </div>
            `;

            container.appendChild(questionDiv);

            if (typeof window.lucide !== 'undefined' && window.lucide.createIcons) {
                window.lucide.createIcons();
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
            document.getElementById('question-count').textContent = count;
        }

        function updateTotalPoints() {
            const inputs = document.querySelectorAll('input[name^="questions["][name$="[points]"]');
            let total = 0;
            inputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });
            document.querySelector('input[name="max_score"]').value = total || 100;
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
                            <input type="radio" name="questions[${id}][correct]" value="true" class="text-[#0b2d6b]" required>
                            <span class="text-sm">True</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="questions[${id}][correct]" value="false" class="text-[#0b2d6b]">
                            <span class="text-sm">False</span>
                        </label>
                    </div>
                `;
            } else if (select.value === 'multiple_choice') {
                optionsDiv.innerHTML = `
                    <label class="text-xs text-slate-500">Answer Options (select correct answer)</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="A" class="text-[#0b2d6b]" required>
                            <span class="text-sm font-medium">A.</span>
                            <input type="text" name="questions[${id}][options][A]" placeholder="Option A" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="B" class="text-[#0b2d6b]">
                            <span class="text-sm font-medium">B.</span>
                            <input type="text" name="questions[${id}][options][B]" placeholder="Option B" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="C" class="text-[#0b2d6b]">
                            <span class="text-sm font-medium">C.</span>
                            <input type="text" name="questions[${id}][options][C]" placeholder="Option C" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white cursor-pointer hover:bg-slate-50">
                            <input type="radio" name="questions[${id}][correct]" value="D" class="text-[#0b2d6b]">
                            <span class="text-sm font-medium">D.</span>
                            <input type="text" name="questions[${id}][options][D]" placeholder="Option D" class="flex-1 bg-transparent border-0 p-0 text-sm focus:ring-0" required>
                        </label>
                    </div>
                `;
            } else {
                optionsDiv.innerHTML = `
                    <label class="text-xs text-slate-500">Correct Answer (optional, for auto-grading)</label>
                    <input type="text" name="questions[${id}][correct_answer]" placeholder="Enter expected answer" class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" />
                `;
            }
        }
    </script>
@endsection
