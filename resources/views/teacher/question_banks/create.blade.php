@extends('layouts.teacher')

@section('content')
    <div class="max-w-2xl mx-auto">
        {{-- Breadcrumbs & Header --}}
        <div class="mb-6">
            <a href="{{ route('teacher.question-banks.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-800 mb-2">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-1"></i>Back to Question Banks
            </a>
            <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="database" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Create Question Bank</span>
            </h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <form method="POST" action="{{ route('teacher.question-banks.store') }}" class="p-6 space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800 mb-2">Question Bank Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. Midterm 1 Prep, Chapter 4 Questions" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-white text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b] @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-semibold text-slate-800 mb-2">Description</label>
                    <textarea name="description" id="description" rows="4" placeholder="Briefly describe the topics covered in this question bank..." class="w-full p-3 rounded-xl border border-slate-200 bg-white text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b] @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('teacher.question-banks.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        Create Question Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
