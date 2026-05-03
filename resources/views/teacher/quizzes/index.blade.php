@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Quizzes</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">Manage all your quizzes</div>
        </div>
        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center">
                <select name="course_id" class="h-10 px-3 rounded-xl border border-slate-200 bg-white text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ $filters['course_id'] == $course->id ? 'selected' : '' }}>
                            {{ $course->title ?: $course->course_number }}
                        </option>
                    @endforeach
                </select>
            </form>
            @if($filters['course_id'] > 0)
                @php
                    $selectedCourse = $courses->firstWhere('id', $filters['course_id']);
                @endphp
                @if($selectedCourse)
                    <a href="{{ route('teacher.quizzes.create', $selectedCourse) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>New Quiz
                    </a>
                @else
                    <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Select Course First
                    </a>
                @endif
            @else
                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Select Course to Create
                </a>
            @endif
        </div>
    </div>

    @if($quizzes->count() > 0)
        {{-- Quiz Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($quizzes as $quiz)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Quiz</span>
                        <span class="text-xs text-slate-500">{{ $quiz->course->title ?? $quiz->course->course_number }}</span>
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $quiz->title }}</h3>
                        <p class="text-sm text-slate-600 mb-4">{{ Str::limit($quiz->description, 80) ?: 'No description' }}</p>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                            <div>
                                <div class="text-xs text-slate-500">Due Date</div>
                                <div class="font-medium text-slate-900">{{ $quiz->due_date ? $quiz->due_date->format('M j, Y') : 'No due date' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Time Limit</div>
                                <div class="font-medium text-slate-900">{{ $quiz->time_limit ?? 60 }} min</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Max Score</div>
                                <div class="font-medium text-slate-900">{{ $quiz->max_score ?? 100 }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-500">Attempts</div>
                                <div class="font-medium text-slate-900">{{ $quiz->attempts_allowed ?? 1 }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="flex-1 inline-flex items-center justify-center h-9 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <i data-lucide="eye" class="h-4 w-4 mr-1"></i>View
                            </a>
                            <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="flex-1 inline-flex items-center justify-center h-9 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <i data-lucide="pencil" class="h-4 w-4 mr-1"></i>Edit
                            </a>
                            <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full inline-flex items-center justify-center h-9 rounded-lg border border-red-200 bg-red-50 text-sm font-medium text-red-700 hover:bg-red-100" onclick="return confirm('Delete this quiz?')">
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
            {{ $quizzes->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
            <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="help-circle" class="h-8 w-8 text-slate-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No Quizzes Found</h3>
            <p class="text-sm text-slate-500 mb-4">You haven't created any quizzes yet.</p>
            <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Create Your First Quiz
            </a>
        </div>
    @endif
@endsection
