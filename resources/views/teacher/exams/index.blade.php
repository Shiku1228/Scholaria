@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
            <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
            <span>Exams</span>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2">
                <select name="course_id" onchange="this.form.submit()"
                    class="h-10 rounded-xl border border-slate-200 bg-white text-sm font-medium text-slate-700 px-3 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ $filters['course_id'] == $course->id ? 'selected' : '' }}>
                            {{ $course->title ?: $course->course_number }}
                        </option>
                    @endforeach
                </select>
            </form>
            @if($filters['course_id'] > 0)
                @php $selectedCourse = $courses->firstWhere('id', $filters['course_id']); @endphp
                @if($selectedCourse)
                    <a href="{{ route('teacher.exams.create', $selectedCourse) }}"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors shadow-sm">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>New Exam
                    </a>
                @endif
            @else
                <a href="{{ route('teacher.courses.index') }}"
                    class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Select Course First
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-6 text-sm text-emerald-700 flex items-center gap-2">
            <i data-lucide="check-circle" class="h-4 w-4 flex-shrink-0"></i>{{ session('success') }}
        </div>
    @endif

    @forelse($exams as $exam)
        @php
            $isFaceToFace = $exam->isFaceToFace();
            $submissionCount = $exam->getSubmissionCount();
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-4 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 rounded-xl {{ $isFaceToFace ? 'bg-amber-100' : 'bg-purple-100' }} flex items-center justify-center flex-shrink-0">
                        <i data-lucide="{{ $isFaceToFace ? 'users' : 'laptop' }}" class="h-6 w-6 {{ $isFaceToFace ? 'text-amber-600' : 'text-purple-600' }}"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h3 class="font-semibold text-slate-900 text-sm">{{ $exam->title }}</h3>
                            @if($isFaceToFace)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    <i data-lucide="users" class="h-3 w-3 mr-1"></i>Face-to-Face
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                    <i data-lucide="laptop" class="h-3 w-3 mr-1"></i>Online
                                </span>
                            @endif
                            @if($exam->is_published)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Published</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Draft</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mb-2">{{ $exam->course->title ?? $exam->course->course_number }}</p>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                            @if($exam->exam_date)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="h-3 w-3"></i>
                                    {{ $exam->exam_date->format('M d, Y g:i A') }}
                                </span>
                            @endif
                            @if(!$isFaceToFace && $exam->duration)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="clock" class="h-3 w-3"></i>
                                    {{ $exam->duration }} min
                                </span>
                            @endif
                            @if($isFaceToFace && $exam->location)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="map-pin" class="h-3 w-3"></i>
                                    {{ $exam->location }}
                                </span>
                            @endif
                            @if(!$isFaceToFace)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="help-circle" class="h-3 w-3"></i>
                                    {{ $exam->questions->count() }} questions
                                </span>
                            @endif
                            <span class="flex items-center gap-1">
                                <i data-lucide="target" class="h-3 w-3"></i>
                                {{ $exam->max_score }} pts
                            </span>
                            @if(!$isFaceToFace)
                                <span class="flex items-center gap-1">
                                    <i data-lucide="inbox" class="h-3 w-3"></i>
                                    {{ $submissionCount }} submission{{ $submissionCount !== 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('teacher.exams.show', $exam) }}"
                        class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="eye" class="h-3.5 w-3.5 mr-1.5"></i>View
                    </a>
                    <a href="{{ route('teacher.exams.edit', $exam) }}"
                        class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="pencil" class="h-3.5 w-3.5 mr-1.5"></i>Edit
                    </a>
                    @if(!$isFaceToFace)
                        <a href="{{ route('teacher.exams.questions', $exam) }}"
                            class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c] transition-colors">
                            <i data-lucide="list-checks" class="h-3.5 w-3.5 mr-1.5"></i>Questions
                        </a>
                    @endif
                    <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-lg border border-red-200 bg-white text-red-500 hover:bg-red-50 transition-colors"
                            onclick="return confirm('Delete this exam?')">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
            <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="file-text" class="h-8 w-8 text-slate-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">No Exams Found</h3>
            <p class="text-sm text-slate-500 mb-6">
                @if($filters['course_id'] > 0)
                    No exams for this course yet. Create your first exam!
                @else
                    You haven't created any exams yet. Select a course to get started.
                @endif
            </p>
            <a href="{{ route('teacher.courses.index') }}"
                class="inline-flex items-center justify-center h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] transition-colors">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Go to Courses
            </a>
        </div>
    @endforelse

    @if($exams->hasPages())
        <div class="mt-6">{{ $exams->links() }}</div>
    @endif
@endsection
