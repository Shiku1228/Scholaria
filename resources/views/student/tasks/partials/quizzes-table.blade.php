<table class="min-w-full text-sm">
    <thead class="bg-slate-100">
        <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
            <th class="py-4 px-6">Quiz</th>
            <th class="py-4 px-6">Course</th>
            <th class="py-4 px-6">Due Date</th>
            <th class="py-4 px-6 text-center">Status</th>
            <th class="py-4 px-6">Score</th>
            <th class="py-4 px-6">Action</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        @forelse($quizzes as $quiz)
        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
            <td class="py-4 px-6">
                <div class="flex items-start gap-3">
                    <div class="h-8 w-8 rounded-full bg-orange-100 border border-orange-200 text-orange-700 flex items-center justify-center text-xs font-semibold flex-shrink-0 mt-0.5">
                        <i data-lucide="help-circle" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900 leading-tight">{{ $quiz->title }}</div>
                        @if($quiz->description)
                            <div class="text-xs text-slate-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($quiz->description), 100) }}</div>
                        @endif
                        @if($quiz->duration)
                            <div class="text-xs text-slate-500 mt-1">
                                <i data-lucide="clock" class="h-3 w-3 inline mr-1"></i>
                                {{ $quiz->duration }} minutes
                            </div>
                        @endif
                        @if($quiz->attempts_allowed)
                            <div class="text-xs text-slate-500 mt-1">
                                <i data-lucide="repeat" class="h-3 w-3 inline mr-1"></i>
                                {{ $quiz->attempts_allowed }} attempts
                            </div>
                        @endif
                    </div>
                </div>
            </td>
            <td class="py-4 px-6">
                <div class="text-sm text-slate-900">{{ $quiz->course_number }}</div>
                <div class="text-xs text-slate-500">{{ $quiz->course_title }}</div>
            </td>
            <td class="py-4 px-6">
                @if($quiz->due_date)
                    <div class="text-sm {{ $quiz->is_overdue ? 'text-red-600 font-medium' : 'text-slate-700' }}">
                        {{ $quiz->due_date->format('M d, Y') }}
                    </div>
                    <div class="text-xs {{ $quiz->is_overdue ? 'text-red-500' : 'text-slate-500' }}">
                        {{ $quiz->due_date->format('h:i A') }}
                        @if($quiz->is_overdue)
                            <span class="ml-1">(Closed)</span>
                        @else
                            <span class="ml-1">(Available)</span>
                        @endif
                    </div>
                @else
                    <div class="text-sm text-slate-500">No deadline</div>
                @endif
            </td>
            <td class="py-4 px-6 text-center">
                @if($quiz->status === 'submitted')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>
                        Completed
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $quiz->is_overdue ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700' }}">
                        <i data-lucide="zap" class="h-3 w-3 mr-1"></i>
                        {{ $quiz->is_overdue ? 'Closed' : 'Available' }}
                    </span>
                @endif
            </td>
            <td class="py-4 px-6">
                @if($quiz->score !== null)
                    <div class="text-sm font-medium text-slate-900">{{ $quiz->score }}/{{ $quiz->total_points ?? 'N/A' }}</div>
                @else
                    <div class="text-sm text-slate-500">Not taken</div>
                @endif
            </td>
            <td class="py-4 px-6">
                <a href="{{ route('student.assignments.show', $quiz->id) }}" 
                   class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    @if($quiz->status === 'submitted')
                        View Results
                    @else
                        Start Quiz
                    @endif
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="py-10 px-6 text-center text-sm text-slate-500">
                @if($courseId > 0)
                    No quizzes found for the selected course.
                @else
                    No quizzes found.
                @endif
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
