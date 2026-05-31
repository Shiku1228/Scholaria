@php
    $showCourse       = $showCourseCol ?? true;
    $rowList          = ($rows instanceof \Illuminate\Contracts\Pagination\Paginator) ? $rows->items() : (is_iterable($rows) ? $rows : []);
    $colspan          = $showCourse ? 6 : 5;
    $hasStudentNumber = $hasStudentNumber ?? \Illuminate\Support\Facades\Schema::hasColumn('users', 'student_number');
@endphp
<div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <th class="py-3 px-4">Student</th>
                @if ($showCourse)
                    <th class="py-3 px-4">Course</th>
                @endif
                <th class="py-3 px-4">Teacher</th>
                <th class="py-3 px-4">Semester</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Enrolled</th>
                <th class="py-3 px-4 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rowList as $enrollment)
                @php
                    $studentName = (string) ($enrollment->student?->name ?? '—');
                    $initial     = strtoupper(substr($studentName, 0, 1)) ?: 'S';

                    $semesterLabel = match ($enrollment->course?->semester) {
                        'first'  => '1st',
                        'second' => '2nd',
                        'summer' => 'Summer',
                        default  => $enrollment->course?->semester ?? '—',
                    };

                    $statusLabel = match ($enrollment->status) {
                        'active'     => 'Active',
                        'completed'  => 'Completed',
                        'dropped'    => 'Dropped',
                        'unenrolled' => 'Unenrolled',
                        default      => ucfirst((string) $enrollment->status),
                    };

                    $statusClass = match ($enrollment->status) {
                        'active'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'completed'  => 'bg-blue-50 text-blue-700 border-blue-200',
                        'dropped'    => 'bg-amber-50 text-amber-700 border-amber-200',
                        'unenrolled' => 'bg-red-50 text-red-700 border-red-200',
                        default      => 'bg-slate-100 text-slate-600 border-slate-200',
                    };

                    $courseCode  = !empty($enrollment->course?->course_code) ? $enrollment->course->course_code . ' — ' : '';
                    $courseTitle = $enrollment->course?->title ?? '—';
                    $courseNum   = $enrollment->course?->course_number ?? '';
                @endphp
                <tr class="text-slate-700 hover:bg-slate-50/60 transition-colors">
                    <td class="py-3.5 px-4">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 flex-shrink-0 rounded-full bg-[#eaf0fb] border border-[#c9d7f2] text-[#0b2d6b] flex items-center justify-center text-xs font-semibold">
                                {{ $initial }}
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 leading-tight">{{ $studentName }}</div>
                                @if ($hasStudentNumber)
                                    @php
                                        $sno = null;
                                        // Prefer Student profile student_number (plain text)
                                        try { $sno = $enrollment->student?->student?->student_number ?: null; } catch (\Throwable) {}
                                        // Fallback: User::student_number (encrypted, accessor decrypts)
                                        if (!$sno) { try { $sno = $enrollment->student?->student_number ?: null; } catch (\Throwable) {} }
                                    @endphp
                                    <div class="text-xs text-slate-400 mt-0.5">Student No: {{ $sno ?: '—' }}</div>
                                @endif
                                @if (!empty($enrollment->student?->email))
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $enrollment->student->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    @if ($showCourse)
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-slate-900 leading-tight">{{ $courseCode }}{{ $courseTitle }}</div>
                            @if ($courseNum !== '')
                                <div class="text-xs text-slate-400 mt-0.5">CN: {{ $courseNum }}</div>
                            @endif
                        </td>
                    @endif
                    <td class="py-3.5 px-4 text-slate-700">{{ $enrollment->teacher?->name ?? '—' }}</td>
                    <td class="py-3.5 px-4 text-slate-700">{{ $semesterLabel }}</td>
                    <td class="py-3.5 px-4">
                        <span class="inline-flex items-center h-6 px-2.5 rounded-full border text-xs font-medium {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td class="py-3.5 px-4 text-slate-500 text-xs">
                        {{ optional($enrollment->enrolled_at)->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="py-3.5 px-4 text-center">
                        <a href="{{ route('admin.enrollments.edit', $enrollment) }}"
                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 bg-white text-slate-400 hover:text-[#0b2d6b] hover:border-[#0b2d6b] hover:bg-[#eaf0fb] transition-colors"
                            title="Edit enrollment">
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colspan + 1 }}" class="py-12 px-4 text-center">
                        <i data-lucide="users" class="mx-auto mb-2 text-slate-300" style="width:32px;height:32px;"></i>
                        <div class="text-sm text-slate-500">No students enrolled in this course yet.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
