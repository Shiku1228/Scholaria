@extends('layouts.student')

@section('content')
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $exam->title }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                    <i data-lucide="check" class="h-3 w-3 mr-1"></i>Completed
                </span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $exam->course->course_number ?? $exam->course->title }}</div>
        </div>
        <a href="{{ route('student.exams.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back to Exams
        </a>
    </div>

    {{-- Score Card --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
        <div class="text-center">
            <div class="text-sm text-slate-500 mb-2">Your Score</div>
            <div class="text-5xl font-bold text-slate-900 mb-2">
                {{ $attempt->score ?? 0 }}<span class="text-2xl text-slate-400">/{{ $exam->max_score }}</span>
            </div>
            @php
                $percentage = $exam->max_score > 0 ? (($attempt->score ?? 0) / $exam->max_score) * 100 : 0;
            @endphp
            <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $percentage >= 75 ? 'bg-green-100 text-green-700' : ($percentage >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                {{ number_format($percentage, 1) }}%
            </div>
        </div>
        <div class="grid grid-cols-3 gap-4 mt-6 pt-6 border-t border-slate-200">
            <div class="text-center">
                <div class="text-2xl font-semibold text-slate-900">{{ $exam->questions->count() }}</div>
                <div class="text-xs text-slate-500">Total Questions</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-semibold text-green-600">{{ $attempt->answers->whereNotNull('score')->where('score', '>', 0)->count() }}</div>
                <div class="text-xs text-slate-500">Correct</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-semibold text-slate-600">{{ $attempt->answers->whereNull('score')->count() }}</div>
                <div class="text-xs text-slate-500">Pending Review</div>
            </div>
        </div>
    </div>

    {{-- Answers Review --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="list" class="h-4 w-4 text-slate-500"></i>
                Answer Review
            </div>
        </div>
        <div class="divide-y divide-slate-200">
            @foreach($exam->questions as $index => $question)
                @php
                    $answer = $attempt->answers->where('question_id', $question->id)->first();
                    $isCorrect = $answer && $answer->score && $answer->score > 0;
                    $isGraded = $answer && $answer->score !== null;
                @endphp
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full {{ $isGraded ? ($isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') : 'bg-slate-100 text-slate-500' }} flex items-center justify-center text-sm font-semibold">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <p class="text-slate-900 font-medium mb-2">{{ $question->question_text }}</p>
                            <div class="flex items-center gap-2 text-xs mb-3">
                                <span class="text-slate-500">{{ $question->points }} point{{ $question->points > 1 ? 's' : '' }}</span>
                                @if($isGraded)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $answer->score }}/{{ $question->points }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">
                                        Awaiting grading
                                    </span>
                                @endif
                            </div>
                            
                            @if($question->isMultipleChoice())
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($question->options as $key => $option)
                                        <div class="p-2 rounded-lg text-sm {{ $answer && $answer->answer === $key ? ($isCorrect ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200') : ($question->correct_answer === $key ? 'bg-green-50 border border-green-200' : 'bg-slate-50') }}">
                                            <span class="font-medium">{{ $key }}.</span> {{ $option }}
                                            @if($answer && $answer->answer === $key)
                                                <i data-lucide="{{ $isCorrect ? 'check' : 'x' }}" class="h-4 w-4 {{ $isCorrect ? 'text-green-600' : 'text-red-600' }} ml-1 inline"></i>
                                            @elseif($question->correct_answer === $key)
                                                <i data-lucide="check" class="h-4 w-4 text-green-600 ml-1 inline"></i>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($question->isTrueFalse())
                                <div class="flex gap-4">
                                    <span class="p-2 rounded-lg {{ $answer && $answer->answer === 'true' ? ($isCorrect ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700') : 'bg-slate-50 text-slate-600' }}">
                                        True
                                        @if($answer && $answer->answer === 'true')
                                            <i data-lucide="{{ $isCorrect ? 'check' : 'x' }}" class="h-4 w-4 ml-1 inline"></i>
                                        @endif
                                    </span>
                                    <span class="p-2 rounded-lg {{ $answer && $answer->answer === 'false' ? ($isCorrect ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700') : 'bg-slate-50 text-slate-600' }}">
                                        False
                                        @if($answer && $answer->answer === 'false')
                                            <i data-lucide="{{ $isCorrect ? 'check' : 'x' }}" class="h-4 w-4 ml-1 inline"></i>
                                        @endif
                                    </span>
                                </div>
                            @else
                                <div class="bg-slate-50 rounded-lg p-3">
                                    <p class="text-sm text-slate-700">Your answer:</p>
                                    <p class="text-sm font-medium text-slate-900 mt-1">{{ $answer->answer ?? 'No answer' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
