@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div>
            <div class="text-xl font-semibold">My Courses</div>
            <div class="text-sm text-gray-500">Courses you are currently enrolled in</div>
        </div>

        @if (empty($courses))
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="text-sm text-gray-500">You are not enrolled in any course yet.</div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach ($courses as $course)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex flex-col">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 truncate">{{ (string) data_get($course, 'course_name', '') }}</div>
                                <div class="text-xs text-gray-500 mt-1">Teacher: {{ (string) data_get($course, 'teacher_name', 'N/A') }}</div>
                            </div>
                            <div class="h-10 w-10 rounded-2xl bg-violet-50 flex items-center justify-center">
                                <i data-lucide="book-open" style="width:18px;height:18px;color:rgb(109 40 217);"></i>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Progress</span>
                                <span class="font-semibold text-gray-700">{{ (int) data_get($course, 'progress', 0) }}%</span>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full bg-violet-600" style="width: {{ (int) data_get($course, 'progress', 0) }}%"></div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500">
                                {{ number_format((int) data_get($course, 'assignments_submitted', 0)) }} / {{ number_format((int) data_get($course, 'assignments_total', 0)) }} assignments submitted
                            </div>
                        </div>

                        <div class="mt-5">
                            <a href="{{ route('student.assignments.index', ['course_id' => (int) data_get($course, 'course_id', 0)]) }}" class="inline-flex items-center justify-center h-11 w-full rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700">Continue Course</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
