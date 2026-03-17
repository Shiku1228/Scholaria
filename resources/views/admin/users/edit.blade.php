@extends('layouts.admin')

@section('content')
    @php
        $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $fallbackFirst = $nameParts[0] ?? '';
        $fallbackLast = count($nameParts) > 1 ? (string) end($nameParts) : '';
        $fallbackMiddle = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';
    @endphp
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
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->first_name ?? $fallbackFirst) }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                @error('first_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="middle_name">Middle Name</label>
                <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $user->middle_name ?? $fallbackMiddle) }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                @error('middle_name')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" for="last_name">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->last_name ?? $fallbackLast) }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
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
            @php
                $currentRole = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'Student') : 'Student';
            @endphp
            <select id="role" name="role" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                <option value="Admin" {{ old('role', $currentRole) === 'Admin' ? 'selected' : '' }}>Admin</option>
                <option value="Teacher" {{ old('role', $currentRole) === 'Teacher' ? 'selected' : '' }}>Teacher</option>
                <option value="Student" {{ old('role', $currentRole) === 'Student' ? 'selected' : '' }}>Student</option>
            </select>
            @error('role')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
        </div>

        <div id="studentNumberGroup">
            <label class="block text-sm font-medium text-gray-700" for="student_number">Student Number</label>
            <input id="student_number" name="student_number" type="text" value="{{ old('student_number', $user->student_number ?? '') }}" class="mt-2 block w-full h-11 rounded-xl border border-gray-200 px-4 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="e.g. 2026-000123">
            @error('student_number')<div class="mt-2 text-sm text-red-600">{{ $message }}</div>@enderror
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

