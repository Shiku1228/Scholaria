@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div>
            <div class="text-xl font-semibold">Grades</div>
            <div class="text-sm text-gray-500">Scores and feedback for your assignments</div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">Gradebook</div>
                <div class="text-xs text-gray-500">Per assignment results</div>
            </div>

            <div class="p-4 sm:p-5">
                @if (empty($rows))
                    <div class="text-sm text-gray-500">No grades available yet. Once teachers grade your submissions, you will see them here.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                            <tr class="text-left text-xs text-gray-500">
                                <th class="py-2 pr-3">Course</th>
                                <th class="py-2 pr-3">Assignment</th>
                                <th class="py-2 pr-3">Score</th>
                                <th class="py-2">Feedback</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                            @foreach ($rows as $row)
                                <tr class="text-gray-700">
                                    <td class="py-3 pr-3 font-medium text-gray-900">{{ (string) data_get($row, 'course_name', '') }}</td>
                                    <td class="py-3 pr-3">{{ (string) data_get($row, 'assignment_title', '') }}</td>
                                    <td class="py-3 pr-3 text-gray-900 font-semibold">
                                        @php $score = data_get($row, 'score'); @endphp
                                        {{ $score === null ? '—' : (string) $score }}
                                    </td>
                                    <td class="py-3 text-gray-500">{{ (string) data_get($row, 'feedback', '') }}</td>
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
