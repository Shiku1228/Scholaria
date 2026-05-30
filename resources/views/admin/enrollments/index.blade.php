@extends('layouts.dashboard', [
    'title' => 'Manage Enrollments',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
<div class="space-y-5">

    {{-- ── Page Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="text-xl font-semibold text-slate-900">Manage Enrollments</div>
            <div class="text-sm text-slate-500 mt-0.5">Select a course to view and manage student enrollments.</div>
        </div>
    </div>

    {{-- ── Flash Messages ──────────────────────────────────────────────────── --}}
    @if (session('success'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            <i data-lucide="check-circle" style="width:16px;height:16px;" class="flex-shrink-0"></i>
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i data-lucide="alert-circle" style="width:16px;height:16px;" class="flex-shrink-0"></i>
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li class="flex items-center gap-2">
                        <i data-lucide="alert-circle" style="width:14px;height:14px;" class="flex-shrink-0"></i>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Course Selector + Filters ───────────────────────────────────────── --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
        <form method="GET" action="{{ route('admin.enrollments.index') }}" id="filter-form">

            {{-- Primary: Course selector --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5" for="course_id">
                    Select Course / Subject
                </label>
                <select id="course_id" name="course_id"
                    class="h-11 w-full rounded-xl border-slate-300 bg-white text-sm font-medium text-slate-800 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                    onchange="this.form.submit()">
                    <option value="">— All Courses —</option>
                    @foreach ($courses as $c)
                        <option value="{{ $c->id }}"
                            {{ (string) $filterCourse === (string) $c->id ? 'selected' : '' }}>
                            {{ !empty($c->course_code) ? $c->course_code . ' — ' : '' }}{{ $c->title }} — CN: {{ $c->course_number }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Secondary: Text search + dropdown filters --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                <div class="xl:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Search</label>
                    <input name="search" type="text" value="{{ $search }}"
                        placeholder="Student name, email, student no., course code, CN, teacher…"
                        class="h-10 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Teacher</label>
                    <select name="teacher_id"
                        class="h-10 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="">All</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ $fTeacherId === $teacher->id ? 'selected' : '' }}>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Semester</label>
                    <select name="semester"
                        class="h-10 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="" {{ $fSemester === '' ? 'selected' : '' }}>All</option>
                        <option value="first"  {{ $fSemester === 'first'  ? 'selected' : '' }}>1st</option>
                        <option value="second" {{ $fSemester === 'second' ? 'selected' : '' }}>2nd</option>
                        <option value="summer" {{ $fSemester === 'summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Status</label>
                    <select name="status"
                        class="h-10 w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value=""           {{ $fStatus === ''           ? 'selected' : '' }}>All</option>
                        <option value="active"     {{ $fStatus === 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="completed"  {{ $fStatus === 'completed'  ? 'selected' : '' }}>Completed</option>
                        <option value="dropped"    {{ $fStatus === 'dropped'    ? 'selected' : '' }}>Dropped</option>
                        <option value="unenrolled" {{ $fStatus === 'unenrolled' ? 'selected' : '' }}>Unenrolled</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 flex items-center gap-2">
                <button type="submit"
                    class="h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Apply Filters
                </button>
                @if ($hasFilters || $filterCourse !== '')
                    <a href="{{ route('admin.enrollments.index') }}"
                        class="h-10 px-4 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-600 hover:bg-slate-50 inline-flex items-center gap-1.5">
                        <i data-lucide="x" style="width:13px;height:13px;"></i> Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ── BRANCH: No course + no filters = empty state ────────────────────── --}}
    @if ($filterCourse === '' && ! $hasFilters)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm py-16 text-center">
            <i data-lucide="book-open" class="mx-auto mb-3 text-slate-300" style="width:44px;height:44px;"></i>
            <div class="text-sm font-semibold text-slate-500">Select a course to view enrollments.</div>
            <div class="text-xs text-slate-400 mt-1">Choose a course from the dropdown above, or use the filters to search across all courses.</div>
        </div>

    {{-- ── BRANCH: No course + filters applied = grouped view ─────────────── --}}
    @elseif ($filterCourse === '' && $hasFilters)

        @if ($groupedEnrollments && $groupedEnrollments->isNotEmpty())
            @foreach ($groupedEnrollments as $courseId => $courseRows)
                @php
                    $gc = $courseRows->first()?->course;
                    $gcCode  = !empty($gc?->course_code) ? $gc->course_code . ' — ' : '';
                    $gcTitle = $gc?->title ?? '—';
                    $gcNum   = $gc?->course_number ?? '—';
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $gcCode }}{{ $gcTitle }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">CN: {{ $gcNum }}</div>
                        </div>
                        <span class="inline-flex items-center justify-center h-6 px-2.5 rounded-full bg-[#eaf0fb] text-xs font-semibold text-[#0b2d6b]">
                            {{ $courseRows->count() }}
                        </span>
                    </div>
                    @include('admin.enrollments._table', ['rows' => $courseRows, 'showCourseCol' => false])
                </div>
            @endforeach
        @else
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm py-14 text-center">
                <i data-lucide="search-x" class="mx-auto mb-2 text-slate-300" style="width:36px;height:36px;"></i>
                <div class="text-sm text-slate-500">No enrollments match your search.</div>
            </div>
        @endif

    {{-- ── BRANCH: Specific course selected ───────────────────────────────── --}}
    @else
        @php
            $c = $selectedCourse;
            $rawStart = $c->start_time ?? '';
            $rawEnd   = $c->end_time   ?? '';

            try {
                $fmtStart = $rawStart ? \Carbon\Carbon::createFromFormat('H:i:s', $rawStart)->format('g:i A') : '—';
            } catch (\Throwable) {
                try { $fmtStart = $rawStart ? \Carbon\Carbon::createFromFormat('H:i', $rawStart)->format('g:i A') : '—'; }
                catch (\Throwable) { $fmtStart = $rawStart ?: '—'; }
            }
            try {
                $fmtEnd = $rawEnd ? \Carbon\Carbon::createFromFormat('H:i:s', $rawEnd)->format('g:i A') : '—';
            } catch (\Throwable) {
                try { $fmtEnd = $rawEnd ? \Carbon\Carbon::createFromFormat('H:i', $rawEnd)->format('g:i A') : '—'; }
                catch (\Throwable) { $fmtEnd = $rawEnd ?: '—'; }
            }

            $days     = $c->days_pattern ?? '';
            $schedule = ($days ? $days . ', ' : '') . $fmtStart . ' — ' . $fmtEnd;
            $semLabel = match($c->semester ?? '') {
                'first'  => '1st Semester',
                'second' => '2nd Semester',
                'summer' => 'Summer',
                default  => $c->semester ?? '—',
            };
        @endphp

        {{-- ── Course Info Card ─────────────────────────────────────────── --}}
        <div class="rounded-2xl border border-[#c9d7f2] bg-[#eaf0fb] p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-lg font-bold text-[#0b2d6b]">
                        {{ !empty($c->course_code) ? $c->course_code . ' — ' : '' }}{{ $c->title }}
                    </div>
                    <div class="text-sm text-[#3b5ea6] mt-0.5">CN: {{ $c->course_number }}</div>
                </div>
                <span class="inline-flex items-center gap-1.5 h-7 px-3 rounded-full bg-[#0b2d6b] text-white text-xs font-semibold">
                    <i data-lucide="users" style="width:12px;height:12px;"></i>
                    {{ $totalActive }} Active
                </span>
            </div>
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div>
                    <div class="text-xs font-medium text-[#3b5ea6]">Teacher</div>
                    <div class="text-sm font-semibold text-[#0b2d6b] mt-0.5">{{ $c->teacher?->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-[#3b5ea6]">Semester</div>
                    <div class="text-sm font-semibold text-[#0b2d6b] mt-0.5">{{ $semLabel }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-[#3b5ea6]">School Year</div>
                    <div class="text-sm font-semibold text-[#0b2d6b] mt-0.5">{{ $c->school_year ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-medium text-[#3b5ea6]">Schedule</div>
                    <div class="text-sm font-semibold text-[#0b2d6b] mt-0.5">{{ $schedule }}</div>
                </div>
            </div>
        </div>

        {{-- ── Add Enrollment Form ───────────────────────────────────────── --}}
        @php
        $aStudentData = $availableStudents->map(function ($s) use ($hasStudentSno) {
            $sno = '';
            // Prefer Student profile student_number (plain text — accurate)
            if ($hasStudentSno && $s->student && !empty($s->student->student_number)) {
                $sno = (string) $s->student->student_number;
            }
            // Fallback: User::student_number (encrypted, accessor decrypts)
            if (!$sno) {
                try { $sno = (string) ($s->student_number ?? ''); } catch (\Throwable) {}
            }
            return ['id' => $s->id, 'name' => (string) ($s->name ?? ''), 'email' => (string) ($s->email ?? ''), 'student_number' => $sno];
        });
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-slate-100">
                <div class="text-sm font-semibold text-slate-900">Add Student to This Course</div>
                <div class="text-xs text-slate-500 mt-0.5">
                    Status is automatically set to <span class="font-medium text-emerald-600">Active</span>.
                    Teacher and Semester are taken from the selected course.
                </div>
            </div>
            <form method="POST" action="{{ route('admin.enrollments.store') }}"
                  class="px-5 py-4 space-y-4" id="aEnrollForm">
                @csrf
                <input type="hidden" name="course_id" value="{{ $filterCourse }}">
                <input type="hidden" name="enrolled_at" value="{{ date('Y-m-d') }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Student Number (with suggestions dropdown) --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1" for="aStudentNoInput">
                            Student Number
                        </label>
                        <div class="relative">
                            <input type="text" id="aStudentNoInput"
                                placeholder="e.g. 2025-0001"
                                autocomplete="off"
                                {{ $availableStudents->isEmpty() ? 'disabled' : '' }}
                                class="h-10 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <div id="aStudentNoDropdown"
                                 class="hidden absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                            </div>
                        </div>
                        <div id="aStudentNoMsg" class="hidden mt-1 text-xs text-red-600">
                            No student found with this Student Number.
                        </div>
                    </div>

                    {{-- Student (searchable by name / email / student no.) --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">
                            Student
                            @if ($availableStudents->isEmpty())
                                <span class="ml-2 text-xs font-normal text-amber-600">— All students are already enrolled.</span>
                            @endif
                        </label>
                        <div class="relative">
                            <input type="text" id="aStudentSearchInput"
                                autocomplete="off"
                                placeholder="{{ $availableStudents->isEmpty() ? 'No available students' : 'Search by name, email, or student no.…' }}"
                                {{ $availableStudents->isEmpty() ? 'disabled' : '' }}
                                class="h-10 w-full rounded-xl border-slate-300 bg-white text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <input type="hidden" name="student_id" id="aStudentIdHidden">
                            <div id="aStudentDropdown"
                                 class="hidden absolute z-50 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto">
                            </div>
                        </div>
                        <div id="aStudentSelectErr" class="hidden mt-1 text-xs text-red-600">Please select a valid student.</div>
                        @error('student_id')
                            <div class="mt-1 text-xs text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Student Preview --}}
                <div id="aStudentPreview" class="hidden rounded-xl border border-[#c9d7f2] bg-[#eaf0fb] px-4 py-3">
                    <div class="text-xs font-semibold text-[#3b5ea6] mb-2">Selected Student</div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
                        <div>
                            <div class="text-xs text-[#3b5ea6]">Name</div>
                            <div class="font-medium text-[#0b2d6b] mt-0.5" id="aPreviewName"></div>
                        </div>
                        <div>
                            <div class="text-xs text-[#3b5ea6]">Student No.</div>
                            <div class="font-medium text-[#0b2d6b] mt-0.5" id="aPreviewStudentNo"></div>
                        </div>
                        <div>
                            <div class="text-xs text-[#3b5ea6]">Email</div>
                            <div class="font-medium text-[#0b2d6b] mt-0.5" id="aPreviewEmail"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        {{ $availableStudents->isEmpty() ? 'disabled' : '' }}
                        class="h-10 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-2">
                        <i data-lucide="user-plus" style="width:15px;height:15px;"></i>
                        Add Enrollment
                    </button>
                </div>
            </form>
        </div>

        <script>
        (function () {
            const students  = @json($aStudentData->values());
            const noInput   = document.getElementById('aStudentNoInput');
            const noMsg     = document.getElementById('aStudentNoMsg');
            const noDrop    = document.getElementById('aStudentNoDropdown');
            const searchIn  = document.getElementById('aStudentSearchInput');
            const selectErr = document.getElementById('aStudentSelectErr');
            const hiddenId  = document.getElementById('aStudentIdHidden');
            const dropdown  = document.getElementById('aStudentDropdown');
            const preview   = document.getElementById('aStudentPreview');
            const prvName   = document.getElementById('aPreviewName');
            const prvNo     = document.getElementById('aPreviewStudentNo');
            const prvEmail  = document.getElementById('aPreviewEmail');
            const form      = document.getElementById('aEnrollForm');

            if (!noInput || !searchIn) return;

            let selected = null;

            function esc(s) {
                return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            }

            function filterAll(q) {
                const lq = q.toLowerCase();
                return students.filter(function (s) {
                    return s.name.toLowerCase().includes(lq)
                        || s.email.toLowerCase().includes(lq)
                        || (s.student_number && s.student_number.toLowerCase().includes(lq));
                });
            }

            function filterBySno(q) {
                if (!q) return [];
                const lq = q.toLowerCase();
                return students.filter(function (s) {
                    return s.student_number && s.student_number.toLowerCase().includes(lq);
                });
            }

            // Search dropdown: shows Name — Email
            function renderSearchDrop(list) {
                dropdown.innerHTML = '';
                if (!list.length) {
                    dropdown.innerHTML = '<div class="px-4 py-3 text-sm text-slate-400">No students found.</div>';
                } else {
                    list.forEach(function (s) {
                        const el = document.createElement('div');
                        el.className = 'px-4 py-2.5 cursor-pointer hover:bg-[#eaf0fb] text-sm border-b border-slate-50 last:border-0';
                        el.innerHTML = '<div class="font-medium text-slate-900">' + esc(s.name) + '</div>'
                            + (s.email ? '<div class="text-xs text-slate-400">' + esc(s.email) + '</div>' : '');
                        el.addEventListener('mousedown', function (e) { e.preventDefault(); pick(s); });
                        dropdown.appendChild(el);
                    });
                }
                dropdown.classList.remove('hidden');
            }

            // Student Number suggestions: shows SNO — Name — Email
            function renderSnoDrop(list) {
                noDrop.innerHTML = '';
                if (!list.length) { noDrop.classList.add('hidden'); return; }
                list.forEach(function (s) {
                    const el = document.createElement('div');
                    el.className = 'px-4 py-2.5 cursor-pointer hover:bg-[#eaf0fb] text-sm border-b border-slate-50 last:border-0';
                    el.innerHTML = '<div class="font-medium text-slate-900">'
                        + (s.student_number ? esc(s.student_number) + ' — ' : '')
                        + esc(s.name) + '</div>'
                        + (s.email ? '<div class="text-xs text-slate-400">' + esc(s.email) + '</div>' : '');
                    el.addEventListener('mousedown', function (e) { e.preventDefault(); pick(s); });
                    noDrop.appendChild(el);
                });
                noDrop.classList.remove('hidden');
            }

            function pick(s) {
                selected          = s;
                hiddenId.value    = s.id;
                searchIn.value    = s.name + (s.email ? ' — ' + s.email : '');
                noInput.value     = s.student_number || '';
                noMsg.classList.add('hidden');
                selectErr.classList.add('hidden');
                dropdown.classList.add('hidden');
                noDrop.classList.add('hidden');
                prvName.textContent  = s.name;
                prvNo.textContent    = s.student_number || 'No Student No.';
                prvEmail.textContent = s.email || '—';
                preview.classList.remove('hidden');
            }

            function clearSel() {
                selected       = null;
                hiddenId.value = '';
                preview.classList.add('hidden');
            }

            // Student Number field — partial match, show suggestions
            noInput.addEventListener('input', function () {
                const val = this.value.trim();
                noMsg.classList.add('hidden');
                noDrop.classList.add('hidden');
                if (!val) { clearSel(); searchIn.value = ''; return; }
                const matches = filterBySno(val);
                if (!matches.length) {
                    clearSel();
                    searchIn.value = '';
                    noMsg.classList.remove('hidden');
                } else {
                    // Only auto-select when the user has typed the full exact student number
                    const exact = matches.find(function (s) {
                        return s.student_number.toLowerCase() === val.toLowerCase();
                    });
                    if (exact) { pick(exact); } else { renderSnoDrop(matches); }
                }
            });

            noInput.addEventListener('focus', function () {
                const val = this.value.trim();
                if (val && !selected) {
                    const matches = filterBySno(val);
                    if (matches.length > 1) renderSnoDrop(matches);
                }
            });

            noInput.addEventListener('blur', function () {
                setTimeout(function () { noDrop.classList.add('hidden'); }, 150);
            });

            // Student search field — filter by name / email / student number
            searchIn.addEventListener('input', function () {
                const val = this.value.trim();
                if (!val) { dropdown.classList.add('hidden'); clearSel(); return; }
                if (selected && searchIn.value === selected.name + (selected.email ? ' — ' + selected.email : '')) return;
                clearSel();
                renderSearchDrop(filterAll(val));
            });

            searchIn.addEventListener('focus', function () {
                const val = this.value.trim();
                if (!selected) renderSearchDrop(val ? filterAll(val) : students.slice(0, 20));
            });

            searchIn.addEventListener('blur', function () {
                setTimeout(function () { dropdown.classList.add('hidden'); }, 150);
            });

            // Prevent submit without a valid student selected
            if (form) {
                form.addEventListener('submit', function (e) {
                    if (!hiddenId.value) {
                        e.preventDefault();
                        selectErr.classList.remove('hidden');
                        searchIn.focus();
                    }
                });
            }

            // Restore old('student_id') after server-side validation error
            const oldId = '{{ old('student_id') }}';
            if (oldId) {
                const m = students.find(function (s) { return String(s.id) === oldId; });
                if (m) pick(m);
            }
        })();
        </script>

        {{-- ── Enrolled Students Table ───────────────────────────────────── --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold text-slate-900">Enrolled Students</div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        All enrollment records for this course.
                        @if ($fStatus !== '')
                            Filtered by: <span class="font-medium">{{ ucfirst($fStatus) }}</span>.
                        @endif
                    </div>
                </div>
                <span class="inline-flex items-center justify-center h-6 px-2.5 rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                    {{ $enrollments instanceof \Illuminate\Pagination\LengthAwarePaginator ? $enrollments->total() : $enrollments->count() }}
                </span>
            </div>

            @include('admin.enrollments._table', ['rows' => $enrollments, 'showCourseCol' => false])

            @if ($enrollments instanceof \Illuminate\Pagination\LengthAwarePaginator && $enrollments->hasPages())
                <div class="px-5 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $enrollments->links() }}
                </div>
            @endif
        </div>

    @endif {{-- end branch --}}

</div>
@endsection
