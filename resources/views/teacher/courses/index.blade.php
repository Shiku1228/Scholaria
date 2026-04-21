@extends('layouts.teacher')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ (string) session('success') }}
        </div>
    @endif

    @if ($errors->has('cover_image'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first('cover_image') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-3xl font-bold text-slate-900">My Courses</div>
            <div class="text-sm text-slate-500">Courses assigned to you</div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse ($courses as $course)
            @php
                $semesterLabel = match (strtolower((string) ($course->semester ?? ''))) {
                    'first' => '1st Semester',
                    'second' => '2nd Semester',
                    'summer' => 'Summer',
                    default => (string) ($course->semester ?? 'Semester'),
                };

                $completion = (int) ($course->completion_rate ?? 0);
                $isDone = $completion >= 100;

                $topBgClass = match ((int) (($course->id ?? 0) % 4)) {
                    0 => 'from-blue-600 to-indigo-600',
                    1 => 'from-indigo-600 to-violet-600',
                    2 => 'from-teal-600 to-emerald-600',
                    default => 'from-orange-500 to-amber-500',
                };

                $courseName = (string) ($course->title ?: $course->course_number ?: ('Course #' . ($course->id ?? '')));
                $code = (string) ($course->course_number ?? '');
            @endphp

            <div class="group rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="h-40 relative p-4 bg-cover bg-center"
                    style="{{ !empty($course->cover_image ?? '') ? 'background-image:url(' . e(asset('storage/' . ltrim((string) $course->cover_image, '/'))) . ');' : '' }}"
                >
                    @if (empty($course->cover_image ?? ''))
                        <div class="absolute inset-0 bg-gradient-to-br {{ $topBgClass }}"></div>
                    @else
                        <div class="absolute inset-0 bg-slate-900/20"></div>
                    @endif
                    <div class="flex items-start justify-between gap-2">
                        <div class="relative z-10 inline-flex items-center rounded-lg bg-white/85 text-slate-700 px-2 py-1 text-xs font-semibold">{{ $semesterLabel }}</div>
                        @if (($course->school_year ?? '') !== '')
                            <div class="relative z-10 inline-flex items-center rounded-lg bg-white/85 text-slate-700 px-2 py-1 text-xs font-semibold">{{ $course->school_year }}</div>
                        @endif
                    </div>
                </div>

                <div class="p-5">
                    <div class="text-2xl font-bold text-slate-900 leading-tight">{{ $courseName }}</div>
                    <div class="mt-1 text-sm text-slate-500">
                        {{ auth()->user()->name }}{{ $code !== '' ? ' • ' . $code : '' }}
                    </div>

                    <div class="mt-5 flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $completion }}% Completed</span>
                        @if ($isDone)
                            <span class="text-emerald-600 font-semibold">Done</span>
                        @else
                            <span class="text-slate-500">{{ (int) ($course->submitted_students ?? 0) }}/{{ (int) ($course->enrolled_students ?? 0) }} students</span>
                        @endif
                    </div>

                    <div class="mt-2 h-2 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full {{ $isDone ? 'bg-emerald-500' : 'bg-indigo-600' }} rounded-full" style="width: {{ $completion }}%"></div>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <a href="{{ isset($course->id) ? route('teacher.courses.show', $course->id) : '#' }}" class="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c]">Open</a>
                        @if (isset($course->id))
                            <form method="POST" action="{{ route('teacher.courses.cover.update', $course->id) }}" enctype="multipart/form-data" class="inline-flex">
                                @csrf
                                <input
                                    id="cover_image_{{ $course->id }}"
                                    name="cover_image"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    class="hidden"
                                    onchange="if(this.files && this.files.length){ this.form.submit(); }"
                                >
                                <label for="cover_image_{{ $course->id }}" class="cursor-pointer inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-300 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    Edit Photo
                                </label>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                No courses found.
            </div>
        @endforelse
    </div>
@endsection
