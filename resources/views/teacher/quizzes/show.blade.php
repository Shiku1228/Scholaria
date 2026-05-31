@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="help-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $quiz->title }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $quiz->course->course_number }} • {{ $quiz->course->title }}</div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('teacher.quizzes.scores.export', $quiz) }}"
               class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-emerald-300 bg-emerald-50 text-sm font-semibold text-emerald-700 hover:bg-emerald-100 transition-colors">
                <i data-lucide="download" class="h-4 w-4 mr-2"></i>Export Excel
            </a>
            <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="pencil" class="h-4 w-4 mr-2"></i>Edit
            </a>
            <form method="POST" action="{{ route('teacher.quizzes.destroy', $quiz) }}" class="inline" onsubmit="return confirm('Delete this quiz?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-red-200 bg-red-50 text-sm font-semibold text-red-700 hover:bg-red-100">
                    <i data-lucide="trash-2" class="h-4 w-4 mr-2"></i>Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i data-lucide="users" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Total Attempts</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attempts->count() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <i data-lucide="check-circle" class="h-5 w-5 text-emerald-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Completed</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attempts->whereNotNull('submitted_at')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i data-lucide="user-check" class="h-5 w-5 text-purple-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Unique Students</div>
                    <div class="text-sm font-semibold text-slate-900">{{ $attempts->pluck('student_id')->unique()->count() }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="target" class="h-5 w-5 text-amber-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Average Score</div>
                    <div class="text-sm font-semibold text-slate-900">{{ round($attempts->whereNotNull('submitted_at')->avg('score') ?? 0, 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Quiz Details --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <i data-lucide="file-text" class="h-4 w-4 text-slate-500"></i>
                        Quiz Details
                    </div>
                    @if($quiz->is_published)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Published</span>
                    @else
                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full">Draft</span>
                    @endif
                </div>
                <div class="p-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="book-open" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Course:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->course->title ?? $quiz->course->course_number }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Start Date:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->start_date ? $quiz->start_date->format('M j, Y g:i A') : 'Immediately available' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar-days" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Due Date:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->due_date ? $quiz->due_date->format('M j, Y g:i A') : 'No due date' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Time Limit:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->time_limit ?? 15 }} minutes</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="target" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Max Score:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->max_score ?? ($quiz->points ?? 0) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="rotate-ccw" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Attempts Allowed:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->attempts_allowed ?? 1 }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="help-circle" class="h-4 w-4 text-slate-400"></i>
                                <span class="text-slate-600">Questions:</span>
                                <span class="font-medium text-slate-900">{{ $quiz->questions()->count() }} {{ $quiz->shuffle_questions ? '(shuffled)' : '' }}</span>
                            </div>
                            @if($quiz->random_subset_count)
                                <div class="flex items-center gap-2">
                                    <i data-lucide="filter" class="h-4 w-4 text-slate-400"></i>
                                    <span class="text-slate-600">Random Subset:</span>
                                    <span class="font-medium text-slate-900">Choose {{ $quiz->random_subset_count }} questions</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    @if($quiz->description)
                        <div class="mt-4 pt-4 border-t border-slate-100">
                            <p class="text-sm text-slate-600">{{ $quiz->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Student Attempts --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <i data-lucide="users" class="h-4 w-4 text-slate-500"></i>
                        Student Attempts
                    </div>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse($attempts as $attempt)
                        <div class="p-4 flex items-center justify-between hover:bg-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-slate-100 flex items-center justify-center">
                                    <i data-lucide="user" class="h-4 w-4 text-slate-500"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $attempt->student->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-500">
                                        Started {{ $attempt->started_at?->diffForHumans() ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($attempt->submitted_at)
                                    <div class="text-right">
                                        <div class="text-sm font-semibold text-slate-900">{{ $attempt->score ?? 0 }} / {{ $quiz->max_score ?? ($quiz->points ?? 0) }}</div>
                                        <div class="text-xs text-emerald-600">Completed</div>
                                    </div>
                                    <a href="{{ route('teacher.quizzes.attempts.show', [$quiz, $attempt]) }}"
                                        class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c] transition-colors">
                                        <i data-lucide="eye" class="h-3.5 w-3.5 mr-1"></i>Review
                                    </a>
                                @else
                                    <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full">In Progress</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-10 text-center">
                            <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="users" class="h-8 w-8 text-slate-400"></i>
                            </div>
                            <p class="text-sm text-slate-500">No student attempts yet.</p>
                            <p class="text-xs text-slate-400 mt-1">Students will appear here when they take the quiz.</p>
                        </div>
                    @endforelse
                </div>
                @if($attempts->count() > 0)
                    <div class="px-5 py-3 border-t border-slate-200 bg-slate-50">
                        {{ $attempts->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="text-sm font-semibold text-slate-800">Quick Actions</div>
                </div>
                <div class="p-4 space-y-2">
                    <a href="{{ route('teacher.quizzes.questions', $quiz) }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 border border-slate-200">
                        <div class="h-8 w-8 rounded-lg bg-amber-100 flex items-center justify-center">
                            <i data-lucide="help-circle" class="h-4 w-4 text-amber-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-900">Manage Questions</div>
                            <div class="text-xs text-slate-500">{{ $quiz->questions()->count() }} questions</div>
                        </div>
                        <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400"></i>
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 border border-slate-200 opacity-50 cursor-not-allowed">
                        <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i data-lucide="bar-chart-2" class="h-4 w-4 text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-slate-900">View Statistics</div>
                            <div class="text-xs text-slate-500">Coming soon</div>
                        </div>
                    </a>
                </div>
            </div>            {{-- Status & Release Controls --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                    <div class="text-sm font-semibold text-slate-800">Status & Release Controls</div>
                </div>
                <div class="p-4 space-y-4">
                    @if($quiz->is_published)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-start gap-2.5">
                            <i data-lucide="check-circle" class="h-5 w-5 text-green-600 mt-0.5"></i>
                            <div>
                                <div class="text-sm font-semibold text-green-900">Quiz is Published</div>
                                <p class="text-xs text-green-700 mt-0.5">Active and visible to students.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('teacher.quizzes.unpublish', $quiz) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center h-10 px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                <i data-lucide="eye-off" class="h-4 w-4 mr-2"></i>Unpublish (Revert to Draft)
                            </button>
                        </form>
                    @else
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2.5">
                            <i data-lucide="alert-circle" class="h-5 w-5 text-amber-600 mt-0.5"></i>
                            <div>
                                <div class="text-sm font-semibold text-amber-900">Quiz is Draft</div>
                                <p class="text-xs text-amber-700 mt-0.5">Hidden from students.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('teacher.quizzes.publish', $quiz) }}">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center h-10 px-4 rounded-lg bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] shadow-sm" {{ $quiz->questions()->count() === 0 ? 'disabled' : '' }}>
                                <i data-lucide="upload" class="h-4 w-4 mr-2"></i>Publish Quiz
                            </button>
                            @if($quiz->questions()->count() === 0)
                                <p class="text-xs text-amber-750 mt-2 text-center">Add questions before publishing.</p>
                            @endif
                        </form>
                    @endif

                    @if($quiz->feedback_type === 'delayed')
                        <div class="border-t border-slate-100 pt-4 space-y-3">
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>Feedback Release Mode:</span>
                                <span class="font-semibold text-purple-700">Delayed</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>Results Released:</span>
                                @if($quiz->results_released)
                                    <span class="inline-flex items-center text-green-700 font-semibold">
                                        <i data-lucide="check" class="h-3 w-3 mr-1"></i>Released
                                    </span>
                                @else
                                    <span class="text-amber-700 font-semibold">Pending Release</span>
                                @endif
                            </div>

                            @if(!$quiz->results_released)
                                <form method="POST" action="{{ route('teacher.quizzes.release-results', $quiz) }}">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-655 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm">
                                        <i data-lucide="unlock" class="h-4 w-4 mr-2"></i>Release Results & Feedback
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="border-t border-slate-100 pt-4 text-xs text-slate-500 flex items-center justify-between">
                            <span>Feedback Release Mode:</span>
                            <span class="font-semibold text-slate-700">Instant on Submit</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
