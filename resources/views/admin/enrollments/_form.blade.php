@php
    $editing = isset($enrollment) && $enrollment;

    $selectedCourseId  = (string) old('course_id',  $editing ? $enrollment->course_id  : '');
    $selectedStudentId = (string) old('student_id', $editing ? $enrollment->student_id : '');
    $selectedStatus    = (string) old('status',     $editing ? $enrollment->status     : 'active');

    $enrolledAtValue = old('enrolled_at');
    if ($enrolledAtValue === null || $enrolledAtValue === '') {
        $enrolledAtValue = $editing && $enrollment->enrolled_at
            ? $enrollment->enrolled_at->format('Y-m-d')
            : now()->format('Y-m-d');
    }

    // Build course data map for JavaScript auto-fill
    $courseDataMap = [];
    foreach ($courses as $c) {
        $rawStart = $c->start_time ?? '';
        $rawEnd   = $c->end_time   ?? '';

        $courseDataMap[$c->id] = [
            'teacher_name' => $c->teacher?->name ?? '',
            'teacher_id'   => $c->teacher_id ?? '',
            'semester'     => $c->semester    ?? '',
            'school_year'  => $c->school_year ?? '',
            'days_pattern' => $c->days_pattern ?? '',
            'start_time'   => $rawStart,
            'end_time'     => $rawEnd,
        ];
    }
@endphp

{{-- Student --}}
<div>
    <label class="block text-sm font-medium text-gray-700" for="student_id">Student</label>
    <select id="student_id" name="student_id"
        class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
        required>
        <option value="">Select student</option>
        @foreach ($students as $student)
            <option value="{{ $student->id }}"
                {{ $selectedStudentId === (string) $student->id ? 'selected' : '' }}>
                {{ $student->name }}{{ !empty($student->email) ? ' — ' . $student->email : '' }}
            </option>
        @endforeach
    </select>
    @error('student_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
</div>

{{-- Course --}}
<div>
    <label class="block text-sm font-medium text-gray-700" for="course_id">Course</label>
    <select id="course_id" name="course_id"
        class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
        required>
        <option value="">Select course</option>
        @foreach ($courses as $course)
            @php $hasTeacher = (bool) $course->teacher_id; @endphp
            <option value="{{ $course->id }}"
                {{ $selectedCourseId === (string) $course->id ? 'selected' : '' }}
                {{ $hasTeacher ? '' : 'disabled' }}>
                {{ !empty($course->course_code) ? $course->course_code . ' — ' : '' }}{{ $course->title }} — CN: {{ $course->course_number }}{{ $hasTeacher ? '' : ' (no teacher assigned)' }}
            </option>
        @endforeach
    </select>
    @error('course_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
</div>

{{-- Auto-filled Course Info (read-only) --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <div class="block text-sm font-medium text-gray-700">Assigned Teacher</div>
        <div id="teacherDisplay"
            class="mt-2 h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 flex items-center text-sm text-gray-700">
            &mdash;
        </div>
    </div>
    <div>
        <div class="block text-sm font-medium text-gray-700">Semester</div>
        <div id="semesterDisplay"
            class="mt-2 h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 flex items-center text-sm text-gray-700">
            &mdash;
        </div>
    </div>
    <div>
        <div class="block text-sm font-medium text-gray-700">School Year</div>
        <div id="schoolYearDisplay"
            class="mt-2 h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 flex items-center text-sm text-gray-700">
            &mdash;
        </div>
    </div>
    <div>
        <div class="block text-sm font-medium text-gray-700">Schedule</div>
        <div id="scheduleDisplay"
            class="mt-2 h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 flex items-center text-sm text-gray-700">
            &mdash;
        </div>
    </div>
</div>

{{-- Status (only on edit) + Enrollment Date --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    @if ($editing)
    <div>
        <label class="block text-sm font-medium text-gray-700" for="status">Status</label>
        <select id="status" name="status"
            class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
            required>
            <option value="active"     {{ $selectedStatus === 'active'     ? 'selected' : '' }}>Active</option>
            <option value="completed"  {{ $selectedStatus === 'completed'  ? 'selected' : '' }}>Completed</option>
            <option value="dropped"    {{ $selectedStatus === 'dropped'    ? 'selected' : '' }}>Dropped</option>
            <option value="unenrolled" {{ $selectedStatus === 'unenrolled' ? 'selected' : '' }}>Unenrolled</option>
        </select>
        @error('status')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>
    @else
    <div>
        <div class="block text-sm font-medium text-gray-700">Status</div>
        <div class="mt-2 h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 flex items-center text-sm">
            <span class="inline-flex items-center h-6 px-3 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-medium text-emerald-700">
                Active (auto-set)
            </span>
        </div>
    </div>
    @endif

    <div>
        <label class="block text-sm font-medium text-gray-700" for="enrolled_at">Enrollment Date</label>
        <input id="enrolled_at" name="enrolled_at" type="date" value="{{ $enrolledAtValue }}"
            class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
        @error('enrolled_at')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>
</div>

<script>
(function () {
    const courseSelect    = document.getElementById('course_id');
    const teacherDisplay  = document.getElementById('teacherDisplay');
    const semesterDisplay = document.getElementById('semesterDisplay');
    const schoolYrDisplay = document.getElementById('schoolYearDisplay');
    const scheduleDisplay = document.getElementById('scheduleDisplay');

    const courseData = @json($courseDataMap);

    const semesterLabels = {
        first:  '1st Semester',
        second: '2nd Semester',
        summer: 'Summer',
    };

    function formatTime(t) {
        if (!t) return '';
        const parts = t.split(':');
        let h = parseInt(parts[0], 10);
        const m = parts[1] || '00';
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return `${h}:${m} ${ampm}`;
    }

    function update() {
        const id   = courseSelect ? courseSelect.value : '';
        const data = id ? (courseData[id] || {}) : {};

        if (teacherDisplay)  teacherDisplay.textContent  = data.teacher_name  || '—';
        if (semesterDisplay) semesterDisplay.textContent = data.semester ? (semesterLabels[data.semester] || data.semester) : '—';
        if (schoolYrDisplay) schoolYrDisplay.textContent = data.school_year   || '—';

        if (scheduleDisplay) {
            const days  = data.days_pattern || '';
            const start = formatTime(data.start_time);
            const end   = formatTime(data.end_time);
            const time  = (start && end) ? `${start} — ${end}` : (start || end || '');
            scheduleDisplay.textContent = days
                ? (time ? `${days}, ${time}` : days)
                : (time || '—');
        }
    }

    courseSelect?.addEventListener('change', update);
    update();
})();
</script>
