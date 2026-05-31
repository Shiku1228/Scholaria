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
        .btn-brand-secondary {
            background-color: #ffffff !important;
            color: #334155 !important;
            border: 1px solid #cbd5e1 !important;
        }
        .btn-brand-secondary:hover {
            background-color: #f1f5f9 !important;
        }
    </style>

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
                    <a href="{{ route('teacher.quizzes.create', $selectedCourse) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] btn-brand-primary">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>New Quiz
                    </a>
                @else
                    <button type="button" onclick="document.getElementById('course-selector-modal').classList.remove('hidden')" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] btn-brand-primary">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>New Quiz
                    </button>
                @endif
            @else
                <button type="button" onclick="document.getElementById('course-selector-modal').classList.remove('hidden')" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] btn-brand-primary">
                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>New Quiz
                </button>
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
            <button type="button" onclick="document.getElementById('course-selector-modal').classList.remove('hidden')" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Create Your First Quiz
            </button>
        </div>
    @endif

    {{-- Course Selector Modal --}}
    <div id="course-selector-modal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl w-full max-w-md overflow-hidden transform transition-all">
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-blue-50 flex items-center justify-center border border-blue-100">
                        <i data-lucide="help-circle" class="h-4.5 w-4.5 text-[#0b2d6b]"></i>
                    </div>
                    <span class="text-base font-bold text-slate-800">Create New Quiz</span>
                </div>
                <button type="button" onclick="document.getElementById('course-selector-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-655 hover:bg-slate-100 h-8 w-8 rounded-lg flex items-center justify-center transition-colors">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>
            {{-- Modal Body --}}
            <div class="p-6">
                <p class="text-sm text-slate-500 mb-4">Please select the course for which you want to create a quiz:</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1.5 uppercase tracking-wide">Select Course</label>
                        <select id="modal_course_id" class="w-full rounded-xl border border-slate-200 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] text-sm py-2.5 px-3">
                            <option value="" disabled selected>-- Choose a course --</option>
                            @foreach($courses as $course)
                                <option value="{{ route('teacher.quizzes.create', $course->id) }}">{{ $course->title ?: $course->course_number }} ({{ $course->course_number }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('course-selector-modal').classList.add('hidden')" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-all btn-brand-secondary">
                    Cancel
                </button>
                <button type="button" onclick="proceedToCreateQuiz()" class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-all btn-brand-primary">
                    Continue<i data-lucide="chevron-right" class="h-4 w-4 ml-1"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        function proceedToCreateQuiz() {
            const courseSelect = document.getElementById('modal_course_id');
            const targetUrl = courseSelect.value;
            if (!targetUrl) {
                alert('Please select a course to proceed.');
                return;
            }
            window.location.href = targetUrl;
        }
    </script>
@endsection
