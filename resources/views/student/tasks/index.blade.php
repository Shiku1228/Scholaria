@extends('layouts.student')

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
                    <div class="mt-1 text-sm text-slate-500">View and manage your assignments, exams, and quizzes.</div>
                </div>

                <form method="GET" action="{{ route('student.tasks.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="relative">
                        <select name="course_id" class="h-11 min-w-[200px] rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] outline-none cursor-pointer">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $courseId == $course->id ? 'selected' : '' }}>
                                    {{ $course->course_number }} - {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($hasFilters)
                    <a href="{{ route('student.tasks.index', ['tab' => $activeTab]) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-700 text-sm font-medium hover:bg-slate-100">
                        Clear
                    </a>
                    @endif
                </form>
            </div>

            <!-- Task Type Tabs -->
            <div class="mt-4 flex border-b border-slate-200">
                <a href="{{ route('student.tasks.index', ['tab' => 'assignments', 'course_id' => $courseId]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'assignments' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                        Assignments
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $assignments->count() }}</span>
                    </span>
                </a>
                <a href="{{ route('student.tasks.index', ['tab' => 'exams', 'course_id' => $courseId]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'exams' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                        Exams
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $exams->count() }}</span>
                    </span>
                </a>
                <a href="{{ route('student.tasks.index', ['tab' => 'quizzes', 'course_id' => $courseId]) }}"
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
                @include('student.tasks.partials.assignments-table')
            @elseif($activeTab === 'exams')
                @include('student.tasks.partials.exams-table')
            @elseif($activeTab === 'quizzes')
                @include('student.tasks.partials.quizzes-table')
            @endif
        </div>
    </div>
@endsection
