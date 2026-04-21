@extends('layouts.teacher')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">{{ $assignment->title }}</div>
            <div class="text-sm text-gray-500">{{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}</div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.assignments.index', $course) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
            <a href="{{ route('teacher.assignments.edit', [$course, $assignment]) }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Edit</a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="text-sm font-semibold text-gray-800">Details</div>
            <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between"><span class="text-gray-600">Due Date</span><span class="font-semibold text-gray-900">{{ $assignment->due_date ?? 'â€”' }}</span></div>
                <div class="flex items-center justify-between"><span class="text-gray-600">Max Score</span><span class="font-semibold text-gray-900">{{ $assignment->max_score ?? 'â€”' }}</span></div>
            </div>
            <div class="mt-4 text-sm text-gray-700">{{ $assignment->description ?? '' }}</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Submissions</div>
                <div class="text-xs text-gray-500">Grade and provide feedback</div>
            </div>
            <div class="p-4 sm:p-5">
                @if ($submissions instanceof \Illuminate\Support\Collection ? $submissions->isEmpty() : $submissions->count() === 0)
                    <div class="text-sm text-gray-500">No submissions yet.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                                <th class="py-3 pr-3">Student</th>
                                <th class="py-3 pr-3">Submitted At</th>
                                <th class="py-3 pr-3">File</th>
                                <th class="py-3 pr-3">Score</th>
                                <th class="py-3">Feedback</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach ($submissions as $s)
                                <tr class="align-top text-gray-700">
                                    <td class="py-3 pr-3 font-medium text-gray-900">{{ $s->student?->name ?? 'â€”' }}</td>
                                    <td class="py-3 pr-3 text-gray-500">{{ $s->submitted_at ?? $s->created_at ?? 'â€”' }}</td>
                                    <td class="py-3 pr-3">
                                        @if ($s->file_path)
                                            <a class="text-sm font-semibold text-[#0a3a8a] hover:underline" href="{{ asset('storage/' . $s->file_path) }}" target="_blank">Download</a>
                                        @else
                                            <span class="text-gray-500">â€”</span>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-3">
                                        <form method="POST" action="{{ route('teacher.submissions.update', [$course, $assignment, $s]) }}" class="flex items-start gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="score" value="{{ old('score', $s->score) }}" class="w-24 rounded-lg border-gray-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                                    </td>
                                    <td class="py-3">
                                            <input name="feedback" value="{{ old('feedback', $s->feedback) }}" class="w-72 max-w-full rounded-lg border-gray-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                                            <button type="submit" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c]">Save</button>
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
    </div>
@endsection

