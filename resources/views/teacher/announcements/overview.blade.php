@extends('layouts.teacher')

@section('content')
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Announcements</div>
            <div class="text-sm text-gray-500">All announcements across your courses</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Title</th>
                <th class="py-3 px-4">Posted</th>
                <th class="py-3 px-4"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($announcements as $n)
                <tr class="text-gray-700">
                    <td class="py-3 px-4">{{ $n->course?->course_number ?? $n->course?->title ?? ('Course #' . ($n->course_id ?? '')) }}</td>
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $n->title ?? '—' }}</td>
                    <td class="py-3 px-4 text-gray-500">{{ $n->created_at ?? '—' }}</td>
                    <td class="py-3 px-4 text-right">
                        @if ($n->course_id)
                            <a href="{{ route('teacher.announcements.show', [$n->course_id, $n->id]) }}" class="text-sm font-semibold text-[#0a3a8a] hover:underline">View</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-10 px-4 text-center text-sm text-gray-500">No announcements found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($announcements, 'links'))
        <div class="mt-6">{{ $announcements->links() }}</div>
    @endif
@endsection
