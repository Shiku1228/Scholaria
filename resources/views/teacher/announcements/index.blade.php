@extends('layouts.teacher')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Announcements</div>
            <div class="text-sm text-gray-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.courses.show', $course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
            <a href="{{ route('teacher.announcements.create', $course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Create</a>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Title</th>
                <th class="py-3 px-4">Posted</th>
                <th class="py-3 px-4"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($announcements as $n)
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $n->title ?? 'â€”' }}</td>
                    <td class="py-3 px-4 text-gray-500">{{ $n->created_at ?? 'â€”' }}</td>
                    <td class="py-3 px-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('teacher.announcements.show', [$course, $n]) }}" class="text-sm font-semibold text-[#0a3a8a] hover:underline">View</a>
                            <a href="{{ route('teacher.announcements.edit', [$course, $n]) }}" class="text-sm font-semibold text-gray-700 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('teacher.announcements.destroy', [$course, $n]) }}" onsubmit="return confirm('Delete this announcement?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="py-10 px-4 text-center text-sm text-gray-500">No announcements found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($announcements, 'links'))
        <div class="mt-6">{{ $announcements->links() }}</div>
    @endif
@endsection

