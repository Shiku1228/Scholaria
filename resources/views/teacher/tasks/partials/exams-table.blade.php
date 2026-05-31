<table class="min-w-full text-sm">
    <thead class="bg-slate-100">
        <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
            <th class="py-4 px-6">Exam</th>
            <th class="py-4 px-6">Course</th>
            <th class="py-4 px-6">Exam Date</th>
            <th class="py-4 px-6 text-center">Submissions</th>
            <th class="py-4 px-6 text-center">Completion</th>
            <th class="py-4 px-6">Status</th>
            <th class="py-4 px-6">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        @forelse($exams as $exam)
        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
            <td class="py-4 px-6">
                <div class="flex items-start gap-3">
                    <div class="h-8 w-8 rounded-full bg-purple-100 border border-purple-200 text-purple-700 flex items-center justify-center text-xs font-semibold flex-shrink-0 mt-0.5">
                        <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900 leading-tight">{{ $exam->title }}</div>
                        @if($exam->description)
                            <div class="text-xs text-slate-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($exam->description), 100) }}</div>
                        @endif
                        @if($exam->duration)
                            <div class="text-xs text-slate-500 mt-1">
                                <i data-lucide="clock" class="h-3 w-3 inline mr-1"></i>
                                {{ $exam->duration }} minutes
                            </div>
                        @endif
                        @if($exam->total_points)
                            <div class="text-xs text-slate-500 mt-1">{{ $exam->total_points }} points</div>
                        @endif
                    </div>
                </div>
            </td>
            <td class="py-4 px-6">
                <div class="text-sm text-slate-900">{{ $exam->course_number }}</div>
                <div class="text-xs text-slate-500">{{ $exam->course_title }}</div>
            </td>
            <td class="py-4 px-6">
                @if($exam->due_date)
                    <div class="text-sm {{ $exam->is_overdue ? 'text-red-600 font-medium' : 'text-slate-700' }}">
                        {{ $exam->due_date->format('M d, Y') }}
                    </div>
                    <div class="text-xs {{ $exam->is_overdue ? 'text-red-500' : 'text-slate-500' }}">
                        {{ $exam->due_date->format('h:i A') }}
                        @if($exam->is_overdue)
                            <span class="ml-1">(Ended)</span>
                        @else
                            <span class="ml-1">(Starts)</span>
                        @endif
                    </div>
                @else
                    <div class="text-sm text-slate-500">No date set</div>
                @endif
            </td>
            <td class="py-4 px-6 text-center">
                <div class="text-sm font-medium text-slate-900">{{ $exam->completed_count }}/{{ $exam->submission_count }}</div>
                <div class="text-xs text-slate-500">completed</div>
            </td>
            <td class="py-4 px-6 text-center">
                <div class="flex items-center justify-center">
                    <div class="w-12 bg-slate-200 rounded-full h-2">
                        <div class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: {{ $exam->completion_rate }}%"></div>
                    </div>
                </div>
                <div class="text-xs text-slate-500 mt-1">{{ $exam->completion_rate }}%</div>
            </td>
            <td class="py-4 px-6">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $exam->is_overdue ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                    {{ $exam->is_overdue ? 'Ended' : 'Scheduled' }}
                </span>
            </td>
            <td class="py-4 px-6">
                <div class="flex items-center gap-2">
                    <a href="{{ route('teacher.exams.show', $exam) }}" class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="eye" class="h-3 w-3 mr-1"></i>
                        View
                    </a>
                    <a href="{{ route('teacher.exams.edit', $exam) }}" class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                        <i data-lucide="edit" class="h-3 w-3 mr-1"></i>
                        Edit
                    </a>
                    <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" onsubmit="return confirm('Delete this exam?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-red-200 bg-red-50 text-xs font-medium text-red-700 hover:bg-red-100 transition-colors">
                            <i data-lucide="trash-2" class="h-3 w-3 mr-1"></i>
                            Delete
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="py-10 px-6 text-center text-sm text-slate-500">
                @if($courseId > 0)
                    No exams found for the selected course.
                @else
                    No exams found. <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Create your first exam</a>
                @endif
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
