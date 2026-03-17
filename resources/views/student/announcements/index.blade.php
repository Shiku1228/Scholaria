@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-2xl font-bold text-slate-900">Announcements</div>
                <div class="text-sm text-slate-500">Updates from your enrolled courses</div>
            </div>
            <a href="{{ route('student.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Courses</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                    <th class="py-3 px-4">Course</th>
                    <th class="py-3 px-4">Title</th>
                    <th class="py-3 px-4">Posted</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse ($announcements as $row)
                    <tr class="align-top">
                        <td class="py-3 px-4 font-medium text-slate-900">{{ (string) data_get($row, 'course_name', 'Course') }}</td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-900">{{ (string) data_get($row, 'title', '') }}</div>
                            @if ((string) data_get($row, 'content', '') !== '')
                                <div class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit((string) data_get($row, 'content', ''), 140) }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            @if ((string) data_get($row, 'created_at', '') !== '')
                                {{ \Carbon\Carbon::parse((string) data_get($row, 'created_at'))->format('Y-m-d h:i A') }}
                            @else
                                --
                            @endif
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
    </div>
@endsection

