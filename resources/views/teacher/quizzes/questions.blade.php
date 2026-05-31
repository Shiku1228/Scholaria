@extends('layouts.teacher')

@section('content')
    <style>
        /* High-Contrast Brand buttons - Bulletproof Fallback */
        .btn-brand-primary {
            background-color: #0b2d6b !important;
            color: #ffffff !important;
        }
        .btn-brand-primary:hover {
            background-color: #0a275c !important;
        }
        .btn-brand-success {
            background-color: #047857 !important; /* Deeper Emerald-700 green for WCAG AA compliance */
            color: #ffffff !important;
        }
        .btn-brand-success:hover {
            background-color: #065f46 !important;
        }
        .btn-brand-secondary {
            background-color: #ffffff !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .btn-brand-secondary:hover {
            background-color: #f1f5f9 !important;
        }
        .btn-brand-purple {
            background-color: #7c3aed !important;
            color: #ffffff !important;
            border: 1px solid #6d28d9 !important;
        }
        .btn-brand-purple:hover {
            background-color: #6d28d9 !important;
        }
    </style>

    @php
        $questionBanks = \App\Models\QuestionBank::where('teacher_id', auth()->id())->with('questions')->get();
    @endphp

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Quiz Questions</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $quiz->title }} • {{ $quiz->course->course_number ?? $quiz->course->title }}</div>
        </div>
        <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 btn-brand-secondary">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Quiz
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="help-circle" class="h-5 w-5 text-amber-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Total Questions</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $questions->count() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <i data-lucide="target" class="h-5 w-5 text-emerald-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Total Points</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $quiz->max_score ?? $questions->sum('points') }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg {{ $quiz->is_published ? 'bg-green-100' : 'bg-amber-100' }} flex items-center justify-center">
                    <i data-lucide="{{ $quiz->is_published ? 'check-circle' : 'clock' }}" class="h-5 w-5 {{ $quiz->is_published ? 'text-green-600' : 'text-amber-600' }}"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Status</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $quiz->is_published ? 'Published' : 'Draft' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Import from Question Bank Card --}}
    @if($questionBanks->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-purple-50/50 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold text-purple-900">
                    <i data-lucide="database" class="h-4 w-4 text-purple-600"></i>
                    Import Questions from Question Bank
                </div>
                <button type="button" onclick="toggleImportCard()" class="text-purple-600 hover:text-purple-800 text-xs font-semibold">
                    Toggle Import Form
                </button>
            </div>
            <div id="import-card-body" class="p-5 hidden space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Select Question Bank</label>
                    <select id="bank_selector" onchange="showBankQuestions(this.value)" class="w-full rounded-lg border-slate-200 focus:border-purple-500 focus:ring-purple-500 text-sm">
                        <option value="">-- Choose a Question Bank --</option>
                        @foreach($questionBanks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->questions->count() }} questions)</option>
                        @endforeach
                    </select>
                </div>

                <form method="POST" action="{{ route('teacher.quizzes.import-bank', $quiz) }}" id="import-questions-form" class="hidden space-y-4">
                    @csrf
                    <input type="hidden" name="question_bank_id" id="hidden_bank_id">
                    
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div class="bg-slate-50 px-4 py-2 border-b border-slate-200 text-xs font-semibold text-slate-700 flex items-center justify-between">
                            <span>Questions Available</span>
                            <button type="button" onclick="selectAllImportQuestions(true)" class="text-purple-600 hover:underline">Select All</button>
                        </div>
                        <div class="divide-y divide-slate-100 max-h-60 overflow-y-auto" id="bank-questions-list">
                            {{-- Dynamically populated via JS --}}
                            @foreach($questionBanks as $bank)
                                <div class="bank-group hidden" id="bank-group-{{ $bank->id }}">
                                    @forelse($bank->questions as $q)
                                        <label class="flex items-start gap-3 p-3 hover:bg-slate-50 cursor-pointer">
                                            <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" class="mt-1 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                            <div class="text-sm">
                                                <p class="font-medium text-slate-800">{{ $q->question_text }}</p>
                                                <div class="flex items-center gap-2 text-xs text-slate-500 mt-1">
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100">{{ ucfirst(str_replace('_', ' ', $q->question_type)) }}</span>
                                                    <span>{{ $q->points }} pts</span>
                                                </div>
                                            </div>
                                        </label>
                                    @empty
                                        <p class="p-4 text-sm text-slate-500 text-center">No questions in this bank.</p>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center h-10 px-5 rounded-lg bg-purple-600 text-white text-sm font-semibold hover:bg-purple-700 shadow-sm btn-brand-purple">
                            <i data-lucide="download" class="h-4 w-4 mr-2"></i>Import Selected Questions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Add Question Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="plus-circle" class="h-4 w-4 text-slate-500"></i>
                Add New Question
            </div>
        </div>
        <form method="POST" action="{{ route('teacher.quizzes.questions.add', $quiz) }}" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="help-circle" class="h-4 w-4 text-slate-400"></i>
                            Question Text <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <textarea name="question_text" rows="4" placeholder="Enter your question..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none" required></textarea>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Question Type</label>
                        <select name="question_type" id="question_type" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="true_false">True / False</option>
                            <option value="short_answer">Short Answer</option>
                            <option value="essay">Essay</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            <span class="flex items-center gap-2">
                                <i data-lucide="target" class="h-4 w-4 text-emerald-500"></i>
                                Points
                            </span>
                        </label>
                        <input type="number" name="points" value="1" min="1" max="100" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required />
                    </div>
                </div>
            </div>

            {{-- Multiple Choice Options --}}
            <div id="mc_options" class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Answer Options</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex items-center gap-2">
                        <input type="radio" name="correct_answer" value="A" class="text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm font-medium text-slate-700">A.</span>
                        <input type="text" name="options[]" placeholder="Option A" class="flex-1 rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="radio" name="correct_answer" value="B" class="text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm font-medium text-slate-700">B.</span>
                        <input type="text" name="options[]" placeholder="Option B" class="flex-1 rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="radio" name="correct_answer" value="C" class="text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm font-medium text-slate-700">C.</span>
                        <input type="text" name="options[]" placeholder="Option C" class="flex-1 rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="radio" name="correct_answer" value="D" class="text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm font-medium text-slate-700">D.</span>
                        <input type="text" name="options[]" placeholder="Option D" class="flex-1 rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                    </div>
                </div>
                <p class="text-xs text-slate-500">Select the radio button next to the correct answer.</p>
            </div>

            {{-- True/False Correct Answer --}}
            <div id="tf_options" class="hidden space-y-2">
                <label class="block text-sm font-medium text-slate-700">Correct Answer</label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="correct_answer" value="true" class="text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm text-slate-700">True</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="correct_answer" value="false" class="text-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <span class="text-sm text-slate-700">False</span>
                    </label>
                </div>
            </div>

            {{-- Short Answer Correct Answer --}}
            <div id="sa_options" class="hidden space-y-2">
                <label class="block text-sm font-medium text-slate-700">Acceptable Correct Answer (Optional)</label>
                <input type="text" name="correct_answer" id="correct_answer_sa" placeholder="Expected answer text..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
            </div>

            {{-- Explanation field for all types --}}
            <div>
                <label for="explanation" class="block text-sm font-medium text-slate-700 mb-1">
                    <span class="flex items-center gap-2">
                        <i data-lucide="info" class="h-4 w-4 text-[#0b2d6b]"></i>
                        Correct Answer Explanation (Explanations are displayed to students based on reveal control feedback settings)
                    </span>
                </label>
                <textarea name="explanation" id="explanation" rows="2" placeholder="Explain why the answer is correct..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none"></textarea>
            </div>

            {{-- Error Display --}}
            <div id="question-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <p class="text-red-600 text-sm font-medium" id="error-message"></p>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="add-question-btn" class="inline-flex items-center justify-center h-10 px-5 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c] shadow-sm btn-brand-primary">
                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Add Question
                </button>
            </div>
        </form>
    </div>

    {{-- Questions List --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="list" class="h-4 w-4 text-slate-500"></i>
                Questions List
            </div>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse ($questions as $index => $question)
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-semibold">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-2 flex-1">
                                    <p class="text-sm text-slate-900 font-medium">{{ $question->question_text }}</p>
                                    <div class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100">
                                            {{ ucfirst(str_replace('_', ' ', $question->question_type)) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                            {{ $question->points }} pts
                                        </span>
                                    </div>
                                    
                                    @if($question->isMultipleChoice() && $question->options)
                                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($question->options as $key => $option)
                                                <div class="flex items-center gap-2 p-2 rounded-lg {{ $question->correct_answer === $key ? 'bg-green-50 border border-green-200' : 'bg-slate-50' }}">
                                                    <span class="text-sm font-medium {{ $question->correct_answer === $key ? 'text-green-700' : 'text-slate-600' }}">{{ $key }}.</span>
                                                    <span class="text-sm {{ $question->correct_answer === $key ? 'text-green-700' : 'text-slate-600' }}">{{ $option }}</span>
                                                    @if($question->correct_answer === $key)
                                                        <i data-lucide="check" class="h-4 w-4 text-green-600 ml-auto"></i>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @elseif($question->isTrueFalse())
                                        <div class="mt-2 text-sm">
                                            <span class="text-slate-500">Correct answer:</span>
                                            <span class="font-medium text-green-700">{{ ucfirst($question->correct_answer) }}</span>
                                        </div>
                                    @elseif($question->isShortAnswer())
                                        <div class="mt-2 text-sm">
                                            <span class="text-slate-500">Acceptable correct answer:</span>
                                            <span class="font-medium text-green-700">{{ $question->correct_answer ?: 'Any text (manually graded)' }}</span>
                                        </div>
                                    @elseif($question->isEssay())
                                        <div class="mt-2 text-sm">
                                            <span class="text-slate-500">Reference answer:</span>
                                            <span class="font-medium text-violet-700">{{ $question->correct_answer ?: 'Manual review required' }}</span>
                                        </div>
                                    @endif

                                    @if($question->explanation)
                                        <div class="bg-amber-50/50 border border-amber-100 rounded-lg p-3 text-xs text-slate-600 mt-2 flex items-start gap-2">
                                            <i data-lucide="info" class="h-4 w-4 text-amber-600 mt-0.5 flex-shrink-0"></i>
                                            <div>
                                                <span class="font-semibold text-slate-700">Explanation:</span>
                                                {{ $question->explanation }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('teacher.quizzes.questions.remove', [$quiz, $question]) }}" class="flex-shrink-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 p-1" onclick="return confirm('Remove this question?')">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="help-circle" class="h-8 w-8 text-slate-400"></i>
                    </div>
                    <p class="text-sm text-slate-500">No questions added yet.</p>
                    <p class="text-xs text-slate-400 mt-1">Use the form above to add questions.</p>
                </div>
            @endforelse
        </div>
    </div>

    @if($questions->count() > 0 && !($quiz->is_published ?? false))
        <div class="mt-6 flex justify-center">
            <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors shadow-sm btn-brand-success">
                    <i data-lucide="check-circle" class="h-4 w-4 mr-2"></i>Publish Quiz
                </button>
            </form>
        </div>
    @endif

    <script>
        function toggleImportCard() {
            const body = document.getElementById('import-card-body');
            body.classList.toggle('hidden');
        }

        function showBankQuestions(bankId) {
            const form = document.getElementById('import-questions-form');
            const hiddenInput = document.getElementById('hidden_bank_id');
            const groups = document.querySelectorAll('.bank-group');
            
            // Hide all groups
            groups.forEach(g => g.classList.add('hidden'));
            
            if (bankId) {
                hiddenInput.value = bankId;
                const activeGroup = document.getElementById('bank-group-' + bankId);
                if (activeGroup) {
                    activeGroup.classList.remove('hidden');
                }
                form.classList.remove('hidden');
            } else {
                hiddenInput.value = '';
                form.classList.add('hidden');
            }
        }

        function selectAllImportQuestions(checked) {
            const activeGroup = document.querySelector('.bank-group:not(.hidden)');
            if (activeGroup) {
                const checkboxes = activeGroup.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(cb => cb.checked = checked);
            }
        }

        document.getElementById('question_type').addEventListener('change', function() {
            const mcOptions = document.getElementById('mc_options');
            const tfOptions = document.getElementById('tf_options');
            const saOptions = document.getElementById('sa_options');
            const errorDiv = document.getElementById('question-error');
            
            if (this.value === 'multiple_choice') {
                mcOptions.classList.remove('hidden');
                tfOptions.classList.add('hidden');
                saOptions.classList.add('hidden');
            } else if (this.value === 'true_false') {
                mcOptions.classList.add('hidden');
                tfOptions.classList.remove('hidden');
                saOptions.classList.add('hidden');
            } else if (this.value === 'short_answer') {
                mcOptions.classList.add('hidden');
                tfOptions.classList.add('hidden');
                saOptions.classList.remove('hidden');
            } else if (this.value === 'essay') {
                mcOptions.classList.add('hidden');
                tfOptions.classList.add('hidden');
                saOptions.classList.remove('hidden');
                const correctAnswerSa = document.getElementById('correct_answer_sa');
                if (correctAnswerSa) correctAnswerSa.placeholder = "Optional model answer or rubric notes...";
            } else {
                mcOptions.classList.add('hidden');
                tfOptions.classList.add('hidden');
                saOptions.classList.add('hidden');
            }
            
            // Hide error when changing type
            errorDiv.classList.add('hidden');
        });

        // Client-side validation before submit
        document.querySelector('form[action*="questions"]').addEventListener('submit', function(e) {
            const questionType = document.getElementById('question_type').value;
            const errorDiv = document.getElementById('question-error');
            const errorMsg = document.getElementById('error-message');
            
            // Check if correct answer is selected for multiple choice or true/false
            if (questionType === 'multiple_choice' || questionType === 'true_false') {
                const selectedAnswer = document.querySelector('input[name="correct_answer"]:checked');
                
                if (!selectedAnswer) {
                    e.preventDefault();
                    errorMsg.textContent = 'Please select the correct answer before adding this question.';
                    errorDiv.classList.remove('hidden');
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }
            }
            
            // Hide error if validation passes
            errorDiv.classList.add('hidden');
        });
    </script>
@endsection
