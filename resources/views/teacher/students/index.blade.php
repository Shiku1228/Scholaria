@extends('layouts.teacher')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Students (Roster)</div>
            <div class="text-sm text-gray-500">Students enrolled in your courses</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Student</th>
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Semester</th>
                <th class="py-3 px-4">Enrollment Date</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $row)
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $row->student_name ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $row->course_name ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $row->semester ?? '—' }}</td>
                    <td class="py-3 px-4">{{ $row->enrolled_at ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-10 px-4 text-center text-sm text-gray-500">No students found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
