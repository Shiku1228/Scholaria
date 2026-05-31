@extends('layouts.student')

@section('content')
@php
    $formatTime = function(?string $t): string {
        if (! $t) return '';
        try { return \Carbon\Carbon::parse($t)->format('g:i A'); } catch (\Throwable $e) { return $t; }
    };
    $hasFilters    = $filterCourse || $search;
    $selectedCourse = $filterCourse ? $courses->firstWhere('id', $filterCourse) : null;

    // Rate color thresholds
    $pct = $summary['percentage'];
    if ($pct >= 75) {
        $rateText = 'text-emerald-600';
        $rateBorder = 'border-emerald-200 bg-emerald-50';
    } elseif ($pct >= 50) {
        $rateText = 'text-amber-600';
        $rateBorder = 'border-amber-200 bg-amber-50';
    } else {
        $rateText = 'text-red-600';
        $rateBorder = 'border-red-200 bg-red-50';
    }
@endphp

<div class="space-y-5">

    {{-- Page Header --}}
    <div>
        <div class="text-xl font-semibold">Attendance</div>
        <div class="text-sm text-gray-500">Your attendance records across all enrolled courses</div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
            <div class="text-[11px] text-gray-500 font-medium uppercase tracking-wide">Total</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total'] }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">
                {{ $selectedCourse ? 'This subject' : 'All subjects' }}
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-4">
            <div class="text-[11px] text-emerald-600 font-medium uppercase tracking-wide">Present</div>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $summary['present'] }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">Days attended</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-4">
            <div class="text-[11px] text-red-500 font-medium uppercase tracking-wide">Absent</div>
            <div class="text-2xl font-bold text-red-500 mt-1">{{ $summary['absent'] }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">Days missed</div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-4">
            <div class="text-[11px] text-amber-600 font-medium uppercase tracking-wide">Excused</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $summary['excused'] }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">Days excused</div>
        </div>
        <div class="col-span-2 sm:col-span-1 bg-white rounded-2xl shadow-sm border {{ $summary['total'] > 0 ? $rateBorder : 'border-[#c9d7f2]' }} p-4">
            <div class="text-[11px] {{ $summary['total'] > 0 ? $rateText : 'text-[#0b2d6b]' }} font-medium uppercase tracking-wide">Rate</div>
            <div class="text-2xl font-bold {{ $summary['total'] > 0 ? $rateText : 'text-[#0b2d6b]' }} mt-1">{{ $summary['percentage'] }}%</div>
            <div class="text-[10px] text-gray-400 mt-0.5">(Present + Excused) ÷ Total</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-4 sm:p-5 border-b border-gray-100">
            <div class="text-sm font-semibold">Filter Records</div>
        </div>
        <form method="GET" action="{{ route('student.attendance.index') }}"
              class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">

            <div class="sm:col-span-2 xl:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Subject / Course</label>
                <select name="course_id"
                    class="h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                    onchange="this.form.submit()">
                    <option value="">All Courses</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}"
                            {{ (string) $filterCourse === (string) $course->id ? 'selected' : '' }}>
                            {{ !empty($course->course_code) ? $course->course_code . ' — ' : '' }}{{ $course->title }} — CN: {{ $course->course_number }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2 xl:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Subject, code, teacher…"
                        class="h-10 flex-1 min-w-0 rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                    <button type="submit"
                        class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                        Filter
                    </button>
                </div>
            </div>

            @if ($hasFilters)
                <div class="sm:col-span-2 lg:col-span-3 xl:col-span-6 flex justify-end">
                    <a href="{{ route('student.attendance.index') }}"
                        class="inline-flex items-center gap-1.5 h-9 px-4 rounded-xl border border-gray-200 bg-white text-xs font-semibold text-gray-600 hover:bg-gray-50">
                        <i data-lucide="x" style="width:13px;height:13px;"></i>
                        Clear filters
                    </a>
                </div>
            @endif
        </form>
    </div>

    {{-- Per-subject banner (only when a course is selected) --}}
    @if ($selectedCourse)
    <div class="bg-white rounded-2xl shadow-sm border border-[#c9d7f2] overflow-hidden">
        <div class="bg-[#0b2d6b]/5 border-b border-[#c9d7f2] px-5 py-3 flex items-center gap-2">
            <i data-lucide="book-marked" style="width:14px;height:14px;" class="text-[#0b2d6b] flex-shrink-0"></i>
            <span class="text-sm font-semibold text-[#0b2d6b] truncate">
                {{ !empty($selectedCourse->course_code) ? $selectedCourse->course_code . ' — ' : '' }}{{ $selectedCourse->title }} — CN: {{ $selectedCourse->course_number }}
            </span>
        </div>
        <div class="p-5 flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap gap-5 text-sm">
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 flex-shrink-0"></span>
                    <span class="text-gray-500">Present</span>
                    <span class="font-bold text-gray-900">{{ $summary['present'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></span>
                    <span class="text-gray-500">Absent</span>
                    <span class="font-bold text-gray-900">{{ $summary['absent'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></span>
                    <span class="text-gray-500">Excused</span>
                    <span class="font-bold text-gray-900">{{ $summary['excused'] }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="text-gray-400">Total</span>
                    <span class="font-bold text-gray-900">{{ $summary['total'] }}</span>
                    <span class="text-gray-400 text-xs">classes</span>
                </div>
            </div>
            <div class="px-5 py-2.5 rounded-xl border {{ $summary['total'] > 0 ? $rateBorder : 'border-gray-200 bg-gray-50' }} text-center min-w-[90px]">
                <div class="text-2xl font-bold {{ $summary['total'] > 0 ? $rateText : 'text-gray-400' }}">{{ $summary['percentage'] }}%</div>
                <div class="text-[10px] text-gray-400 mt-0.5">Attendance Rate</div>
            </div>
        </div>
        @if ($summary['total'] > 0)
        <div class="px-5 pb-4">
            <div class="flex items-center gap-3">
                <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden flex">
                    <div class="bg-emerald-500 h-full transition-all" style="width: {{ ($summary['present'] / $summary['total']) * 100 }}%"></div>
                    <div class="bg-amber-400 h-full transition-all" style="width: {{ ($summary['excused'] / $summary['total']) * 100 }}%"></div>
                    <div class="bg-red-400 h-full transition-all" style="width: {{ ($summary['absent'] / $summary['total']) * 100 }}%"></div>
                </div>
                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $summary['total'] }} class{{ $summary['total'] !== 1 ? 'es' : '' }}</span>
            </div>
            <div class="flex gap-4 mt-1.5 text-[10px] text-gray-400">
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> Present</span>
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span> Excused</span>
                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span> Absent</span>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Records --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold">Attendance Records</div>
                <div class="text-xs text-gray-500">
                    @if ($rows->isNotEmpty())
                        {{ $rows->count() }} record{{ $rows->count() !== 1 ? 's' : '' }} found
                        @if ($selectedCourse)
                            for {{ $selectedCourse->course_number }}{{ !empty($selectedCourse->course_code) ? ' — ' . $selectedCourse->course_code : '' }}
                        @endif
                    @endif
                </div>
            </div>
        </div>

        @if ($courses->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="book-open" class="mx-auto mb-3 text-gray-300" style="width:36px;height:36px;"></i>
                <div class="text-sm font-medium text-gray-700">Not enrolled in any courses</div>
                <div class="text-xs text-gray-400 mt-1">You are not enrolled in any courses yet.</div>
            </div>
        @elseif ($rows->isEmpty())
            <div class="p-12 text-center">
                <i data-lucide="clipboard-check" class="mx-auto mb-3 text-gray-300" style="width:36px;height:36px;"></i>
                <div class="text-sm font-medium text-gray-700">No attendance records found</div>
                <div class="text-xs text-gray-400 mt-1">
                    @if ($filterCourse && !$search)
                        No attendance records yet for this subject. Your teacher has not marked attendance yet.
                    @elseif ($hasFilters)
                        No records match your filters. Try adjusting or clearing them.
                    @else
                        Your teachers have not marked attendance yet.
                    @endif
                </div>
            </div>
        @else
            {{-- Desktop table --}}
            <div class="overflow-x-auto hidden sm:block">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-100 bg-gray-50/60">
                            <th class="py-3 px-4 font-medium">Subject</th>
                            <th class="py-3 px-4 font-medium">Teacher</th>
                            <th class="py-3 px-4 font-medium">Schedule</th>
                            <th class="py-3 px-4 font-medium whitespace-nowrap">Date</th>
                            <th class="py-3 px-4 font-medium">Status</th>
                            <th class="py-3 px-4 font-medium">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @foreach ($rows as $row)
                        <tr class="text-gray-700 hover:bg-gray-50/50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-900">{{ $row->course_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    {{ !empty($row->course_code) ? $row->course_code . ' — ' : '' }}CN: {{ $row->course_number }}
                                </div>
                            </td>
                            <td class="py-3 px-4 text-gray-600">{{ $row->teacher_name }}</td>
                            <td class="py-3 px-4 text-xs text-gray-500">
                                @php
                                    $schedParts = [];
                                    if (!empty($row->days_pattern)) $schedParts[] = $row->days_pattern;
                                    if (!empty($row->start_time)) {
                                        $timeStr = $formatTime($row->start_time);
                                        if (!empty($row->end_time)) $timeStr .= ' – ' . $formatTime($row->end_time);
                                        $schedParts[] = $timeStr;
                                    }
                                @endphp
                                {{ $schedParts ? implode(' · ', $schedParts) : '—' }}
                            </td>
                            <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($row->date)->format('M j, Y') }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($row->status === 'present')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <i data-lucide="check" style="width:10px;height:10px;"></i> Present
                                    </span>
                                @elseif ($row->status === 'absent')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <i data-lucide="x" style="width:10px;height:10px;"></i> Absent
                                    </span>
                                @elseif ($row->status === 'excused')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <i data-lucide="minus-circle" style="width:10px;height:10px;"></i> Excused
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-gray-500 text-xs">{{ $row->remarks ?: '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="sm:hidden divide-y divide-gray-100">
                @foreach ($rows as $row)
                    @php
                        $schedParts = [];
                        if (!empty($row->days_pattern)) $schedParts[] = $row->days_pattern;
                        if (!empty($row->start_time)) {
                            $timeStr = $formatTime($row->start_time);
                            if (!empty($row->end_time)) $timeStr .= ' – ' . $formatTime($row->end_time);
                            $schedParts[] = $timeStr;
                        }
                    @endphp
                    <div class="p-4 space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900">{{ $row->course_name }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ !empty($row->course_code) ? $row->course_code . ' — ' : '' }}CN: {{ $row->course_number }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $row->teacher_name }}</div>
                                @if ($schedParts)
                                    <div class="text-xs text-gray-400">{{ implode(' · ', $schedParts) }}</div>
                                @endif
                            </div>
                            <div class="flex-shrink-0">
                                @if ($row->status === 'present')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Present</span>
                                @elseif ($row->status === 'absent')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Absent</span>
                                @elseif ($row->status === 'excused')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Excused</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span>{{ \Carbon\Carbon::parse($row->date)->format('M j, Y') }}</span>
                            @if ($row->remarks)
                                <span class="italic text-gray-400">{{ $row->remarks }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
