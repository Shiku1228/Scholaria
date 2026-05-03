@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Exam Questions</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $exam->title }} • {{ $exam->course->course_number ?? $exam->course->title }}</div>
        </div>
        <a href="{{ route('teacher.exams.show', $exam) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Exam
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i data-lucide="help-circle" class="h-5 w-5 text-blue-600"></i>
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
                    <div class="text-sm font-semibold text-slate-900">{{ $exam->getTotalPoints() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg {{ $exam->is_published ? 'bg-green-100' : 'bg-amber-100' }} flex items-center justify-center">
                    <i data-lucide="{{ $exam->is_published ? 'check-circle' : 'clock' }}" class="h-5 w-5 {{ $exam->is_published ? 'text-green-600' : 'text-amber-600' }}"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Status</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $exam->is_published ? 'Published' : 'Draft' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Question Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="plus-circle" class="h-4 w-4 text-slate-500"></i>
                Add New Question
            </div>
        </div>
        <form method="POST" action="{{ route('teacher.exams.questions.add', $exam) }}" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        <span class="flex items-center gap-2">
                            <i data-lucide="help-circle" class="h-4 w-4 text-slate-400"></i>
                            Question Text <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <textarea name="question_text" rows="3" placeholder="Enter your question..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none" required></textarea>
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

            {{-- Error Display --}}
            <div id="question-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                <p class="text-red-600 text-sm font-medium" id="error-message"></p>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="add-question-btn" class="inline-flex items-center justify-center h-10 px-5 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c] shadow-sm">
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
                                <div>
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
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('teacher.exams.questions.remove', [$exam, $question]) }}" class="flex-shrink-0">
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

    @if($questions->count() > 0 && !$exam->is_published)
        <div class="mt-6 flex justify-center">
            <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors shadow-sm">
                    <i data-lucide="check-circle" class="h-4 w-4 mr-2"></i>Publish Exam
                </button>
            </form>
        </div>
    @endif

    <script>
        document.getElementById('question_type').addEventListener('change', function() {
            const mcOptions = document.getElementById('mc_options');
            const tfOptions = document.getElementById('tf_options');
            const errorDiv = document.getElementById('question-error');
            
            if (this.value === 'multiple_choice') {
                mcOptions.classList.remove('hidden');
                tfOptions.classList.add('hidden');
            } else if (this.value === 'true_false') {
                mcOptions.classList.add('hidden');
                tfOptions.classList.remove('hidden');
            } else {
                mcOptions.classList.add('hidden');
                tfOptions.classList.add('hidden');
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
