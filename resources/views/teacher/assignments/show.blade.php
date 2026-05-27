@extends('layouts.teacher')

@section('content')
    {{-- Header Section --}}
    <div class="flex items-start justify-between gap-4 mb-8">
        <div class="flex-1">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] flex items-center justify-center shadow-lg">
                    <i data-lucide="file-text" class="h-6 w-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $assignment->title }}</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
            </a>
            <a href="{{ route('teacher.assignments.edit', [$course, $assignment]) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all">
                <i data-lucide="pencil" class="h-4 w-4 mr-2"></i>Edit
            </a>
            <form method="POST" action="{{ route('teacher.assignments.destroy', [$course, $assignment]) }}" onsubmit="return confirm('Delete this assignment? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center h-11 px-4 rounded-lg border border-red-200 bg-red-50 text-sm font-semibold text-red-700 hover:bg-red-100 transition-colors shadow-sm">
                    <i data-lucide="trash-2" class="h-4 w-4 mr-2"></i>Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Assignment Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        {{-- Due Date Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="calendar-clock" class="h-6 w-6 text-amber-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Due Date</div>
                    <div class="mt-1">
                        @if($assignment->due_date)
                            @php
                                $daysUntilDue = now()->diffInDays($assignment->due_date);
                                $isOverdue = $assignment->due_date < now();
                                $isUrgent = $daysUntilDue <= 3 && !$isOverdue;
                            @endphp
                            <div class="text-sm font-bold text-slate-900">{{ $assignment->due_date->format('M d, Y') }}</div>
                            <div class="text-xs {{ $isOverdue ? 'text-red-600 font-semibold' : ($isUrgent ? 'text-amber-600 font-semibold' : 'text-slate-500') }}">
                                @if($isOverdue)
                                    <i data-lucide="alert-circle" class="h-3 w-3 inline mr-1"></i>Overdue
                                @elseif($isUrgent)
                                    <i data-lucide="clock" class="h-3 w-3 inline mr-1"></i>{{ $daysUntilDue }} day{{ $daysUntilDue !== 1 ? 's' : '' }} left
                                @else
                                    {{ $assignment->due_date->format('g:i A') }}
                                @endif
                            </div>
                        @else
                            <div class="text-sm font-semibold text-slate-400">No due date set</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Max Score Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="target" class="h-6 w-6 text-emerald-600"></i>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Max Score</div>
                    <div class="mt-1 text-sm font-bold text-slate-900">{{ $assignment->max_score ?? 100 }} <span class="text-xs font-normal text-slate-500">points</span></div>
                </div>
            </div>
        </div>

        {{-- Submissions Count Card --}}
        <div class="group relative bg-white rounded-xl shadow-sm border border-slate-200 hover:shadow-md hover:border-slate-300 transition-all duration-200 p-5">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent opacity-0 group-hover:opacity-100 rounded-xl transition-opacity duration-200"></div>
            <div class="relative flex items-center gap-4">
                <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <i data-lucide="users" class="h-6 w-6 text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <div class="text-xs font-medium text-slate-500 uppercase tracking-wide">Submissions</div>
                    @php
                        $submissionCount = $submissions instanceof \Illuminate\Support\Collection ? $submissions->count() : $submissions->total();
                    @endphp
                    <div class="mt-1 text-sm font-bold text-slate-900">{{ $submissionCount }} <span class="text-xs font-normal text-slate-500">submitted</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Description Section --}}
    @if($assignment->description)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center">
                    <i data-lucide="align-left" class="h-5 w-5 text-slate-600"></i>
                </div>
                <h2 class="text-lg font-semibold text-slate-900">Description</h2>
            </div>
            <div class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $assignment->description }}</div>
        </div>
    @endif

    {{-- Submissions Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-lg bg-slate-200 flex items-center justify-center">
                            <i data-lucide="inbox" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Student Submissions</h2>
                            <p class="text-xs text-slate-500 mt-1">Review, grade, and provide feedback to students</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            @if ($submissions instanceof \Illuminate\Support\Collection ? $submissions->isEmpty() : $submissions->count() === 0)
                <div class="text-center py-12">
                    <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="inbox" class="h-8 w-8 text-slate-400"></i>
                    </div>
                    <h3 class="text-base font-semibold text-slate-900 mb-1">No submissions yet</h3>
                    <p class="text-sm text-slate-500">Students will appear here once they submit their work</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($submissions as $s)
                        @php
                            $isLate = $s->submitted_at && $assignment->due_date && $s->submitted_at->isAfter($assignment->due_date);
                            $hasScore = $s->score !== null && $s->score !== '';
                            $scorePercentage = $hasScore && $assignment->max_score ? ($s->score / $assignment->max_score) * 100 : 0;
                        @endphp
                        <div class="group relative bg-slate-50 rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow-sm transition-all duration-200 p-5">
                            {{-- Top Row: Student Info and Status --}}
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ strtoupper(substr($s->student?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-900 truncate">{{ $s->student?->name ?? 'Unknown Student' }}</p>
                                        <p class="text-xs text-slate-500">
                                            @if($s->submitted_at)
                                                {{ $s->submitted_at->format('M d, Y \a\t g:i A') }}
                                                @if($isLate)
                                                    <span class="ml-2 inline-flex items-center gap-1 text-red-600 font-semibold">
                                                        <i data-lucide="alert-circle" class="h-3 w-3"></i>Late
                                                    </span>
                                                @endif
                                            @elseif($s->created_at)
                                                {{ $s->created_at->format('M d, Y \a\t g:i A') }}
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                
                                {{-- Score Badge --}}
                                <div class="flex-shrink-0 text-right">
                                    @if($hasScore)
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg @if($scorePercentage >= 80) bg-emerald-100 @elseif($scorePercentage >= 60) bg-amber-100 @else bg-red-100 @endif">
                                            <span class="text-sm font-bold @if($scorePercentage >= 80) text-emerald-700 @elseif($scorePercentage >= 60) text-amber-700 @else text-red-700 @endif">{{ $s->score }}</span>
                                            <span class="text-xs @if($scorePercentage >= 80) text-emerald-600 @elseif($scorePercentage >= 60) text-amber-600 @else text-red-600 @endif">/{{ $assignment->max_score ?? 100 }}</span>
                                        </div>
                                    @else
                                        <span class="inline-block px-3 py-1 rounded-lg bg-slate-200 text-xs font-medium text-slate-600">Not graded</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Submission Content Row --}}
                            <div class="mb-4 pb-4 border-b border-slate-200">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 text-sm text-slate-600">
                                        @switch($s->submission_type ?? 'file')
                                            @case('text')
                                                <i data-lucide="file-text" class="h-4 w-4 text-blue-500"></i>
                                                <span>Text submission</span>
                                                @if($s->content)
                                                    <button onclick="document.getElementById('text-modal-{{ $s->id }}').classList.remove('hidden')" class="ml-auto text-[#0b2d6b] hover:text-[#0a275c] font-semibold text-xs hover:underline">View</button>
                                                @endif
                                                @break
                                            @case('link')
                                                <i data-lucide="link" class="h-4 w-4 text-purple-500"></i>
                                                <span>Link submission</span>
                                                @if($s->content)
                                                    <a href="{{ $s->content }}" target="_blank" class="ml-auto text-[#0b2d6b] hover:text-[#0a275c] font-semibold text-xs hover:underline flex items-center gap-1">
                                                        Open <i data-lucide="external-link" class="h-3 w-3"></i>
                                                    </a>
                                                @endif
                                                @break
                                            @case('file')
                                            @default
                                                <i data-lucide="file" class="h-4 w-4 text-amber-500"></i>
                                                <span>File submission</span>
                                                @if ($s->file_path)
                                                    <a href="{{ asset('storage/' . $s->file_path) }}" target="_blank" class="ml-auto text-[#0b2d6b] hover:text-[#0a275c] font-semibold text-xs hover:underline flex items-center gap-1">
                                                        Download <i data-lucide="download" class="h-3 w-3"></i>
                                                    </a>
                                                @endif
                                                @break
                                        @endswitch
                                    </div>
                                </div>
                            </div>

                            {{-- Grading Form Row --}}
                            <form method="POST" action="{{ route('teacher.submissions.update', [$course, $assignment, $s]) }}" class="flex items-end gap-3">
                                @csrf
                                @method('PATCH')
                                
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-slate-600 mb-2">Score</label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" name="score" value="{{ old('score', $s->score) }}" min="0" max="{{ $assignment->max_score ?? 100 }}" class="w-20 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20" placeholder="-" />
                                        <span class="text-xs text-slate-500">/{{ $assignment->max_score ?? 100 }}</span>
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-slate-600 mb-2">Feedback</label>
                                    <input type="text" name="feedback" value="{{ old('feedback', $s->feedback) }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20" placeholder="Add feedback..." />
                                </div>

                                <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c] transition-colors">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    <span class="hidden sm:inline ml-2">Save</span>
                                </button>
                            </form>

                            {{-- Text Modal --}}
                            @if($s->submission_type === 'text' && $s->content)
                                <div id="text-modal-{{ $s->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                    <div class="bg-white rounded-xl max-w-3xl w-full max-h-[85vh] flex flex-col shadow-2xl">
                                        {{-- Modal Header --}}
                                        <div class="p-6 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                                    <i data-lucide="file-text" class="h-5 w-5 text-blue-600"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-semibold text-slate-900">{{ $s->student?->name ?? 'Student' }}'s Submission</h3>
                                                    <p class="text-xs text-slate-500 mt-0.5">
                                                        <i data-lucide="calendar" class="h-3 w-3 inline mr-1"></i>
                                                        {{ $s->submitted_at?->format('M d, Y \a\t g:i A') ?? 'Date unknown' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <button onclick="document.getElementById('text-modal-{{ $s->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 hover:bg-slate-100 h-10 w-10 rounded-lg flex items-center justify-center transition-colors">
                                                <i data-lucide="x" class="h-5 w-5"></i>
                                            </button>
                                        </div>

                                        {{-- Modal Body --}}
                                        <div class="flex-1 overflow-y-auto p-8 bg-gradient-to-b from-white via-white to-slate-50">
                                            <div class="prose prose-sm max-w-none prose-headings:text-slate-900 prose-p:text-slate-700 prose-strong:text-slate-900 prose-code:bg-slate-100 prose-code:text-slate-900 prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-pre:bg-slate-900 prose-pre:text-slate-100">
                                                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-sm">
                                                    <div class="text-base leading-relaxed text-slate-800 whitespace-pre-wrap break-words [&>p]:mb-4 [&>p:last-child]:mb-0 [&>*]:mb-4 [&>*:last-child]:mb-0">
                                                        {!! nl2br(htmlspecialchars(strip_tags($s->content), ENT_QUOTES)) !!}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Modal Footer --}}
                                        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-2">
                                            <button onclick="document.getElementById('text-modal-{{ $s->id }}').classList.add('hidden')" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-100 transition-colors">
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if (method_exists($submissions, 'links'))
                    <div class="mt-6 pt-6 border-t border-slate-200">{{ $submissions->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
