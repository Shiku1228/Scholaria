@extends('layouts.teacher')

@section('content')
@php
    $fmtTime = function(?string $t): string {
        if (!$t) return '';
        try { return \Carbon\Carbon::parse($t)->format('g:i A'); } catch (\Throwable $e) { return $t; }
    };
@endphp

<div class="space-y-5">

    {{-- Page Header --}}
    <div>
        <div class="text-xl font-semibold">Attendance</div>
        <div class="text-sm text-gray-500">Select a course to load students and mark attendance</div>
    </div>

    {{-- ── Filter Form ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
        <form id="filterForm" method="GET" action="{{ route('teacher.attendance.index') }}"
              class="p-4 sm:p-5 flex flex-wrap gap-3 items-end">

            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Course / Subject</label>
                <select id="course_id" name="course_id"
                    class="h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                    <option value="">— Select course —</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}"
                            {{ (string) $selectedCourseId === (string) $c->id ? 'selected' : '' }}>
                            {{ $c->course_number }}{{ !empty($c->course_code) ? ' — ' . $c->course_code : '' }} – {{ $c->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Date</label>
                <input type="date" id="date_input" name="date" value="{{ $selectedDate }}"
                    class="h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
            </div>

            <div class="w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status Filter</label>
                <select id="status_filter" name="status"
                    class="h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                    <option value="">All Students</option>
                    <option value="present"    {{ $selectedStatus === 'present'    ? 'selected' : '' }}>Present</option>
                    <option value="absent"     {{ $selectedStatus === 'absent'     ? 'selected' : '' }}>Absent</option>
                    <option value="excused"    {{ $selectedStatus === 'excused'    ? 'selected' : '' }}>Excused</option>
                    <option value="not_marked" {{ $selectedStatus === 'not_marked' ? 'selected' : '' }}>Not Marked</option>
                </select>
            </div>

            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search Student</label>
                <div class="flex gap-2">
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Name or Student ID…"
                        class="h-10 flex-1 rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                    <button type="submit"
                        class="h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] whitespace-nowrap">
                        Load
                    </button>
                </div>
            </div>

        </form>
    </div>

    {{-- ── No course selected ────────────────────────────────────────────── --}}
    @if (! $selectedCourseId)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 py-14 text-center">
            <i data-lucide="clipboard-check" class="mx-auto mb-3 text-gray-300" style="width:40px;height:40px;"></i>
            <div class="text-sm font-semibold text-gray-700">No course selected</div>
            <div class="text-xs text-gray-400 mt-1">Select a course above — students will appear automatically.</div>
        </div>

    @elseif (! $selectedCourse)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center">
            <div class="text-sm font-semibold text-red-600">Course not found or you do not have access to it.</div>
        </div>

    @else
        {{-- ── Course Info + Summary Bar ──────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-4">

                {{-- Left: Course name + schedule --}}
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#eaf0fb] flex items-center justify-center flex-shrink-0">
                        <i data-lucide="book-open" style="width:16px;height:16px;color:#0b2d6b;"></i>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900">
                            {{ $selectedCourse->course_number }} — {{ $selectedCourse->title }}
                        </div>
                        <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-400">
                            @if ($selectedCourse->days_pattern || $selectedCourse->start_time)
                                <span>
                                    {{ $selectedCourse->days_pattern }}
                                    @if ($selectedCourse->start_time)
                                        &nbsp;·&nbsp;
                                        {{ $fmtTime($selectedCourse->start_time) }}
                                        @if ($selectedCourse->end_time) – {{ $fmtTime($selectedCourse->end_time) }} @endif
                                    @endif
                                </span>
                            @else
                                <span class="italic">No schedule set</span>
                            @endif
                            @if ($selectedCourse->semester)
                                <span>{{ $selectedCourse->semester }}@if($selectedCourse->school_year) · {{ $selectedCourse->school_year }}@endif</span>
                            @endif
                            <span class="text-gray-300">·</span>
                            <span>{{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Right: Summary counts --}}
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide">Total</div>
                        <div class="font-bold text-gray-900">{{ $summary['total'] }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[11px] text-emerald-500 uppercase tracking-wide">Present</div>
                        <div class="font-bold text-emerald-600">{{ $summary['present'] }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[11px] text-red-400 uppercase tracking-wide">Absent</div>
                        <div class="font-bold text-red-500">{{ $summary['absent'] }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[11px] text-amber-500 uppercase tracking-wide">Excused</div>
                        <div class="font-bold text-amber-600">{{ $summary['excused'] }}</div>
                    </div>
                    <div class="text-center">
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide">Unmarked</div>
                        <div class="font-bold text-gray-400">{{ $summary['notMarked'] }}</div>
                    </div>
                    @if ($summary['total'] > 0)
                    <div class="hidden sm:flex flex-col items-end gap-1 ml-2">
                        <div class="text-[11px] text-[#0b2d6b] font-semibold">{{ $summary['markedPct'] }}% marked</div>
                        <div class="w-32 bg-gray-100 rounded-full h-1.5">
                            <div class="bg-[#0b2d6b] h-1.5 rounded-full"
                                 style="width:{{ $summary['markedPct'] }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- ── Student Table ──────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Table header with bulk actions --}}
            <div class="px-4 sm:px-5 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-gray-900">Students</span>
                    <span class="text-xs text-gray-400">{{ $students->count() }} of {{ $summary['total'] }}</span>
                </div>

                @if ($allStudents->count() > 0)
                @php $bulkLabel = \Carbon\Carbon::parse($selectedDate)->format('M j, Y'); @endphp
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 mr-1">Bulk:</span>

                    <form method="POST" action="{{ route('teacher.attendance.bulk') }}"
                          onsubmit="return confirm('Mark all {{ $allStudents->count() }} students as Present?')">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                        <input type="hidden" name="date"      value="{{ $selectedDate }}">
                        <input type="hidden" name="status"    value="present">
                        <button type="submit"
                            class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
                            <i data-lucide="check-circle" style="width:12px;height:12px;"></i> All Present
                        </button>
                    </form>

                    <form method="POST" action="{{ route('teacher.attendance.bulk') }}"
                          onsubmit="return confirm('Mark all {{ $allStudents->count() }} students as Absent?')">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $selectedCourseId }}">
                        <input type="hidden" name="date"      value="{{ $selectedDate }}">
                        <input type="hidden" name="status"    value="absent">
                        <button type="submit"
                            class="inline-flex items-center gap-1 h-7 px-2.5 rounded-lg border border-red-200 bg-red-50 text-xs font-semibold text-red-700 hover:bg-red-100">
                            <i data-lucide="x-circle" style="width:12px;height:12px;"></i> All Absent
                        </button>
                    </form>
                </div>
                @endif
            </div>

            {{-- ── Desktop Table ─────────────────────────────────────────── --}}
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 border-b border-gray-100 bg-gray-50/50">
                            <th class="py-2.5 px-4 w-8">#</th>
                            <th class="py-2.5 px-4">Student Name</th>
                            <th class="py-2.5 px-4">Student ID</th>
                            <th class="py-2.5 px-4 w-28">Status</th>
                            <th class="py-2.5 px-4 w-40">Remarks</th>
                            <th class="py-2.5 px-4 w-52">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse ($students as $i => $student)
                        @php
                            $att    = $attendanceMap[$student->id] ?? null;
                            $status = $att ? $att->status : null;

                            $btnP = $status === 'present'
                                ? 'bg-emerald-600 text-white border-emerald-600'
                                : 'bg-white text-emerald-700 border-emerald-200 hover:bg-emerald-50';
                            $btnA = $status === 'absent'
                                ? 'bg-red-600 text-white border-red-600'
                                : 'bg-white text-red-700 border-red-200 hover:bg-red-50';
                            $btnE = $status === 'excused'
                                ? 'bg-amber-500 text-white border-amber-500'
                                : 'bg-white text-amber-700 border-amber-200 hover:bg-amber-50';
                        @endphp
                        <tr class="hover:bg-gray-50/40 transition-colors">
                            <td class="py-3 px-4 text-gray-400 text-xs">{{ $i + 1 }}</td>
                            <td class="py-3 px-4 font-medium text-gray-900">{{ $student->name }}</td>
                            <td class="py-3 px-4 text-gray-500 text-xs font-mono">
                                {{ ($student->student_number ?? '') ?: '—' }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($status === 'present')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <i data-lucide="check" style="width:10px;height:10px;"></i> Present
                                    </span>
                                @elseif ($status === 'absent')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <i data-lucide="x" style="width:10px;height:10px;"></i> Absent
                                    </span>
                                @elseif ($status === 'excused')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <i data-lucide="minus-circle" style="width:10px;height:10px;"></i> Excused
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 italic">{{ $att->remarks ?? '' }}</td>
                            <td class="py-3 px-4">
                                <form method="POST" action="{{ route('teacher.attendance.store') }}"
                                      class="flex flex-col gap-1.5">
                                    @csrf
                                    <input type="hidden" name="student_id"    value="{{ $student->id }}">
                                    <input type="hidden" name="course_id"     value="{{ $selectedCourseId }}">
                                    <input type="hidden" name="date"          value="{{ $selectedDate }}">
                                    <input type="hidden" name="filter_status" value="{{ $selectedStatus }}">
                                    <input type="hidden" name="filter_search" value="{{ $search }}">
                                    <input type="text" name="remarks"
                                        value="{{ $att->remarks ?? '' }}"
                                        placeholder="Remarks…"
                                        class="h-7 w-full rounded-lg border-gray-200 text-xs focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                                    <div class="flex gap-1">
                                        <button type="submit" name="status" value="present"
                                            class="flex-1 h-7 rounded-lg border text-xs font-semibold transition-all {{ $btnP }}">
                                            Present
                                        </button>
                                        <button type="submit" name="status" value="absent"
                                            class="flex-1 h-7 rounded-lg border text-xs font-semibold transition-all {{ $btnA }}">
                                            Absent
                                        </button>
                                        <button type="submit" name="status" value="excused"
                                            class="flex-1 h-7 rounded-lg border text-xs font-semibold transition-all {{ $btnE }}">
                                            Excused
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <i data-lucide="users" class="mx-auto mb-2 text-gray-300" style="width:30px;height:30px;"></i>
                                <div class="text-sm text-gray-500">
                                    @if ($search || $selectedStatus)
                                        No students match the current filters.
                                    @else
                                        No active enrolled students found for this course.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── Mobile Cards ───────────────────────────────────────────── --}}
            <div class="sm:hidden divide-y divide-gray-100">
                @forelse ($students as $student)
                    @php
                        $att    = $attendanceMap[$student->id] ?? null;
                        $status = $att ? $att->status : null;
                        $btnP   = $status === 'present'  ? 'bg-emerald-600 text-white border-emerald-600'  : 'bg-white text-emerald-700 border-emerald-200';
                        $btnA   = $status === 'absent'   ? 'bg-red-600 text-white border-red-600'          : 'bg-white text-red-700 border-red-200';
                        $btnE   = $status === 'excused'  ? 'bg-amber-500 text-white border-amber-500'      : 'bg-white text-amber-700 border-amber-200';
                    @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">{{ $student->name }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ ($student->student_number ?? '') ?: 'No ID' }}</div>
                            </div>
                            @if ($status === 'present')
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Present</span>
                            @elseif ($status === 'absent')
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Absent</span>
                            @elseif ($status === 'excused')
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Excused</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-400">Not set</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('teacher.attendance.store') }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="student_id"    value="{{ $student->id }}">
                            <input type="hidden" name="course_id"     value="{{ $selectedCourseId }}">
                            <input type="hidden" name="date"          value="{{ $selectedDate }}">
                            <input type="hidden" name="filter_status" value="{{ $selectedStatus }}">
                            <input type="hidden" name="filter_search" value="{{ $search }}">
                            <input type="text" name="remarks"
                                value="{{ $att->remarks ?? '' }}"
                                placeholder="Remarks (optional)"
                                class="h-9 w-full rounded-lg border-gray-200 text-xs focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" />
                            <div class="flex gap-2">
                                <button type="submit" name="status" value="present"
                                    class="flex-1 h-9 rounded-lg border text-xs font-semibold {{ $btnP }}">Present</button>
                                <button type="submit" name="status" value="absent"
                                    class="flex-1 h-9 rounded-lg border text-xs font-semibold {{ $btnA }}">Absent</button>
                                <button type="submit" name="status" value="excused"
                                    class="flex-1 h-9 rounded-lg border text-xs font-semibold {{ $btnE }}">Excused</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <div class="py-12 text-center text-sm text-gray-500">No students found.</div>
                @endforelse
            </div>

        </div>
    @endif

</div>

{{-- Auto-submit on course/date/status change --}}
<script>
(function () {
    var form     = document.getElementById('filterForm');
    var courseEl = document.getElementById('course_id');
    var dateEl   = document.getElementById('date_input');
    var statusEl = document.getElementById('status_filter');

    function autoSubmit() { form && form.submit(); }

    if (courseEl) courseEl.addEventListener('change', autoSubmit);
    if (dateEl)   dateEl.addEventListener('change',   function () { if (courseEl && courseEl.value) autoSubmit(); });
    if (statusEl) statusEl.addEventListener('change', function () { if (courseEl && courseEl.value) autoSubmit(); });
})();
</script>
@endsection
