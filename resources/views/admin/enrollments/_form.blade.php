@php
    $editing = isset($enrollment) && $enrollment;

    $selectedCourseId = (string) old('course_id', $editing ? $enrollment->course_id : '');
    $selectedStudentId = (string) old('student_id', $editing ? $enrollment->student_id : '');
    $selectedStatus = (string) old('status', $editing ? $enrollment->status : 'active');

    $enrolledAtValue = old('enrolled_at');
    if ($enrolledAtValue === null || $enrolledAtValue === '') {
        $enrolledAtValue = $editing && $enrollment->enrolled_at ? $enrollment->enrolled_at->format('Y-m-d') : now()->format('Y-m-d');
    }
@endphp

<div>
    <label class="block text-sm font-medium text-gray-700" for="student_id">Student</label>
    <select id="student_id" name="student_id" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-violet-500 focus:ring-violet-500" required>
        <option value="">Select student</option>
        @foreach ($students as $student)
            <option value="{{ $student->id }}" {{ $selectedStudentId === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
        @endforeach
    </select>
    @error('student_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700" for="course_id">Course</label>
    <select id="course_id" name="course_id" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-violet-500 focus:ring-violet-500" required>
        <option value="">Select course</option>
        @foreach ($courses as $course)
            @php
                $hasTeacher = (bool) $course->teacher_id;
            @endphp
            <option
                value="{{ $course->id }}"
                data-teacher-name="{{ $course->teacher?->name ?? '' }}"
                data-teacher-id="{{ $course->teacher_id ?? '' }}"
                {{ $selectedCourseId === (string) $course->id ? 'selected' : '' }}
                {{ $hasTeacher ? '' : 'disabled' }}
            >
                {{ $course->course_number }} - {{ $course->title }}{{ $hasTeacher ? '' : ' (no teacher assigned)' }}
            </option>
        @endforeach
    </select>
    @error('course_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
</div>

<div>
    <div class="block text-sm font-medium text-gray-700">Teacher</div>
    <div id="teacherDisplay" class="mt-2 h-11 rounded-xl border border-gray-200 bg-gray-50 px-4 flex items-center text-sm text-gray-700">
        —
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div>
        <label class="block text-sm font-medium text-gray-700" for="status">Status</label>
        <select id="status" name="status" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-violet-500 focus:ring-violet-500" required>
            <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active</option>
            <option value="completed" {{ $selectedStatus === 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="dropped" {{ $selectedStatus === 'dropped' ? 'selected' : '' }}>Dropped</option>
        </select>
        @error('status')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700" for="enrolled_at">Enrollment Date</label>
        <input id="enrolled_at" name="enrolled_at" type="date" value="{{ $enrolledAtValue }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-violet-500 focus:ring-violet-500">
        @error('enrolled_at')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>
</div>

<script>
    (function () {
        const courseSelect = document.getElementById('course_id');
        const teacherDisplay = document.getElementById('teacherDisplay');

        function updateTeacher() {
            const opt = courseSelect?.selectedOptions?.[0];
            const name = opt?.dataset?.teacherName || '';

            if (!teacherDisplay) return;
            teacherDisplay.textContent = name ? name : '—';
        }

        courseSelect?.addEventListener('change', updateTeacher);
        updateTeacher();
    })();
</script>
