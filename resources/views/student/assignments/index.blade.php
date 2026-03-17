@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div>
            <div class="text-xl font-semibold">Assignments</div>
            <div class="text-sm text-gray-500">Your upcoming and submitted coursework</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold">Assignment List</div>
                    <div class="text-xs text-gray-500">Assignments from your enrolled courses</div>
                </div>
            </div>

            <div class="p-4 sm:p-5">
                @if (empty($assignments))
                    <div class="text-sm text-gray-500">No assignments yet. When teachers create assignments, they will appear here.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-xs text-gray-500">
                                <th class="py-2 pr-3">Assignment</th>
                                <th class="py-2 pr-3">Course</th>
                                <th class="py-2 pr-3">Due Date</th>
                                <th class="py-2">Action</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach ($assignments as $row)
                                @php
                                    $assignmentId = (int) data_get($row, 'assignment_id', 0);
                                    $submissionId = (int) data_get($row, 'submission_id', 0);
                                @endphp
                                <tr class="text-gray-700">
                                    <td class="py-3 pr-3 font-medium text-gray-900">{{ (string) data_get($row, 'title', '') }}</td>
                                    <td class="py-3 pr-3">{{ (string) data_get($row, 'course_name', '') }}</td>
                                    <td class="py-3 pr-3 text-gray-500">{{ (string) data_get($row, 'due_date', '') }}</td>
                                    <td class="py-3">
                                        @if ($submissionId > 0)
                                            <span class="inline-flex items-center h-8 px-3 rounded-lg bg-green-50 text-green-700 text-xs font-semibold">Submitted</span>
                                        @else
                                            <a href="{{ $assignmentId > 0 ? route('student.assignments.submit', $assignmentId) : '#' }}" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c]">Submit</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

