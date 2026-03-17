@php
    $editing = isset($course) && $course;
    $courseDays = [];

    if ($editing && !empty($course->days_pattern)) {
        $courseDays = array_values(array_filter(array_map('trim', explode(',', (string) $course->days_pattern))));
    }

    $selectedDays = old('class_days', $courseDays);
    $labelClass = 'block text-sm font-semibold text-slate-700';
    $inputClass = 'mt-2 block w-full h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]';
    $textareaClass = 'mt-2 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]';
@endphp

<section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 space-y-5">
    <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Course Information</div>

    <div>
        <label class="{{ $labelClass }}" for="course_number">Course Number</label>
        <input id="course_number" name="course_number" type="text" value="{{ old('course_number', $course->course_number ?? '') }}" class="{{ $inputClass }}" required>
        <div id="courseNumberLiveMessage" class="mt-2 text-sm hidden"></div>
        @error('course_number')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="{{ $labelClass }}" for="course_title">Course Title</label>
        <input id="course_title" name="course_title" type="text" value="{{ old('course_title', $course->title ?? '') }}" class="{{ $inputClass }}" required>
        @error('course_title')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="{{ $labelClass }}" for="course_description">Course Description</label>
        <textarea id="course_description" name="course_description" rows="4" class="{{ $textareaClass }}">{{ old('course_description', $course->description ?? '') }}</textarea>
        @error('course_description')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-5">
    <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Schedule</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="{{ $labelClass }}" for="semester">Semester</label>
            <select id="semester" name="semester" class="{{ $inputClass }}" required>
                @php
                    $semesterValue = old('semester', $course->semester ?? '');
                @endphp
                <option value="" {{ $semesterValue === '' ? 'selected' : '' }}>Select semester</option>
                <option value="first" {{ $semesterValue === 'first' ? 'selected' : '' }}>1st</option>
                <option value="second" {{ $semesterValue === 'second' ? 'selected' : '' }}>2nd</option>
                <option value="summer" {{ $semesterValue === 'summer' ? 'selected' : '' }}>Summer</option>
            </select>
            @error('semester')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="{{ $labelClass }}" for="school_year">School Year</label>
            <input id="school_year" name="school_year" type="text" value="{{ old('school_year', $course->school_year ?? '') }}" placeholder="e.g. 2025-2026" class="{{ $inputClass }}">
            @error('school_year')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="{{ $labelClass }}" for="start_date">Start Date</label>
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', optional($course->start_date ?? null)?->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @error('start_date')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="{{ $labelClass }}" for="end_date">End Date</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', optional($course->end_date ?? null)?->format('Y-m-d')) }}" class="{{ $inputClass }}">
            @error('end_date')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="{{ $labelClass }}" for="class_time_start">Class Time Start</label>
            <input id="class_time_start" name="class_time_start" type="time" value="{{ old('class_time_start', $course->start_time ?? '') }}" class="{{ $inputClass }}">
            @error('class_time_start')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="{{ $labelClass }}" for="class_time_end">Class Time End</label>
            <input id="class_time_end" name="class_time_end" type="time" value="{{ old('class_time_end', $course->end_time ?? '') }}" class="{{ $inputClass }}">
            @error('class_time_end')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>
    </div>

    <div>
        <div class="{{ $labelClass }}">Class Days</div>
        <div class="mt-3 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
            @foreach ([
                'Mon' => 'Mon',
                'Tue' => 'Tue',
                'Wed' => 'Wed',
                'Thu' => 'Thu',
                'Fri' => 'Fri',
                'Sat' => 'Sat',
                'Sun' => 'Sun',
            ] as $dayValue => $dayLabel)
                <label class="group inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm text-slate-700 hover:border-[#0b2d6b] hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="class_days[]" value="{{ $dayValue }}" class="rounded border-slate-300 text-[#0b2d6b] focus:ring-[#0b2d6b]" {{ in_array($dayValue, (array) $selectedDays, true) ? 'checked' : '' }}>
                    <span>{{ $dayLabel }}</span>
                </label>
            @endforeach
        </div>
        @error('class_days')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
    <div class="text-sm font-semibold uppercase tracking-wide text-slate-500">Assigned Instructor</div>

    <div class="mt-3">
        <label class="{{ $labelClass }}" for="teacher_id">Assigned Teacher</label>
        <select id="teacher_id" name="teacher_id" class="{{ $inputClass }}" required>
            <option value="">{{ $teachers->isEmpty() ? 'No teachers available' : 'Select teacher' }}</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" {{ (string) old('teacher_id', $course->teacher_id ?? '') === (string) $teacher->id ? 'selected' : '' }}>
                    {{ $teacher->name }}{{ !empty($teacher->email) ? ' (' . $teacher->email . ')' : '' }}
                </option>
            @endforeach
        </select>
        @error('teacher_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
    </div>
</section>

<script>
    (function () {
        const startDate = document.getElementById('start_date');
        const schoolYear = document.getElementById('school_year');

        startDate?.addEventListener('change', function () {
            if (!this.value) {
                return;
            }

            const year = new Date(this.value).getFullYear();
            if (!Number.isFinite(year)) {
                return;
            }

            const next = year + 1;
            if (!schoolYear.value || schoolYear.value.trim() === '') {
                schoolYear.value = `${year}-${next}`;
            } else {
                schoolYear.value = `${year}-${next}`;
            }
        });

        const courseNumber = document.getElementById('course_number');
        const msg = document.getElementById('courseNumberLiveMessage');
        const ignoreId = {{ isset($course) ? (int) $course->id : 0 }};
        let timer = null;

        function setMsg(text, type) {
            if (!msg) return;
            msg.classList.remove('hidden');
            msg.classList.remove('text-red-600', 'text-green-600', 'text-gray-500');
            msg.classList.add(type === 'error' ? 'text-red-600' : type === 'ok' ? 'text-green-600' : 'text-gray-500');
            msg.textContent = text;
        }

        async function check() {
            const value = (courseNumber?.value || '').trim();
            if (!value) {
                if (msg) msg.classList.add('hidden');
                return;
            }

            try {
                const url = new URL(@json(route('admin.courses.check-number')));
                url.searchParams.set('course_number', value);
                if (ignoreId) {
                    url.searchParams.set('ignore_id', String(ignoreId));
                }

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();

                if (data && data.exists) {
                    setMsg('Course number already exists. Please use a different course number.', 'error');
                } else {
                    setMsg('Course number is available.', 'ok');
                }
            } catch (e) {
                setMsg('Unable to validate course number right now.', 'muted');
            }
        }

        courseNumber?.addEventListener('input', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(check, 350);
        });
    })();
</script>
