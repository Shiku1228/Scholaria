@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $exam->title }}</span>
                @if($exam->isOnline())
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                        <i data-lucide="laptop" class="h-3 w-3 mr-1"></i>Online
                    </span>
                @else
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        <i data-lucide="calendar" class="h-3 w-3 mr-1"></i>Scheduled
                    </span>
                @endif
                @if($exam->is_published)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        Published
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                        Draft
                    </span>
                @endif
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $exam->course->course_number ?? $exam->course->title }}</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('teacher.exams.edit', $exam) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="pencil" class="h-4 w-4 mr-2"></i>Edit
            </a>
            @if($exam->isOnline())
                <a href="{{ route('teacher.exams.questions', $exam) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="help-circle" class="h-4 w-4 mr-2"></i>Questions
                </a>
            @endif
            <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-red-200 bg-white text-sm font-semibold text-red-600 hover:bg-red-50" onclick="return confirm('Delete this exam?')">
                    <i data-lucide="trash-2" class="h-4 w-4 mr-2"></i>Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Due Date Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="calendar-clock" class="h-5 w-5 text-amber-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Exam Date</div>
                    <div class="text-sm font-semibold text-slate-900">
                        @if($exam->exam_date)
                            {{ $exam->exam_date->format('M d, Y') }}
                            <span class="text-xs text-slate-500 block">{{ $exam->exam_date->format('g:i A') }}</span>
                        @else
                            Not scheduled
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Duration Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i data-lucide="clock" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Duration</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $exam->duration ?? 120 }} minutes</div>
                </div>
            </div>
        </div>

        {{-- Max Score Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <i data-lucide="target" class="h-5 w-5 text-emerald-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Max Score</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $exam->max_score ?? 100 }} points</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Description & Instructions --}}
    @if($exam->description || $exam->instructions)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="p-5 space-y-4">
                @if($exam->description)
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-2">
                            <i data-lucide="align-left" class="h-4 w-4 text-slate-500"></i>
                            Description
                        </div>
                        <p class="text-sm text-slate-700">{{ $exam->description }}</p>
                    </div>
                @endif
                @if($exam->instructions)
                    <div>
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-2">
                            <i data-lucide="info" class="h-4 w-4 text-slate-500"></i>
                            Instructions
                        </div>
                        <p class="text-sm text-slate-700">{{ $exam->instructions }}</p>
                    </div>
                @endif
                @if($exam->location)
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="map-pin" class="h-4 w-4 text-rose-500"></i>
                        <span class="text-slate-700"><strong>Location:</strong> {{ $exam->location }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Online Exam Info --}}
    @if($exam->isOnline())
        <div class="bg-purple-50 rounded-xl border border-purple-200 overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-purple-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-semibold text-purple-900">
                        <i data-lucide="laptop" class="h-4 w-4"></i>
                        Online Exam Details
                    </div>
                    @if(!$exam->is_published)
                        <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center h-8 px-4 rounded-lg bg-green-600 text-white text-xs font-medium hover:bg-green-700" {{ $exam->hasQuestions() ? '' : 'disabled' }}>
                                <i data-lucide="check" class="h-3 w-3 mr-1"></i>Publish
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('teacher.exams.unpublish', $exam) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center h-8 px-4 rounded-lg bg-slate-600 text-white text-xs font-medium hover:bg-slate-700">
                                <i data-lucide="eye-off" class="h-3 w-3 mr-1"></i>Unpublish
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i data-lucide="help-circle" class="h-4 w-4 text-purple-600"></i>
                        </div>
                        <div>
                            <div class="text-xs text-purple-700">Questions</div>
                            <div class="text-sm font-semibold text-purple-900">{{ $exam->questions->count() }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i data-lucide="target" class="h-4 w-4 text-purple-600"></i>
                        </div>
                        <div>
                            <div class="text-xs text-purple-700">Total Points</div>
                            <div class="text-sm font-semibold text-purple-900">{{ $exam->getTotalPoints() }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i data-lucide="users" class="h-4 w-4 text-purple-600"></i>
                        </div>
                        <div>
                            <div class="text-xs text-purple-700">Submissions</div>
                            <div class="text-sm font-semibold text-purple-900">{{ $exam->getSubmissionCount() }}</div>
                        </div>
                    </div>
                </div>
                @if(!$exam->hasQuestions())
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-center gap-2 text-sm text-amber-800">
                            <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                            <span>No questions added yet. <a href="{{ route('teacher.exams.questions', $exam) }}" class="underline font-medium">Add questions</a> to publish this exam.</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Student Attempts --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="users" class="h-4 w-4 text-slate-500"></i>
                Student Attempts
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                        <th class="py-3 px-4">Student</th>
                        <th class="py-3 px-4">Started</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Score</th>
                        <th class="py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($attempts as $attempt)
                        <tr class="text-slate-700">
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $attempt->student->name ?? 'Unknown' }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $attempt->started_at->format('M d, Y g:i A') }}</td>
                            <td class="py-3 px-4">
                                @if($attempt->isGraded())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Graded</span>
                                @elseif($attempt->isSubmitted())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Submitted</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">In Progress</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($attempt->score !== null)
                                    <span class="font-medium text-slate-900">{{ $attempt->score }}</span>
                                    <span class="text-slate-500">/ {{ $attempt->max_score }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <a href="#" class="text-[#0b2d6b] hover:text-[#0a275c] text-sm font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-500">
                                <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                                    <i data-lucide="users" class="h-6 w-6 text-slate-400"></i>
                                </div>
                                <p>No student attempts yet.</p>
                                @if($exam->exam_date && $exam->exam_date->isFuture())
                                    <p class="text-xs text-slate-400 mt-1">Exam scheduled for {{ $exam->exam_date->format('M d, Y \a\t g:i A') }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($attempts->hasPages())
            <div class="px-4 py-3 border-t border-slate-200">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>
@endsection
