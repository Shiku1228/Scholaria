@extends('layouts.teacher')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ (string) session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ (string) session('error') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="text-xl font-semibold">Enrollments</div>
            <div class="text-sm text-gray-500">Enrollments for your assigned courses</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100">
        <div class="p-4 sm:p-5 border-b border-gray-100">
            <div class="text-sm font-semibold">Add Student Enrollment</div>
            <div class="text-xs text-gray-500">Teachers can only add students to their own courses.</div>
        </div>
        <form method="POST" action="{{ route('teacher.enrollments.store') }}" class="p-4 sm:p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600" for="student_id">Student</label>
                <select id="student_id" name="student_id" class="mt-1 h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                    <option value="">Select student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" {{ (string) old('student_id') === (string) $student->id ? 'selected' : '' }}>{{ $student->name }}{{ !empty($student->email) ? ' (' . $student->email . ')' : '' }}</option>
                    @endforeach
                </select>
                @error('student_id')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600" for="course_id">Course</label>
                <select id="course_id" name="course_id" class="mt-1 h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                    <option value="">Select course</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" {{ (string) old('course_id') === (string) $course->id ? 'selected' : '' }}>{{ $course->course_number }} - {{ $course->title }}</option>
                    @endforeach
                </select>
                @error('course_id')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600" for="status">Status</label>
                <select id="status" name="status" class="mt-1 h-10 w-full rounded-lg border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="dropped" {{ old('status') === 'dropped' ? 'selected' : '' }}>Dropped</option>
                </select>
                @error('status')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
            </div>

            <div class="flex items-end">
                <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] w-full">
                    Add Enrollment
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
                <th class="py-3 px-4">Student</th>
                <th class="py-3 px-4">Course</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Enrollment Date</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse ($rows as $row)
                <tr class="text-gray-700">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $row->student_name ?? '--' }}</td>
                    <td class="py-3 px-4">{{ $row->course_name ?? '--' }}</td>
                    <td class="py-3 px-4">{{ $row->status ?? '--' }}</td>
                    <td class="py-3 px-4">{{ $row->enrolled_at ?? '--' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="py-10 px-4 text-center text-sm text-gray-500">No enrollments found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
