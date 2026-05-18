@extends('layouts.dashboard', [
    'title' => 'Edit User',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    @php
        $roleName = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Student') : 'Student';

        // Get profile data based on role
        $profile = $user->profile;
        $firstName = old('first_name', $profile?->first_name ?? '');
        $middleName = old('middle_name', $profile?->middle_name ?? '');
        $lastName = old('last_name', $profile?->last_name ?? '');

        // If no profile data, fallback to name parsing
        if (empty($firstName)) {
            $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? (string) end($nameParts) : '';
            $middleName = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';
        }

        // Role-specific profile data
        $studentNumber = old('student_number', $user->student?->student_number ?? '');
        $yearLevel = old('year_level', $user->student?->year_level ?? '');
        $program = old('program', $user->student?->program ?? ($user->teacher?->program ?? ''));
        $college = old('college', $user->student?->college ?? ($user->teacher?->college ?? ''));
        $employeeId = old('employee_id', $user->teacher?->employee_id ?? '');
        $specialization = old('specialization', $user->teacher?->specialization ?? '');
        $adminLevel = old('admin_level', $user->admin?->admin_level ?? 'standard');
        $accessScope = old('access_scope', $user->admin?->access_scope ?? 'all');
        $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];

        // Student catalog options come from DB (passed by controller)
        $colleges = $colleges ?? collect();
        $programs = $programs ?? collect();

        // Teacher program and specialization options are sourced from DB
        $specializations = $specializations ?? collect();
    @endphp
    @can('users.edit')
    <div>
        <div class="text-xl font-semibold">Edit User</div>
        <div class="text-sm text-gray-500">Update teacher or student account</div>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="first_name">First Name</label>
                <input id="first_name" name="first_name" type="text" value="{{ $firstName }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @error('first_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="middle_name">Middle Name</label>
                <input id="middle_name" name="middle_name" type="text" value="{{ $middleName }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                @error('middle_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="last_name">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="{{ $lastName }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @error('last_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
            @error('email')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="role">Role</label>
            <select id="role" name="role" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @php $selectedRole = old('role', $roleName); @endphp

                {{-- Admin roles from DB --}}
                @foreach(($adminRoleNames ?? []) as $adminRoleName)
                    <option value="{{ $adminRoleName }}" {{ $selectedRole === $adminRoleName ? 'selected' : '' }}>
                        {{ $adminRoleName }}
                    </option>
                @endforeach

                {{-- Non-admin roles --}}
                <option value="Teacher" {{ $selectedRole === 'Teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="Student" {{ $selectedRole === 'Student' ? 'selected' : '' }}>Student</option>
            </select>
            @error('role')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <!-- Role-specific fields -->
        <div id="roleSpecificFields">
            <!-- Student Fields -->
            <div id="studentFields" class="space-y-4 {{ $roleName !== 'Student' ? 'hidden' : '' }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="student_number">Student Number</label>
                    <input id="student_number" name="student_number" type="text" value="{{ $studentNumber }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. 2026-000123" {{ $roleName === 'Student' ? 'required' : '' }}>
                    @error('student_number')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $yearLevel === '' ? 'selected' : '' }}>Select Year Level</option>
                            @if($yearLevel !== '' && !in_array($yearLevel, $yearLevels, true))
                                <option value="{{ $yearLevel }}" selected>{{ $yearLevel }}</option>
                            @endif
                            @foreach($yearLevels as $yearLevelOption)
                                <option value="{{ $yearLevelOption }}" {{ $yearLevel === $yearLevelOption ? 'selected' : '' }}>{{ $yearLevelOption }}</option>
                            @endforeach
                        </select>
                        @error('year_level')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="program">Program</label>
                        <select id="program" name="program" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $program === '' ? 'selected' : '' }}>Select Program</option>

                            @php
                                $programNames = $programs->pluck('name')->all();
                            @endphp
                            @if($program !== '' && !in_array($program, $programNames, true))
                                <option value="{{ $program }}" selected>{{ $program }}</option>
                            @endif

                            @foreach($programs as $programOption)
                                @php
                                    $collegeName = optional($programOption->college)->name;
                                @endphp
                                <option
                                    value="{{ $programOption->name }}"
                                    data-college-name="{{ $collegeName }}"
                                    {{ $program === $programOption->name ? 'selected' : '' }}
                                >
                                    {{ $programOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="college">College</label>
                        <select id="college" name="college" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $college === '' ? 'selected' : '' }}>Select College</option>

                            @php
                                $collegeNames = $colleges->pluck('name')->all();
                            @endphp
                            @if($college !== '' && !in_array($college, $collegeNames, true))
                                <option value="{{ $college }}" selected>{{ $college }}</option>
                            @endif

                            @foreach($colleges as $collegeOption)
                                <option value="{{ $collegeOption->name }}" {{ $college === $collegeOption->name ? 'selected' : '' }}>
                                    {{ $collegeOption->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('college')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Teacher Fields -->
            <div id="teacherFields" class="space-y-4 {{ $roleName !== 'Teacher' ? 'hidden' : '' }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="employee_id">Employee ID</label>
                    <input id="employee_id" name="employee_id" type="text" value="{{ $employeeId }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. EMP-2026-001" {{ $roleName === 'Teacher' ? 'required' : '' }}>
                    @error('employee_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="teacher_college">College</label>
                        <select id="teacher_college" name="college" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $college === '' ? 'selected' : '' }}>Select College</option>
                            @php $collegeNamesForTeacher = $colleges->pluck('name')->all(); @endphp
                            @if($college !== '' && !in_array($college, $collegeNamesForTeacher, true))
                                <option value="{{ $college }}" selected>{{ $college }}</option>
                            @endif
                            @foreach($colleges as $collegeOption)
                                <option value="{{ $collegeOption->name }}" {{ $college === $collegeOption->name ? 'selected' : '' }}>{{ $collegeOption->name }}</option>
                            @endforeach
                        </select>
                        @error('college')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="teacher_program">Program</label>
                        <select id="teacher_program" name="program" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $program === '' ? 'selected' : '' }}>Select Program</option>

                            @php $programNamesForTeacher = $programs->pluck('name')->all(); @endphp

                            @if($program !== '' && !in_array($program, $programNamesForTeacher, true))
                                <option value="{{ $program }}" data-college="{{ $college }}" selected>{{ $program }}</option>
                            @endif

                            @foreach($programs as $programOption)
                                @php $teacherCollegeName = optional($programOption->college)->name; @endphp
                                <option value="{{ $programOption->name }}" data-college="{{ $teacherCollegeName }}" {{ $program === $programOption->name ? 'selected' : '' }}>{{ $programOption->name }}</option>
                            @endforeach
                        </select>
                        @error('program')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="specialization">Specialization</label>
                        <input id="specialization" name="specialization" list="specialization_list" type="text" value="{{ $specialization }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="Type specialization (e.g. Mathematics)">
                        <datalist id="specialization_list">
                            @foreach($specializations as $spec)
                                <option value="{{ $spec->specialization }}"></option>
                            @endforeach
                        </datalist>
                        @error('specialization')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Admin Fields -->
            <div id="adminFields" class="space-y-4 {{ $roleName !== 'Admin' ? 'hidden' : '' }}">
                <div class="text-sm text-gray-500">
                    Admin level and access scope are determined automatically from the selected admin role.
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="password">New Password (optional)</label>
            <input id="password" name="password" type="password" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
            @error('password')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="password_confirmation">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Update</button>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
    <script>
        (function () {
            const roleEl = document.getElementById('role');
            const studentFields = document.getElementById('studentFields');
            const teacherFields = document.getElementById('teacherFields');
            const adminFields = document.getElementById('adminFields');
            const studentNumberInput = document.getElementById('student_number');
            const employeeIdInput = document.getElementById('employee_id');

            // Get all inputs from each field group
            const studentInputs = studentFields?.querySelectorAll('input, select') || [];
            const teacherInputs = teacherFields?.querySelectorAll('input, select') || [];
            const adminInputs = adminFields?.querySelectorAll('input, select') || [];

            if (!roleEl) return;

            function toggleRoleFields() {
                const role = roleEl.value;

                // Hide and disable all first
                studentFields?.classList.add('hidden');
                teacherFields?.classList.add('hidden');
                adminFields?.classList.add('hidden');

                studentInputs.forEach(input => input.disabled = true);
                teacherInputs.forEach(input => input.disabled = true);
                adminInputs.forEach(input => input.disabled = true);

                // Show and enable relevant fields
                const adminRoleValues = new Set(@json($adminRoleNames ?? []));

                if (role === 'Student') {
                    studentFields?.classList.remove('hidden');
                    studentInputs.forEach(input => input.disabled = false);
                    if (studentNumberInput) studentNumberInput.required = true;
                    if (employeeIdInput) employeeIdInput.required = false;
                } else if (role === 'Teacher') {
                    teacherFields?.classList.remove('hidden');
                    teacherInputs.forEach(input => input.disabled = false);
                    if (studentNumberInput) studentNumberInput.required = false;
                    if (employeeIdInput) employeeIdInput.required = true;
                } else if (adminRoleValues.has(role)) {
                    adminFields?.classList.remove('hidden');
                    adminInputs.forEach(input => input.disabled = false);
                    if (studentNumberInput) studentNumberInput.required = false;
                    if (employeeIdInput) employeeIdInput.required = false;
                }
            }

            roleEl.addEventListener('change', toggleRoleFields);
            toggleRoleFields();

            const programEl = document.getElementById('program');
            const collegeEl = document.getElementById('college');

            programEl?.addEventListener('change', function () {
                if (!collegeEl) return;
                const selected = this.options[this.selectedIndex];
                const collegeName = selected?.dataset?.collegeName;
                if (collegeName) {
                    collegeEl.value = collegeName;
                }
            });

            // Set college on load if a program was already selected (old input / validation error)
            if (programEl && collegeEl && programEl.value) {
                const selected = programEl.options[programEl.selectedIndex];
                const collegeName = selected?.dataset?.collegeName;
                if (collegeName) {
                    collegeEl.value = collegeName;
                }
            }

            const teacherCollegeEl = document.getElementById('teacher_college');
            const teacherProgramEl = document.getElementById('teacher_program');

            function filterTeacherPrograms(preserveSelected = false) {
                if (!teacherCollegeEl || !teacherProgramEl) return;

                const college = teacherCollegeEl.value;
                let selectedStillVisible = false;
                Array.from(teacherProgramEl.options).forEach(option => {
                    if (!option.value) return;
                    const visible = !college || option.dataset.college === college || (preserveSelected && option.selected);
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible && option.selected) selectedStillVisible = true;
                });

                if (!selectedStillVisible) {
                    teacherProgramEl.value = '';
                }
            }

            teacherCollegeEl?.addEventListener('change', () => filterTeacherPrograms(false));
            filterTeacherPrograms(true);
        })();
    </script>
    @else
        <div class="mt-6 text-sm text-red-600">You do not have permission to edit users.</div>
    @endcan
@endsection
