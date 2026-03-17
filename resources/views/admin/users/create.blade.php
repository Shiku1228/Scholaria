@extends('layouts.admin')

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

        <div id="studentNumberGroup">
            <label class="block text-sm font-medium text-gray-700" for="student_number">Student Number</label>
            <input id="student_number" name="student_number" type="text" value="{{ old('student_number') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. 2026-000123">
            @error('student_number')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
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
            const studentGroup = document.getElementById('studentNumberGroup');
            const studentInput = document.getElementById('student_number');
            if (!roleEl || !studentGroup || !studentInput) return;

            function toggleStudentNumber() {
                const isStudent = roleEl.value === 'Student';
                studentGroup.style.display = isStudent ? '' : 'none';
                studentInput.required = isStudent;
                if (!isStudent) {
                    studentInput.value = '';
                }
            }

            roleEl.addEventListener('change', toggleStudentNumber);
            toggleStudentNumber();
        })();
    </script>
@endsection

