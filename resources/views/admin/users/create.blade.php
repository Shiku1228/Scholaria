@extends('layouts.dashboard', [
    'title' => 'Create User',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    @php
        $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
        $programOptions = [
            'BS Computer Science',
            'BS Information Technology',
            'BS Information Systems',
            'BS Civil Engineering',
            'BS Mechanical Engineering',
            'BS Electrical Engineering',
            'BS Electronics Engineering',
            'BS Architecture',
            'BS Accountancy',
            'BS Business Administration',
            'BS Entrepreneurship',
            'BS Hospitality Management',
            'BS Tourism Management',
            'BS Psychology',
            'BS Criminology',
            'BS Nursing',
            'BS Medical Technology',
            'BS Biology',
            'BS Agriculture',
            'Bachelor of Elementary Education',
            'Bachelor of Secondary Education',
            'BA English Language Studies',
            'BA Political Science',
        ];
        $collegeOptions = [
            'College of Computer Studies',
            'College of Engineering',
            'College of Architecture',
            'College of Business Administration',
            'College of Hospitality and Tourism Management',
            'College of Education',
            'College of Arts and Sciences',
            'College of Criminal Justice Education',
            'College of Nursing',
            'College of Agriculture',
        ];
        $teacherProgramOptions = [
            'Computer Science Department' => 'College of Computer Studies',
            'Information Technology Department' => 'College of Computer Studies',
            'Information Systems Department' => 'College of Computer Studies',
            'Civil Engineering Department' => 'College of Engineering',
            'Mechanical Engineering Department' => 'College of Engineering',
            'Electrical Engineering Department' => 'College of Engineering',
            'Electronics Engineering Department' => 'College of Engineering',
            'Architecture Department' => 'College of Architecture',
            'Accountancy Department' => 'College of Business Administration',
            'Business Administration Department' => 'College of Business Administration',
            'Entrepreneurship Department' => 'College of Business Administration',
            'Hospitality Management Department' => 'College of Hospitality and Tourism Management',
            'Tourism Management Department' => 'College of Hospitality and Tourism Management',
            'Education Department' => 'College of Education',
            'Psychology Department' => 'College of Arts and Sciences',
            'English Language Studies Department' => 'College of Arts and Sciences',
            'Political Science Department' => 'College of Arts and Sciences',
            'Criminology Department' => 'College of Criminal Justice Education',
            'Nursing Department' => 'College of Nursing',
            'Agriculture Department' => 'College of Agriculture',
        ];
        $teacherSpecializationOptions = [
            'Software Engineering' => 'Computer Science Department',
            'Web Development' => 'Computer Science Department',
            'Mobile Application Development' => 'Information Technology Department',
            'Data Science' => 'Information Systems Department',
            'Artificial Intelligence' => 'Computer Science Department',
            'Cybersecurity' => 'Information Technology Department',
            'Network Administration' => 'Information Technology Department',
            'Database Management' => 'Information Systems Department',
            'Civil Engineering' => 'Civil Engineering Department',
            'Mechanical Engineering' => 'Mechanical Engineering Department',
            'Electrical Engineering' => 'Electrical Engineering Department',
            'Electronics Engineering' => 'Electronics Engineering Department',
            'Architecture' => 'Architecture Department',
            'Accounting' => 'Accountancy Department',
            'Business Management' => 'Business Administration Department',
            'Entrepreneurship' => 'Entrepreneurship Department',
            'Hospitality Management' => 'Hospitality Management Department',
            'Tourism Management' => 'Tourism Management Department',
            'General Education' => 'Education Department',
            'English' => 'English Language Studies Department',
            'Mathematics' => 'Education Department',
            'Science' => 'Education Department',
            'Social Studies' => 'Education Department',
            'Psychology' => 'Psychology Department',
            'Political Science' => 'Political Science Department',
            'Criminology' => 'Criminology Department',
            'Nursing' => 'Nursing Department',
            'Agriculture' => 'Agriculture Department',
        ];
        $yearLevelValue = old('year_level', '');
        $programValue = old('program', '');
        $collegeValue = old('college', '');
        $specializationValue = old('specialization', '');
    @endphp

    <div>
        <div class="text-xl font-semibold">Create User</div>
        <div class="text-sm text-gray-500">Add a teacher or student account</div>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="first_name">First Name</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @error('first_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="middle_name">Middle Name</label>
                <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                @error('middle_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="last_name">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @error('last_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
            @error('email')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="role">Role</label>
            <select id="role" name="role" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @php
                    $selectedRole = old('role', request()->query('role', 'Student'));
                @endphp

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
            <div id="studentFields" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="student_number">Student Number</label>
                    <input id="student_number" name="student_number" type="text" value="{{ old('student_number') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. 2026-000123">
                    @error('student_number')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $yearLevelValue === '' ? 'selected' : '' }}>Select Year Level</option>
                            @if($yearLevelValue !== '' && !in_array($yearLevelValue, $yearLevels, true))
                                <option value="{{ $yearLevelValue }}" selected>{{ $yearLevelValue }}</option>
                            @endif
                            @foreach($yearLevels as $yearLevel)
                                <option value="{{ $yearLevel }}" {{ $yearLevelValue === $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                            @endforeach
                        </select>
                        @error('year_level')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="program">Program</label>
                        <select id="program" name="program" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $programValue === '' ? 'selected' : '' }}>Select Program</option>
                            @if($programValue !== '' && !in_array($programValue, $programOptions, true))
                                <option value="{{ $programValue }}" selected>{{ $programValue }}</option>
                            @endif
                            @foreach($programOptions as $programOption)
                                <option value="{{ $programOption }}" {{ $programValue === $programOption ? 'selected' : '' }}>{{ $programOption }}</option>
                            @endforeach
                        </select>
                        @error('program')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="college">College</label>
                        <select id="college" name="college" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $collegeValue === '' ? 'selected' : '' }}>Select College</option>
                            @if($collegeValue !== '' && !in_array($collegeValue, $collegeOptions, true))
                                <option value="{{ $collegeValue }}" selected>{{ $collegeValue }}</option>
                            @endif
                            @foreach($collegeOptions as $collegeOption)
                                <option value="{{ $collegeOption }}" {{ $collegeValue === $collegeOption ? 'selected' : '' }}>{{ $collegeOption }}</option>
                            @endforeach
                        </select>
                        @error('college')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Teacher Fields -->
            <div id="teacherFields" class="space-y-4 hidden">
                <div>
                    <label class="block text-sm font-medium text-gray-700" for="employee_id">Employee ID</label>
                    <input id="employee_id" name="employee_id" type="text" value="{{ old('employee_id') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. EMP-2026-001">
                    @error('employee_id')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="teacher_college">College</label>
                        <select id="teacher_college" name="college" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $collegeValue === '' ? 'selected' : '' }}>Select College</option>
                            @if($collegeValue !== '' && !in_array($collegeValue, $collegeOptions, true))
                                <option value="{{ $collegeValue }}" selected>{{ $collegeValue }}</option>
                            @endif
                            @foreach($collegeOptions as $collegeOption)
                                <option value="{{ $collegeOption }}" {{ $collegeValue === $collegeOption ? 'selected' : '' }}>{{ $collegeOption }}</option>
                            @endforeach
                        </select>
                        @error('college')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="teacher_program">Program</label>
                        <select id="teacher_program" name="program" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $programValue === '' ? 'selected' : '' }}>Select Program</option>
                            @if($programValue !== '' && !array_key_exists($programValue, $teacherProgramOptions))
                                <option value="{{ $programValue }}" data-college="{{ $collegeValue }}" selected>{{ $programValue }}</option>
                            @endif
                            @foreach($teacherProgramOptions as $teacherProgram => $teacherCollege)
                                <option value="{{ $teacherProgram }}" data-college="{{ $teacherCollege }}" {{ $programValue === $teacherProgram ? 'selected' : '' }}>{{ $teacherProgram }}</option>
                            @endforeach
                        </select>
                        @error('program')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="specialization">Specialization</label>
                        <select id="specialization" name="specialization" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="" disabled {{ $specializationValue === '' ? 'selected' : '' }}>Select Specialization</option>
                            @if($specializationValue !== '' && !array_key_exists($specializationValue, $teacherSpecializationOptions))
                                <option value="{{ $specializationValue }}" data-program="{{ $programValue }}" selected>{{ $specializationValue }}</option>
                            @endif
                            @foreach($teacherSpecializationOptions as $specializationOption => $teacherProgram)
                                <option value="{{ $specializationOption }}" data-program="{{ $teacherProgram }}" {{ $specializationValue === $specializationOption ? 'selected' : '' }}>{{ $specializationOption }}</option>
                            @endforeach
                        </select>
                        @error('specialization')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Admin Fields -->
            <div id="adminFields" class="space-y-4 hidden">
                <div class="text-sm text-gray-500">
                    Admin level and access scope are determined automatically from the selected admin role.
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="password">Password</label>
            <input id="password" name="password" type="password" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
            @error('password')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700" for="password_confirmation">Confirm Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Save</button>
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
                    studentNumberInput.required = true;
                    employeeIdInput.required = false;
                } else if (role === 'Teacher') {
                    teacherFields?.classList.remove('hidden');
                    teacherInputs.forEach(input => input.disabled = false);
                    studentNumberInput.required = false;
                    employeeIdInput.required = true;
                } else if (adminRoleValues.has(role)) {
                    adminFields?.classList.remove('hidden');
                    adminInputs.forEach(input => input.disabled = false);
                    studentNumberInput.required = false;
                    employeeIdInput.required = false;
                }
            }

            roleEl.addEventListener('change', toggleRoleFields);
            toggleRoleFields();

            const programEl = document.getElementById('program');
            const collegeEl = document.getElementById('college');
            const programCollegeMap = {
                'BS Computer Science': 'College of Computer Studies',
                'BS Information Technology': 'College of Computer Studies',
                'BS Information Systems': 'College of Computer Studies',
                'BS Civil Engineering': 'College of Engineering',
                'BS Mechanical Engineering': 'College of Engineering',
                'BS Electrical Engineering': 'College of Engineering',
                'BS Electronics Engineering': 'College of Engineering',
                'BS Architecture': 'College of Architecture',
                'BS Accountancy': 'College of Business Administration',
                'BS Business Administration': 'College of Business Administration',
                'BS Entrepreneurship': 'College of Business Administration',
                'BS Hospitality Management': 'College of Hospitality and Tourism Management',
                'BS Tourism Management': 'College of Hospitality and Tourism Management',
                'BS Psychology': 'College of Arts and Sciences',
                'BS Criminology': 'College of Criminal Justice Education',
                'BS Nursing': 'College of Nursing',
                'BS Medical Technology': 'College of Nursing',
                'BS Biology': 'College of Arts and Sciences',
                'BS Agriculture': 'College of Agriculture',
                'Bachelor of Elementary Education': 'College of Education',
                'Bachelor of Secondary Education': 'College of Education',
                'BA English Language Studies': 'College of Arts and Sciences',
                'BA Political Science': 'College of Arts and Sciences',
            };

            programEl?.addEventListener('change', function () {
                const college = programCollegeMap[this.value];
                if (college && collegeEl) {
                    collegeEl.value = college;
                }
            });

            const teacherCollegeEl = document.getElementById('teacher_college');
            const teacherProgramEl = document.getElementById('teacher_program');
            const specializationEl = document.getElementById('specialization');

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

                filterTeacherSpecializations(preserveSelected);
            }

            function filterTeacherSpecializations(preserveSelected = false) {
                if (!teacherProgramEl || !specializationEl) return;

                const program = teacherProgramEl.value;
                let selectedStillVisible = false;
                Array.from(specializationEl.options).forEach(option => {
                    if (!option.value) return;
                    const visible = !program || option.dataset.program === program || (preserveSelected && option.selected);
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible && option.selected) selectedStillVisible = true;
                });

                if (!selectedStillVisible) {
                    specializationEl.value = '';
                }
            }

            teacherCollegeEl?.addEventListener('change', () => filterTeacherPrograms(false));
            teacherProgramEl?.addEventListener('change', () => filterTeacherSpecializations(false));
            filterTeacherPrograms(true);
        })();
    </script>
@endsection
