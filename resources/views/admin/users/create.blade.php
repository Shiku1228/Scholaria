@extends('layouts.dashboard', [
    'title' => 'Create User',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
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
                <option value="Admin" {{ old('role') === 'Admin' ? 'selected' : '' }}>Admin</option>
                <option value="Teacher" {{ old('role') === 'Teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="Student" {{ old('role', request()->query('role', 'Student')) === 'Student' ? 'selected' : '' }}>Student</option>
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
                        <input id="year_level" name="year_level" type="text" value="{{ old('year_level') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. 3rd Year">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="program">Program</label>
                        <input id="program" name="program" type="text" value="{{ old('program') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. BS Computer Science">
                        @error('program')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="college">College</label>
                        <input id="college" name="college" type="text" value="{{ old('college') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. College of Engineering">
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
                        <input id="teacher_college" name="college" type="text" value="{{ old('college') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. College of Engineering">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="teacher_program">Program</label>
                        <input id="teacher_program" name="program" type="text" value="{{ old('program') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. Computer Science Dept">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="specialization">Specialization</label>
                        <input id="specialization" name="specialization" type="text" value="{{ old('specialization') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. Software Engineering">
                    </div>
                </div>
            </div>

            <!-- Admin Fields -->
            <div id="adminFields" class="space-y-4 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="admin_level">Admin Level</label>
                        <select id="admin_level" name="admin_level" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="standard" {{ old('admin_level') === 'standard' ? 'selected' : '' }}>Standard</option>
                            <option value="super" {{ old('admin_level') === 'super' ? 'selected' : '' }}>Super Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="access_scope">Access Scope</label>
                        <select id="access_scope" name="access_scope" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                            <option value="all" {{ old('access_scope') === 'all' ? 'selected' : '' }}>All</option>
                            <option value="limited" {{ old('access_scope') === 'limited' ? 'selected' : '' }}>Limited</option>
                        </select>
                    </div>
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
                } else if (role === 'Admin') {
                    adminFields?.classList.remove('hidden');
                    adminInputs.forEach(input => input.disabled = false);
                    studentNumberInput.required = false;
                    employeeIdInput.required = false;
                }
            }

            roleEl.addEventListener('change', toggleRoleFields);
            toggleRoleFields();
        })();
    </script>
@endsection

