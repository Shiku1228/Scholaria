@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Assignments</h1>
            <p class="mt-2 text-slate-600">Manage your coursework and track submission status</p>
        </div>

        {{-- Empty State --}}
        @if (empty($assignments))
            <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-12 text-center">
                <div class="h-16 w-16 rounded-full bg-slate-200 flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="inbox" class="h-8 w-8 text-slate-500"></i>
                </div>
                <h3 class="text-base font-semibold text-slate-900 mb-1">No assignments yet</h3>
                <p class="text-sm text-slate-600">When teachers create assignments for your courses, they will appear here.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach ($assignments as $row)
                    @php
                        $assignmentId = (int) data_get($row, 'assignment_id', 0);
                        $submissionId = (int) data_get($row, 'submission_id', 0);
                        $dueDate = data_get($row, 'due_date', '');
                        $title = (string) data_get($row, 'title', 'Assignment');
                        $courseName = (string) data_get($row, 'course_name', 'Course');
                        
                        // Parse due date
                        $dueDateObj = null;
                        $isOverdue = false;
                        $isUrgent = false;
                        $daysLeft = 0;
                        
                        if (!empty($dueDate)) {
                            try {
                                $dueDateObj = \Carbon\Carbon::parse($dueDate);
                                $isOverdue = $submissionId === 0 && $dueDateObj < now();
                                $daysLeft = $dueDateObj->diffInDays(now(), false);
                                $isUrgent = $daysLeft > 0 && $daysLeft <= 3 && $submissionId === 0;
                            } catch (\Exception $e) {
                                // Invalid date
                            }
                        }
                    @endphp
                    
                    <div class="group relative bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer" onclick="window.location.href='{{ route('student.assignments.show', $assignmentId) }}';">
                        {{-- Status Badge --}}
                        @if($submissionId > 0)
                            <div class="absolute top-3 right-3 inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-700">
                                <i data-lucide="check-circle" class="h-4 w-4"></i>
                                <span class="text-xs font-semibold">Submitted</span>
                            </div>
                        @elseif($isOverdue)
                            <div class="absolute top-3 right-3 inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-red-100 text-red-700">
                                <i data-lucide="alert-circle" class="h-4 w-4"></i>
                                <span class="text-xs font-semibold">Overdue</span>
                            </div>
                        @elseif($isUrgent)
                            <div class="absolute top-3 right-3 inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-amber-100 text-amber-700">
                                <i data-lucide="clock" class="h-4 w-4"></i>
                                <span class="text-xs font-semibold">Due Soon</span>
                            </div>
                        @endif

                        <div class="p-5">
                            {{-- Title and Course --}}
                            <div class="pr-24 mb-3">
                                <h3 class="text-lg font-semibold text-slate-900 line-clamp-2 group-hover:text-[#0b2d6b] transition-colors">{{ $title }}</h3>
                                <p class="text-sm text-slate-500 mt-1">{{ $courseName }}</p>
                            </div>

                            {{-- Due Date Info --}}
                            @if($dueDateObj)
                                <div class="flex items-center gap-2 mb-4 text-sm {{ $isOverdue ? 'text-red-600 font-semibold' : ($isUrgent ? 'text-amber-600 font-semibold' : 'text-slate-600') }}">
                                    <i data-lucide="calendar" class="h-4 w-4"></i>
                                    @if($isOverdue)
                                        <span>Overdue since {{ $dueDateObj->format('M d') }}</span>
                                    @elseif($isUrgent)
                                        <span>Due in {{ $daysLeft }} day{{ $daysLeft !== 1 ? 's' : '' }} ({{ $dueDateObj->format('M d') }})</span>
                                    @else
                                        <span>Due {{ $dueDateObj->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            @endif

                            {{-- Action Button --}}
                            <div class="pt-4 border-t border-slate-100">
                                <div class="inline-flex items-center gap-1 text-sm font-semibold text-[#0b2d6b] group-hover:gap-2 transition-all">
                                    @if ($submissionId > 0)
                                        <i data-lucide="check" class="h-4 w-4"></i>
                                        <span>View Submission</span>
                                    @else
                                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                                        <span>View Assignment</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

