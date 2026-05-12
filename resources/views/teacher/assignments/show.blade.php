@extends('layouts.teacher')

@section('content')
    {{-- Header Section --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="file-text" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>{{ $assignment->title }}</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
            </a>
            <a href="{{ route('teacher.assignments.edit', [$course, $assignment]) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                <i data-lucide="pencil" class="h-4 w-4 mr-2"></i>Edit
            </a>
        </div>
    </div>

    {{-- Assignment Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Due Date Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="calendar-clock" class="h-5 w-5 text-amber-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Due Date</div>
                    <div class="text-sm font-semibold text-slate-900">
                        @if($assignment->due_date)
                            {{ $assignment->due_date->format('M d, Y') }}
                            <span class="text-xs text-slate-500 block">{{ $assignment->due_date->format('g:i A') }}</span>
                        @else
                            No due date
                        @endif
                    </div>
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
                    <div class="text-sm font-semibold text-slate-900">{{ $assignment->max_score ?? 100 }} points</div>
                </div>
            </div>
        </div>

        {{-- Submissions Count Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i data-lucide="users" class="h-5 w-5 text-blue-600"></i>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Submissions</div>
                    <div class="text-sm font-semibold text-slate-900">
                        @php
                            $submissionCount = $submissions instanceof \Illuminate\Support\Collection ? $submissions->count() : $submissions->total();
                        @endphp
                        {{ $submissionCount }} submitted
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Description Section --}}
    @if($assignment->description)
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-3">
                <i data-lucide="align-left" class="h-4 w-4 text-slate-500"></i>
                Description
            </div>
            <div class="text-sm text-slate-700 leading-relaxed">{{ $assignment->description }}</div>
        </div>
    @endif

    {{-- Submissions Section --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i data-lucide="inbox" class="h-4 w-4 text-slate-500"></i>
                Student Submissions
            </div>
            <div class="text-xs text-slate-500 mt-1">Review submissions, assign scores, and provide feedback</div>
        </div>

        <div class="p-5">
            @if ($submissions instanceof \Illuminate\Support\Collection ? $submissions->isEmpty() : $submissions->count() === 0)
                <div class="text-center py-10">
                    <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <i data-lucide="inbox" class="h-6 w-6 text-slate-400"></i>
                    </div>
                    <div class="text-sm text-slate-500">No submissions yet</div>
                    <div class="text-xs text-slate-400 mt-1">Students will appear here once they submit their work</div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-medium text-slate-500 border-b border-slate-200">
                                <th class="py-3 pr-4">Student</th>
                                <th class="py-3 pr-4">Submitted</th>
                                <th class="py-3 pr-4">Submission</th>
                                <th class="py-3 pr-4 w-32">Score / {{ $assignment->max_score ?? 100 }}</th>
                                <th class="py-3">Feedback</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($submissions as $s)
                                <tr class="align-top">
                                    <td class="py-4 pr-4">
                                        <div class="flex items-center gap-2">
                                            <div class="h-8 w-8 rounded-full bg-[#0b2d6b] text-white flex items-center justify-center text-xs font-semibold">
                                                {{ strtoupper(substr($s->student?->name ?? '?', 0, 1)) }}
                                            </div>
                                            <span class="font-medium text-slate-900">{{ $s->student?->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4">
                                        @if($s->submitted_at)
                                            <div class="text-slate-700">{{ $s->submitted_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-slate-500">{{ $s->submitted_at->format('g:i A') }}</div>
                                        @elseif($s->created_at)
                                            <div class="text-slate-700">{{ $s->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-slate-500">{{ $s->created_at->format('g:i A') }}</div>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-4 pr-4">
                                        @switch($s->submission_type ?? 'file')
                                            @case('text')
                                                @if($s->content)
                                                    <button onclick="document.getElementById('text-modal-{{ $s->id }}').classList.remove('hidden')" class="inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                                        <i data-lucide="file-text" class="h-4 w-4"></i>
                                                        View Text
                                                    </button>
                                                    {{-- Text Modal --}}
                                                    <div id="text-modal-{{ $s->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                                                        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[80vh] flex flex-col">
                                                            <div class="p-4 border-b border-slate-200 flex items-center justify-between">
                                                                <div class="font-semibold text-slate-900">Text Submission</div>
                                                                <button onclick="document.getElementById('text-modal-{{ $s->id }}').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                                                    <i data-lucide="x" class="h-5 w-5"></i>
                                                                </button>
                                                            </div>
                                                            <div class="p-4 overflow-y-auto">
                                                                <div class="bg-slate-50 rounded-lg p-4 text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $s->content }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-slate-400 text-xs">No content</span>
                                                @endif
                                                @break
                                            @case('link')
                                                @if($s->content)
                                                    <a href="{{ $s->content }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline truncate max-w-[200px]">
                                                        <i data-lucide="external-link" class="h-4 w-4 flex-shrink-0"></i>
                                                        <span class="truncate">{{ $s->content }}</span>
                                                    </a>
                                                @else
                                                    <span class="text-slate-400 text-xs">No link</span>
                                                @endif
                                                @break
                                            @case('file')
                                            @default
                                                @if ($s->file_path)
                                                    <a href="{{ asset('storage/' . $s->file_path) }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-medium text-[#0b2d6b] hover:text-[#0a275c] hover:underline">
                                                        <i data-lucide="download" class="h-4 w-4"></i>
                                                        Download
                                                    </a>
                                                @else
                                                    <span class="text-slate-400 text-xs">No file</span>
                                                @endif
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="py-4 pr-4">
                                        <form method="POST" action="{{ route('teacher.submissions.update', [$course, $assignment, $s]) }}" id="grade-form-{{ $s->id }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="score" value="{{ old('score', $s->score) }}" min="0" max="{{ $assignment->max_score ?? 100 }}" class="w-24 rounded-lg border-slate-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="-" />
                                    </td>
                                    <td class="py-4">
                                        <div class="flex items-start gap-2">
                                            <input name="feedback" value="{{ old('feedback', $s->feedback) }}" class="flex-1 min-w-[200px] rounded-lg border-slate-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="Add feedback..." />
                                            <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c] transition-colors">
                                                <i data-lucide="check" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (method_exists($submissions, 'links'))
                    <div class="mt-5">{{ $submissions->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection

