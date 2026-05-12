@extends('layouts.teacher')

@section('content')
    @php
        $activeTab = $activeTab ?? 'assignments';
        $courseId = $filters['course_id'] ?? 0;
        $hasFilters = $courseId > 0;
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                        <i data-lucide="clipboard-list" class="h-5 w-5 text-[#0b2d6b]"></i>
                        <span>Tasks</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">Manage assignments, exams, and quizzes for your courses.</div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <form method="GET" action="{{ route('teacher.tasks.overview') }}" class="flex gap-3">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        <div class="relative">
                            <select name="course_id" class="h-11 min-w-[200px] rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] outline-none cursor-pointer appearance-none bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIiIGhlaWdodD0iOCIgdmlld0JveD0iMCAwIDEyIDgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGQ9Ik0xIDFMNiA2TDExIDEiIHN0cm9rZT0iIzY0NzQ4YiIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KPC9zdmc+')] bg-no-repeat bg-right pr-10">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                        {{ $course->course_number }} - {{ $course->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($hasFilters)
                        <a href="{{ route('teacher.tasks.overview', ['tab' => $activeTab]) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-700 text-sm font-medium hover:bg-slate-100">
                            Clear
                        </a>
                        @endif
                    </form>

                    @if($activeTab === 'assignments')
                        @if($courseId > 0)
                            @php
                                $selectedCourse = $courses->firstWhere('id', $courseId);
                            @endphp
                            @if($selectedCourse)
                                <a href="{{ route('teacher.assignments.create', $selectedCourse) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                    Create Assignment
                                </a>
                            @else
                                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                    Select Course First
                                </a>
                            @endif
                        @else
                            <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                Select Course to Create Assignment
                            </a>
                        @endif
                    @elseif($activeTab === 'quizzes')
                        @if($courseId > 0)
                            @php
                                $selectedCourse = $courses->firstWhere('id', $courseId);
                            @endphp
                            @if($selectedCourse)
                                <a href="{{ route('teacher.quizzes.create', $selectedCourse) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                    Create Quiz
                                </a>
                            @else
                                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                    Select Course First
                                </a>
                            @endif
                        @else
                            <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                Select Course to Create Quiz
                            </a>
                        @endif
                    @elseif($activeTab === 'exams')
                        @if($courseId > 0)
                            @php
                                $selectedCourse = $courses->firstWhere('id', $courseId);
                            @endphp
                            @if($selectedCourse)
                                <a href="{{ route('teacher.exams.create', $selectedCourse) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                    Schedule Exam
                                </a>
                            @else
                                <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                    Select Course First
                                </a>
                            @endif
                        @else
                            <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                                Select Course to Schedule Exam
                            </a>
                        @endif
                    @else
                        <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                            <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                            Go to Courses
                        </a>
                    @endif
                </div>
            </div>

            <!-- Task Type Tabs -->
            <div class="mt-4 flex border-b border-slate-200">
                <a href="{{ route('teacher.tasks.overview', ['tab' => 'assignments', 'course_id' => $courseId]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'assignments' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Assignments
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $assignments->count() }}</span>
                    </span>
                </a>
                <a href="{{ route('teacher.tasks.overview', ['tab' => 'exams', 'course_id' => $courseId]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'exams' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                        Exams
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $exams->count() }}</span>
                    </span>
                </a>
                <a href="{{ route('teacher.tasks.overview', ['tab' => 'quizzes', 'course_id' => $courseId]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'quizzes' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="help-circle" class="h-4 w-4"></i>
                        Quizzes
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $quizzes->count() }}</span>
                    </span>
                </a>
            </div>

            <!-- Active Filters Display -->
            @if($hasFilters)
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-500">Active filters:</span>
                @if($courseId > 0)
                    @php
                        $selectedCourse = $courses->firstWhere('id', $courseId);
                    @endphp
                    @if($selectedCourse)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium">
                            <i data-lucide="book-open" class="h-3 w-3"></i>
                            {{ $selectedCourse->course_number }}
                        </span>
                    @endif
                @endif
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            @if($activeTab === 'assignments')
                @include('teacher.tasks.partials.assignments-table')
            @elseif($activeTab === 'exams')
                @include('teacher.tasks.partials.exams-table')
            @elseif($activeTab === 'quizzes')
                @include('teacher.tasks.partials.quizzes-table')
            @endif
        </div>
    </div>
@endsection
