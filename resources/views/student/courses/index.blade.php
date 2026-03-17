@extends('layouts.student')

@section('content')
    <div>
        <div class="text-3xl font-bold text-slate-900">My Courses</div>
        <div class="text-sm text-slate-500">Courses you are currently enrolled in</div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($courses as $course)
            @php
                $semesterLabel = match (strtolower((string) data_get($course, 'semester', ''))) {
                    'first' => '1st Semester',
                    'second' => '2nd Semester',
                    'summer' => 'Summer',
                    default => (string) (data_get($course, 'semester', '') ?: 'Semester'),
                };

                $courseId = (int) data_get($course, 'course_id', 0);
                $completion = (int) data_get($course, 'progress', 0);
                $isDone = $completion >= 100;

                $topBgClass = match ($courseId % 4) {
                    0 => 'from-blue-600 to-indigo-600',
                    1 => 'from-indigo-600 to-violet-600',
                    2 => 'from-teal-600 to-emerald-600',
                    default => 'from-orange-500 to-amber-500',
                };
            @endphp

            <div class="group rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="h-40 relative p-4 bg-cover bg-center"
                    style="{{ !empty((string) data_get($course, 'cover_image', '')) ? 'background-image:url(' . e(asset('storage/' . ltrim((string) data_get($course, 'cover_image', ''), '/'))) . ');' : '' }}"
                >
                    @if (empty((string) data_get($course, 'cover_image', '')))
                        <div class="absolute inset-0 bg-gradient-to-br {{ $topBgClass }}"></div>
                    @else
                        <div class="absolute inset-0 bg-slate-900/20"></div>
                    @endif

                    <div class="flex items-start justify-between gap-2">
                        <div class="relative z-10 inline-flex items-center rounded-lg bg-white/85 text-slate-700 px-2 py-1 text-xs font-semibold">{{ $semesterLabel }}</div>
                        @if ((string) data_get($course, 'school_year', '') !== '')
                            <div class="relative z-10 inline-flex items-center rounded-lg bg-white/85 text-slate-700 px-2 py-1 text-xs font-semibold">{{ (string) data_get($course, 'school_year', '') }}</div>
                        @endif
                    </div>
                </div>

                <div class="p-5">
                    <div class="text-2xl font-bold text-slate-900 leading-tight">{{ (string) data_get($course, 'course_name', '') }}</div>
                    <div class="mt-1 text-sm text-slate-500">
                        {{ (string) data_get($course, 'teacher_name', 'Teacher') }}{{ (string) data_get($course, 'course_number', '') !== '' ? ' • ' . (string) data_get($course, 'course_number', '') : '' }}
                    </div>

                    <div class="mt-5 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $completion }}% Completed</span>
                        @if ($isDone)
                            <span class="text-emerald-600 font-semibold">Done</span>
                        @else
                            <span class="text-slate-500">{{ number_format((int) data_get($course, 'assignments_submitted', 0)) }}/{{ number_format((int) data_get($course, 'assignments_total', 0)) }} assignments</span>
                        @endif
                    </div>

                    <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full {{ $isDone ? 'bg-emerald-500' : 'bg-indigo-600' }} rounded-full" style="width: {{ $completion }}%"></div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="{{ route('student.courses.show', $courseId) }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c]">
                            Open Course
                        </a>
                        <a href="{{ route('student.assignments.index', ['course_id' => $courseId]) }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            View Assignments
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                You are not enrolled in any active course yet.
            </div>
        @endforelse
    </div>
@endsection

