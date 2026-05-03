<table class="min-w-full text-sm">
    <thead class="bg-slate-100">
        <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
            <th class="py-4 px-6">Assignment</th>
            <th class="py-4 px-6">Course</th>
            <th class="py-4 px-6">Due Date</th>
            <th class="py-4 px-6 text-center">Status</th>
            <th class="py-4 px-6">Score</th>
            <th class="py-4 px-6">Action</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        @forelse($assignments as $assignment)
        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
            <td class="py-4 px-6">
                <div class="flex items-start gap-3">
                    <div class="h-8 w-8 rounded-full bg-blue-100 border border-blue-200 text-blue-700 flex items-center justify-center text-xs font-semibold flex-shrink-0 mt-0.5">
                        <i data-lucide="file-text" class="h-4 w-4"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-slate-900 leading-tight">{{ $assignment->title }}</div>
                        @if($assignment->description)
                            <div class="text-xs text-slate-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($assignment->description), 100) }}</div>
                        @endif
                    </div>
                </div>
            </td>
            <td class="py-4 px-6">
                <div class="text-sm text-slate-900">{{ $assignment->course_number }}</div>
                <div class="text-xs text-slate-500">{{ $assignment->course_title }}</div>
            </td>
            <td class="py-4 px-6">
                @if($assignment->due_date)
                    <div class="text-sm {{ $assignment->is_overdue ? 'text-red-600 font-medium' : 'text-slate-700' }}">
                        {{ $assignment->due_date->format('M d, Y') }}
                    </div>
                    <div class="text-xs {{ $assignment->is_overdue ? 'text-red-500' : 'text-slate-500' }}">
                        {{ $assignment->due_date->format('h:i A') }}
                        @if($assignment->is_overdue)
                            <span class="ml-1">(Overdue)</span>
                        @endif
                    </div>
                @else
                    <div class="text-sm text-slate-500">No due date</div>
                @endif
            </td>
            <td class="py-4 px-6 text-center">
                @if($assignment->status === 'submitted')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                        <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>
                        Submitted
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $assignment->is_overdue ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                        <i data-lucide="clock" class="h-3 w-3 mr-1"></i>
                        {{ $assignment->is_overdue ? 'Overdue' : 'Pending' }}
                    </span>
                @endif
            </td>
            <td class="py-4 px-6">
                @if($assignment->score !== null)
                    <div class="text-sm font-medium text-slate-900">{{ $assignment->score }}/{{ $assignment->total_points ?? 'N/A' }}</div>
                @else
                    <div class="text-sm text-slate-500">Not graded</div>
                @endif
            </td>
            <td class="py-4 px-6">
                <a href="{{ route('student.assignments.submit', $assignment->id) }}" 
                   class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    @if($assignment->status === 'submitted')
                        View
                    @else
                        Submit
                    @endif
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="py-10 px-6 text-center text-sm text-slate-500">
                @if($courseId > 0)
                    No assignments found for the selected course.
                @else
                    No assignments found.
                @endif
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
