@extends('layouts.teacher')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Assignments</div>
            <div class="text-sm text-gray-500">All assignments across your courses</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Title</th>
                <th class="py-3 px-4">Due</th>
                <th class="py-3 px-4">Submissions</th>
                <th class="py-3 px-4"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($assignments as $a)
                <tr class="text-gray-700">
                    <td class="py-3 px-4">{{ $a->course?->course_number ?? $a->course?->title ?? ('Course #' . ($a->course_id ?? '')) }}</td>
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $a->title ?? '—' }}</td>
                    <td class="py-3 px-4 text-gray-500">{{ $a->due_date ?? '—' }}</td>
                    <td class="py-3 px-4 text-gray-500">{{ $a->submissions_count ?? 0 }}</td>
                    <td class="py-3 px-4 text-right">
                        @if ($a->course_id)
                            <a href="{{ route('teacher.assignments.show', [$a->course_id, $a->id]) }}" class="text-sm font-semibold text-[#0a3a8a] hover:underline">View</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-10 px-4 text-center text-sm text-gray-500">No assignments found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($assignments, 'links'))
        <div class="mt-6">{{ $assignments->links() }}</div>
    @endif
@endsection
