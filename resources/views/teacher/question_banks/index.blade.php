@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="database" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Question Banks</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">Create and manage reusable question banks for quizzes and exams</div>
        </div>
        <div>
            <a href="{{ route('teacher.question-banks.create') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>New Question Bank
            </a>
        </div>
    </div>

    @if($banks->count() > 0)
        {{-- Question Bank Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($banks as $bank)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#0b2d6b]/10 text-[#0b2d6b]">Question Bank</span>
                        <span class="text-xs text-slate-500 font-medium flex items-center gap-1">
                            <i data-lucide="help-circle" class="h-3 w-3"></i>
                            {{ $bank->questions_count }} {{ Str::plural('question', $bank->questions_count) }}
                        </span>
                    </div>
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $bank->name }}</h3>
                            <p class="text-sm text-slate-600 mb-4">{{ Str::limit($bank->description, 100) ?: 'No description provided.' }}</p>
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.question-banks.show', $bank) }}" class="flex-1 inline-flex items-center justify-center h-9 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <i data-lucide="eye" class="h-4 w-4 mr-1"></i>Manage
                            </a>
                            <a href="{{ route('teacher.question-banks.edit', $bank) }}" class="flex-1 inline-flex items-center justify-center h-9 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <i data-lucide="pencil" class="h-4 w-4 mr-1"></i>Edit
                            </a>
                            <form method="POST" action="{{ route('teacher.question-banks.destroy', $bank) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full inline-flex items-center justify-center h-9 rounded-lg border border-red-200 bg-red-50 text-sm font-medium text-red-700 hover:bg-red-100" onclick="return confirm('Delete this question bank? This will delete all questions within it.')">
                                    <i data-lucide="trash-2" class="h-4 w-4 mr-1"></i>Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $banks->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="database" class="h-8 w-8 text-slate-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No Question Banks</h3>
            <p class="text-sm text-slate-500 mb-4">You haven't created any question banks yet. Create one to start reusing questions across quizzes and exams.</p>
            <a href="{{ route('teacher.question-banks.create') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Create Question Bank
            </a>
        </div>
    @endif
@endsection
