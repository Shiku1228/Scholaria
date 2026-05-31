@extends('layouts.dashboard', [
    'title' => 'Records',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    @php
        $activeTab = $activeTab ?? 'students';
        $search = $search ?? '';
        $yearLevel = $yearLevel ?? '';
        $program = $program ?? '';
        $college = $college ?? '';
        $hasFilters = $yearLevel || $program || $college;
    @endphp

    <div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        <div class="px-6 py-6 border-b border-slate-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                        <i data-lucide="file-text" class="h-5 w-5 text-[#0b2d6b]"></i>
                        <span>Records</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">View student and teacher records.</div>
                </div>

                <form method="GET" action="{{ route('admin.records.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="relative">
                        <i data-lucide="search" class="h-4 w-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input
                            type="text"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Search by name, ID, program..."
                            class="h-11 w-72 rounded-xl border border-slate-300 bg-slate-50 pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]"
                        >
                    </div>

                    @if($search)
                    <a href="{{ route('admin.records.index', ['tab' => $activeTab]) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-xl border border-slate-300 bg-slate-50 text-slate-700 text-sm font-medium hover:bg-slate-100">
                        Clear
                    </a>
                    @endif
                </form>
            </div>

            <!-- Tabs -->
            <div class="mt-4 flex border-b border-slate-200">
                <a href="{{ route('admin.records.index', ['tab' => 'students', 'q' => $search]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'students' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                        Students
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $students->total() }}</span>
                    </span>
                </a>
                <a href="{{ route('admin.records.index', ['tab' => 'teachers', 'q' => $search]) }}"
                   class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'teachers' ? 'border-[#0b2d6b] text-[#0b2d6b]' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
                    <span class="flex items-center gap-2">
                        <i data-lucide="briefcase" class="h-4 w-4"></i>
                        Teachers
                        <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $teachers->total() }}</span>
                    </span>
                </a>
            </div>

            <!-- Filters for Students -->
            @if($activeTab === 'students')
            <div class="mt-6 pt-6 border-t border-slate-200">
                <form method="GET" action="{{ route('admin.records.index') }}" id="studentFilters" class="flex flex-wrap items-end gap-4">
                    <input type="hidden" name="tab" value="students">
                    @if($search)
                    <input type="hidden" name="q" value="{{ $search }}">
                    @endif

                    <!-- Year Level Filter -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-slate-500">Year Level</label>
                        <select name="year_level" class="h-11 min-w-[150px] rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] outline-none cursor-pointer">
                            <option value="">All Years</option>
                            @foreach($yearLevels as $yl)
                                <option value="{{ $yl }}" {{ $yearLevel == $yl ? 'selected' : '' }}>{{ $yl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Program Filter -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-slate-500">Program</label>
                        <select name="program" class="h-11 min-w-[220px] rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] outline-none cursor-pointer">
                            <option value="">All Programs</option>
                            @foreach($programs as $prog)
                                <option value="{{ $prog }}" {{ $program == $prog ? 'selected' : '' }}>{{ $prog }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- College Filter -->
                    <div class="flex flex-col gap-2">
                        <label class="text-xs font-medium text-slate-500">College</label>
                        <select name="college" class="h-11 min-w-[200px] rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-1 focus:ring-[#0b2d6b] outline-none cursor-pointer">
                            <option value="">All Colleges</option>
                            @foreach($colleges as $col)
                                <option value="{{ $col }}" {{ $college == $col ? 'selected' : '' }}>{{ $col }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Apply Filter Button -->
                    <button type="submit" class="h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c] flex items-center gap-2">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        Apply
                    </button>

                    <!-- Clear Filters -->
                    @if($hasFilters)
                    <a href="{{ route('admin.records.index', ['tab' => 'students', 'q' => $search]) }}" class="h-11 px-5 rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-medium hover:bg-slate-50 flex items-center gap-2">
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Clear
                    </a>
                    @endif
                </form>

                <!-- Active Filters Display -->
                @if($hasFilters)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500">Active filters:</span>
                    @if($yearLevel)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-medium">
                            <i data-lucide="calendar" class="h-3 w-3"></i>
                            {{ $yearLevel }}
                        </span>
                    @endif
                    @if($program)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-medium">
                            <i data-lucide="book-open" class="h-3 w-3"></i>
                            {{ $program }}
                        </span>
                    @endif
                    @if($college)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-purple-50 text-purple-700 text-xs font-medium">
                            <i data-lucide="building-2" class="h-3 w-3"></i>
                            {{ $college }}
                        </span>
                    @endif
                </div>
                @endif
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            @if($activeTab === 'students')
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
                            <th class="py-4 px-6">Student</th>
                            <th class="py-4 px-6">Student Number</th>
                            <th class="py-4 px-6">Program & College</th>
                            <th class="py-4 px-6">Year Level</th>
                            <th class="py-4 px-6 text-center">Enrollments</th>
                            <th class="py-4 px-6 text-center">Submissions</th>
                            <th class="py-4 px-6">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($students as $student)
                        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 border border-blue-200 text-blue-700 flex items-center justify-center text-xs font-semibold">
                                        {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 leading-tight">{{ $student->full_name }}</div>
                                        <div class="text-xs text-slate-500">Enrolled: {{ $student->enrollment_date?->format('M d, Y') ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium">{{ $student->student_number }}</td>
                            <td class="py-4 px-6">
                                <div class="text-sm text-slate-900">{{ $student->program ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-500">{{ $student->college ?? 'N/A' }}</div>
                            </td>
                            <td class="py-4 px-6 text-sm text-slate-700">{{ $student->year_level ?? 'N/A' }}</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                    {{ $student->enrollments_count }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                    {{ $student->submissions_count }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-sm text-slate-600">{{ $student->user?->email ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-10 px-6 text-center text-sm text-slate-500">
                                @if($search)
                                    No students found matching "{{ $search }}"
                                @else
                                    No students found
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $students->onEachSide(1)->links() }}
                </div>

            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase border-b border-slate-200">
                            <th class="py-4 px-6">Teacher</th>
                            <th class="py-4 px-6">Employee ID</th>
                            <th class="py-4 px-6">Program & College</th>
                            <th class="py-4 px-6">Specialization</th>
                            <th class="py-4 px-6 text-center">Courses</th>
                            <th class="py-4 px-6 text-center">Announcements</th>
                            <th class="py-4 px-6">Email</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($teachers as $teacher)
                        <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-purple-100 border border-purple-200 text-purple-700 flex items-center justify-center text-xs font-semibold">
                                        {{ strtoupper(substr($teacher->first_name, 0, 1) . substr($teacher->last_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900 leading-tight">{{ $teacher->full_name }}</div>
                                        <div class="text-xs text-slate-500">Hired: {{ $teacher->hire_date?->format('M d, Y') ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 font-medium">{{ $teacher->employee_id }}</td>
                            <td class="py-4 px-6">
                                <div class="text-sm text-slate-900">{{ $teacher->program ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-500">{{ $teacher->college ?? 'N/A' }}</div>
                            </td>
                            <td class="py-4 px-6 text-sm text-slate-700">{{ $teacher->specialization ?? 'N/A' }}</td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                    {{ $teacher->courses_count }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                    {{ $teacher->announcements_count }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-sm text-slate-600">{{ $teacher->user?->email ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-10 px-6 text-center text-sm text-slate-500">
                                @if($search)
                                    No teachers found matching "{{ $search }}"
                                @else
                                    No teachers found
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $teachers->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
