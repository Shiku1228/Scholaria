@extends('layouts.teacher')

@section('content')
    {{-- Simple Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Exam</h1>
            <p class="text-sm text-slate-500">{{ $exam->course->course_number }} - {{ $exam->course->title }}</p>
        </div>
        <a href="{{ route('teacher.exams.show', $exam) }}" class="text-sm text-[#0b2d6b] hover:underline">
            &larr; Back to Exam
        </a>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            @foreach($errors->all() as $error)
                <p class="text-red-600 text-sm">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.exams.update', $exam) }}" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        @csrf
        @method('PUT')

        {{-- Form Header --}}
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <span class="text-sm font-medium text-slate-700">
                @if($exam->isOnline())
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="laptop" class="h-4 w-4 text-purple-600"></i>
                        Online Exam
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5">
                        <i data-lucide="calendar" class="h-4 w-4 text-amber-600"></i>
                        Scheduled Exam
                    </span>
                @endif
            </span>
            @if($exam->isPublished())
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Published</span>
            @else
                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full">Draft</span>
            @endif
        </div>

        {{-- Form Fields --}}
        <div class="p-6 space-y-5">
            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Exam Title *</label>
                <input type="text" name="title" value="{{ old('title', $exam->title) }}" 
                    class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]" 
                    required>
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                <textarea name="description" rows="2" 
                    class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] resize-none">{{ old('description', $exam->description) }}</textarea>
            </div>

            {{-- Date & Duration Row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date & Time *</label>
                    <input type="datetime-local" name="exam_date" 
                        value="{{ old('exam_date', $exam->exam_date?->format('Y-m-d\TH:i')) }}" 
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]"
                        required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Duration (minutes) *</label>
                    <input type="number" name="duration" value="{{ old('duration', $exam->duration) }}" 
                        min="15" max="480" step="5"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]"
                        required>
                </div>
            </div>

            {{-- Score & Location Row --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Maximum Score</label>
                    <input type="number" name="max_score" value="{{ old('max_score', $exam->max_score) }}" 
                        min="1" max="1000"
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
                    <input type="text" name="location" value="{{ old('location', $exam->location) }}" 
                        placeholder="Room 101, Online, etc."
                        class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b]">
                </div>
            </div>

            {{-- Instructions --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Instructions for Students</label>
                <textarea name="instructions" rows="3" 
                    class="w-full rounded-lg border-slate-300 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] resize-none"
                    placeholder="Exam rules, allowed materials, special instructions...">{{ old('instructions', $exam->instructions) }}</textarea>
            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-[#0b2d6b] text-white text-sm font-medium rounded-lg hover:bg-[#0a275c]">
                    Save Changes
                </button>
                <a href="{{ route('teacher.exams.show', $exam) }}" class="px-5 py-2 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50">
                    Cancel
                </a>
            </div>
            @if($exam->isOnline())
                <a href="{{ route('teacher.exams.questions', $exam) }}" class="px-5 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                    Questions ({{ $exam->questions()->count() }})
                </a>
            @endif
        </div>
    </form>
@endsection
