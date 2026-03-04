@extends('layouts.teacher')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 h-auto md:h-[calc(100vh-6rem)]">
        <div class="col-span-1 md:col-span-4 h-auto md:h-full">
            <div class="h-auto md:h-full rounded-[28px] p-6 shadow-sm border border-gray-100 flex flex-col" style="background: linear-gradient(160deg, var(--slms-panel-grad-a, #0b2d6b) 0%, var(--slms-panel-grad-b, #0a3a8a) 45%, var(--slms-panel-grad-c, #4f46e5) 100%);">
                <div class="text-white text-3xl font-semibold mt-1">WELCOME {{ strtoupper(auth()->user()->name) }} TO YOUR DASHBOARD</div>
                <div class="mt-6 flex flex-col gap-4 flex-1 justify-center">
                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="book-open" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['my_courses']) }}</div>
                        <div class="text-white/70 text-xs mt-1">My Courses</div>
                    </div>

                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="users" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['students_in_my_courses']) }}</div>
                        <div class="text-white/70 text-xs mt-1">Students in My Courses</div>
                    </div>

                    <div class="rounded-[22px] border border-white/10 bg-white/5 p-4">
                        <div class="h-11 w-11 rounded-2xl bg-white/10 flex items-center justify-center">
                            <i data-lucide="clipboard-check" style="width:20px;height:20px;display:block;line-height:0;color:rgba(255,255,255,0.92);"></i>
                        </div>
                        <div class="text-white text-2xl font-semibold mt-4">{{ number_format($stats['new_enrollments']) }}</div>
                        <div class="text-white/70 text-xs mt-1">New Enrollments (7 days)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-1 md:col-span-8 overflow-visible md:overflow-y-auto">
            <div class="space-y-6">
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
        </div>
    </div>
@endsection
