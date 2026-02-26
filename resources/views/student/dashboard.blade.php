@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="text-xs text-gray-500">Enrolled Courses</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['enrolled_courses']) }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="text-xs text-gray-500">In Progress</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['in_progress']) }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="text-xs text-gray-500">Completed</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['completed']) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-4 sm:p-5 border-b border-gray-100">
                <div class="text-sm font-semibold">My Courses</div>
                <div class="text-xs text-gray-500">Continue learning</div>
            </div>
            <div class="p-4 sm:p-5">
                @if (empty($myCourses))
                    <div class="text-sm text-gray-500">No data</div>
                @else
                    <ul class="space-y-2 text-sm">
                        @foreach ($myCourses as $row)
                            <li class="flex items-center justify-between">
                                <span class="font-medium text-gray-900">{{ $row['course_name'] }}</span>
                                <span class="text-violet-700 text-xs font-semibold">Continue</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
