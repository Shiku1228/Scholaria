@extends('layouts.student')

@section('content')
    {{-- Timer Header --}}
    <div class="fixed top-0 left-0 right-0 bg-[#0b2d6b] text-white z-50 py-3 px-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i data-lucide="timer" class="h-5 w-5"></i>
                <span class="font-medium">Time Remaining:</span>
                <span class="font-mono text-lg font-bold" id="timer">--:--</span>
            </div>
            <div class="text-sm font-medium">
                {{ $exam->title }}
            </div>
        </div>
    </div>

    <div class="pt-20 max-w-3xl mx-auto px-4 py-6">
        {{-- Timer Start Info --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6 text-xs text-slate-500 flex items-center justify-between shadow-sm">
            <span>Attempt #{{ $attempt->attempt_number }}</span>
            <span>Timer Started at: <strong class="text-slate-800 font-semibold">{{ $attempt->started_at ? $attempt->started_at->format('g:i:s A') : now()->format('g:i:s A') }}</strong></span>
        </div>

        <form method="POST" action="{{ route('student.exams.submit', $exam) }}" id="exam-form">
            @csrf
            
            <div class="space-y-6">
                @foreach($questions as $index => $question)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 h-8 w-8 rounded-full bg-[#0b2d6b] text-white flex items-center justify-center text-sm font-semibold">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1">
                                <p class="text-slate-900 font-medium mb-4">{{ $question->question_text }}</p>
                                <p class="text-xs text-slate-400 mb-4">{{ $question->points }} point{{ $question->points > 1 ? 's' : '' }}</p>
                                
                                @if($question->isMultipleChoice())
                                    <div class="space-y-2">
                                        @foreach($question->options as $key => $option)
                                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" class="text-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                                                <span class="text-sm font-medium text-slate-700">{{ $key }}.</span>
                                                <span class="text-sm text-slate-700">{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($question->isTrueFalse())
                                    <div class="flex gap-4">
                                        <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="true" class="text-[#0b2d6b]" required>
                                            <span class="text-sm text-slate-700">True</span>
                                        </label>
                                        <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="false" class="text-[#0b2d6b]">
                                            <span class="text-sm text-slate-700">False</span>
                                        </label>
                                    </div>
                                @else
                                    <textarea name="answers[{{ $question->id }}]" rows="4" placeholder="Enter your answer..." class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] resize-none" required></textarea>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Submit Button --}}
            <div class="mt-8 flex items-center justify-between">
                <div class="text-sm text-slate-500">
                    <span id="answered-count">0</span> of {{ count($questions) }} answered
                </div>
                <button type="submit" id="submit-btn" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 shadow-sm" onclick="return confirm('Submit your exam? You cannot change answers after submission.')">
                    <i data-lucide="check-circle" class="h-4 w-4 mr-2"></i>Submit Exam
                </button>
            </div>
        </form>
    </div>

    <script>
        // Timer functionality
        const startedAt = new Date('{{ $attempt->started_at }}');
        const durationMinutes = {{ $exam->duration }};
        const endTime = new Date(startedAt.getTime() + durationMinutes * 60000);
        
        function updateTimer() {
            const now = new Date();
            const diff = endTime - now;
            
            if (diff <= 0) {
                document.getElementById('timer').textContent = '00:00';
                const submitBtn = document.getElementById('submit-btn');
                if (submitBtn) {
                    submitBtn.onclick = null;
                }
                alert('Time limit reached! Submitting your exam automatically.');
                document.getElementById('exam-form').submit();
                return;
            }
            
            const minutes = Math.floor(diff / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);
            
            document.getElementById('timer').textContent = 
                minutes.toString().padStart(2, '0') + ':' + 
                seconds.toString().padStart(2, '0');
        }
        
        updateTimer();
        setInterval(updateTimer, 1000);
        
        // Update answered count
        function updateAnsweredCount() {
            let answered = 0;
            
            @foreach($questions as $question)
                const q{{ $question->id }} = document.querySelectorAll('input[name="answers[{{ $question->id }}]"]:checked, textarea[name="answers[{{ $question->id }}]"]');
                if (q{{ $question->id }}.length > 0 && (q{{ $question->id }}[0].tagName === 'TEXTAREA' ? q{{ $question->id }}[0].value.trim() !== '' : q{{ $question->id }}[0].checked)) {
                    answered++;
                }
            @endforeach
            
            document.getElementById('answered-count').textContent = answered;
        }
        
        document.getElementById('exam-form').addEventListener('change', updateAnsweredCount);
        document.getElementById('exam-form').addEventListener('input', updateAnsweredCount);
        
        // Run once on load to initialize count
        updateAnsweredCount();
    </script>
@endsection
