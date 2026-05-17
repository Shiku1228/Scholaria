@extends('layouts.student')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $quiz->title }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">Time Remaining: {{ $quiz->time_limit ?? 15 }} minutes</div>
        </div>
    </div>

    {{-- Quiz Form --}}
    <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="space-y-6">
        @csrf

        @foreach($quiz->questions as $index => $question)
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center gap-2">
                        <span class="h-6 w-6 rounded-full bg-[#0b2d6b] text-white flex items-center justify-center text-xs font-semibold">{{ $index + 1 }}</span>
                        <span class="text-sm font-medium text-slate-800">Question {{ $index + 1 }}</span>
                        <span class="text-xs text-slate-500">({{ $question->points }} pts)</span>
                    </div>
                </div>
                <div class="p-5">
                    <p class="text-sm text-slate-900 mb-4">{{ $question->question_text }}</p>

                    @if($question->isMultipleChoice())
                        <div class="space-y-2">
                            @foreach($question->options as $key => $option)
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" class="text-[#0b2d6b]" required>
                                    <span class="text-sm"><strong>{{ $key }}.</strong> {{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif($question->isTrueFalse())
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="answers[{{ $question->id }}]" value="true" class="text-[#0b2d6b]" required>
                                <span class="text-sm">True</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="answers[{{ $question->id }}]" value="false" class="text-[#0b2d6b]">
                                <span class="text-sm">False</span>
                            </label>
                        </div>
                    @else
                        <textarea name="answers[{{ $question->id }}]" rows="3" class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" placeholder="Enter your answer..."></textarea>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="flex justify-center">
            <button type="submit" class="inline-flex items-center justify-center h-12 px-8 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700 transition-colors shadow-sm" onclick="return confirm('Submit quiz?')">
                <i data-lucide="check-circle" class="h-5 w-5 mr-2"></i>Submit Quiz
            </button>
        </div>
    </form>
@endsection
