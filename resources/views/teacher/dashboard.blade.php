@extends('layouts.teacher')

@section('content')
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="text-xs text-gray-500">My Courses</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['my_courses']) }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="text-xs text-gray-500">Students in My Courses</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['students_in_my_courses']) }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-4 border border-gray-100">
                <div class="text-xs text-gray-500">New Enrollments (7 days)</div>
                <div class="text-2xl font-semibold mt-1">{{ number_format($stats['new_enrollments']) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">My Course List</div>
                    <div class="text-xs text-gray-500">Latest courses</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($courseList))
                        <div class="text-sm text-gray-500">No data</div>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($courseList as $c)
                                <li class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">{{ $c['course_name'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="p-4 sm:p-5 border-b border-gray-100">
                    <div class="text-sm font-semibold">Recent Enrollments</div>
                    <div class="text-xs text-gray-500">In my courses</div>
                </div>
                <div class="p-4 sm:p-5">
                    @if (empty($recentEnrollments))
                        <div class="text-sm text-gray-500">No data</div>
                    @else
                        <ul class="space-y-2 text-sm">
                            @foreach ($recentEnrollments as $row)
                                <li class="flex items-center justify-between">
                                    <span class="text-gray-700">Course #{{ $row['course_id'] }}</span>
                                    <span class="text-gray-500">{{ $row['created_at'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
