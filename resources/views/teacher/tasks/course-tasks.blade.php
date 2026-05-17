@php
    $activeTab = $activeTab ?? 'assignments';
    $courseId = $course->id ?? 0;
@endphp

<div class="rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
    <div class="px-6 py-6 border-b border-slate-200">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                    <i data-lucide="clipboard-list" class="h-5 w-5 text-[#0b2d6b]"></i>
                    <span>Tasks</span>
                </div>
                <div class="mt-1 text-sm text-slate-500">Manage assignments, exams, and quizzes for this course.</div>
            </div>
        </div>

        <!-- Task Type Tabs -->
        <div class="mt-4 flex border-b border-slate-200">
            <button type="button" data-sub-tab-btn="assignments" class="sub-tab-btn px-4 py-3 text-sm font-medium border-b-2 transition-colors border-[#0b2d6b] text-[#0b2d6b]">
                <span class="flex items-center gap-2">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    Assignments
                    <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $assignments->count() }}</span>
                </span>
            </button>
            <button type="button" data-sub-tab-btn="exams" class="sub-tab-btn px-4 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">
                <span class="flex items-center gap-2">
                    <i data-lucide="clipboard-check" class="h-4 w-4"></i>
                    Exams
                    <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $exams->count() }}</span>
                </span>
            </button>
            <button type="button" data-sub-tab-btn="quizzes" class="sub-tab-btn px-4 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">
                <span class="flex items-center gap-2">
                    <i data-lucide="help-circle" class="h-4 w-4"></i>
                    Quizzes
                    <span class="ml-1 px-2 py-0.5 text-xs bg-slate-100 text-slate-600 rounded-full">{{ $quizzes->count() }}</span>
                </span>
            </button>
        </div>
    </div>

<!-- Task Content Panels -->
<div data-sub-tab-panel="assignments" class="mt-6">
    <div class="flex items-center justify-between gap-2 mb-6 px-1">
        <div class="text-sm font-medium text-slate-700 py-1">
            {{ $assignments->count() }} assignment{{ $assignments->count() != 1 ? 's' : '' }}
        </div>
        <a href="{{ route('teacher.assignments.create', $course) }}" class="inline-flex items-center justify-center h-10 px-5 rounded-lg bg-[#0b2d6b] text-white text-sm font-medium hover:bg-[#0a275c] shadow-sm">
            <i data-lucide="plus" class="h-4 w-4 mr-2"></i> Create Assignment
        </a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Title</th>
                    <th class="py-3 px-4">Due Date</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Submissions</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assignments ?? collect() as $assignment)
                <tr class="text-slate-700">
                    <td class="py-3 px-4 font-medium text-slate-900">{{ $assignment->title }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $assignment->due_date?->format('M d, Y') ?? 'No due date' }}</td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $assignment->due_date && $assignment->due_date->isPast() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                            {{ $assignment->due_date && $assignment->due_date->isPast() ? 'Overdue' : 'Active' }}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-slate-600">{{ $assignment->submissions_count ?? 0 }}</td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.assignments.show', [$course, $assignment]) }}" class="text-[#0b2d6b] hover:text-[#0a275c] text-sm">View</a>
                            <a href="{{ route('teacher.assignments.edit', [$course, $assignment]) }}" class="text-slate-500 hover:text-slate-700 text-sm">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-500">No assignments created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div data-sub-tab-panel="exams" class="mt-6 hidden">
    <div class="flex items-center justify-between gap-2 mb-4">
        <div class="text-sm font-medium text-slate-700">
            {{ $exams->count() }} exam{{ $exams->count() != 1 ? 's' : '' }}
        </div>
        <a href="{{ route('teacher.exams.create', $course) }}" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#0b2d6b] text-white text-xs font-medium hover:bg-[#0a275c]">
            <i data-lucide="plus" class="h-4 w-4 mr-1"></i> Schedule Exam
        </a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Title</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4">Duration</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($exams ?? collect() as $exam)
                <tr class="text-slate-700">
                    <td class="py-3 px-4 font-medium text-slate-900">{{ $exam->title }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $exam->exam_date?->format('M d, Y') ?? 'Not scheduled' }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $exam->duration ?? 'N/A' }} min</td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Scheduled
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.exams.show', $exam) }}" class="text-[#0b2d6b] hover:text-[#0a275c] text-sm">View</a>
                            <a href="{{ route('teacher.exams.edit', $exam) }}" class="text-slate-500 hover:text-slate-700 text-sm">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-500">No exams scheduled yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div data-sub-tab-panel="quizzes" class="mt-6 hidden">
    <div class="flex items-center justify-between gap-2 mb-4">
        <div class="text-sm font-medium text-slate-700">
            {{ $quizzes->count() }} quiz{{ $quizzes->count() != 1 ? 'zes' : '' }}
        </div>
        <a href="{{ route('teacher.quizzes.create', $course) }}" class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#0b2d6b] text-white text-xs font-medium hover:bg-[#0a275c]">
            <i data-lucide="plus" class="h-4 w-4 mr-1"></i> Create Quiz
        </a>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                    <th class="py-3 px-4">Title</th>
                    <th class="py-3 px-4">Questions</th>
                    <th class="py-3 px-4">Duration</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($quizzes ?? collect() as $quiz)
                <tr class="text-slate-700">
                    <td class="py-3 px-4 font-medium text-slate-900">{{ $quiz->title }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $quiz->questions_count ?? 0 }}</td>
                    <td class="py-3 px-4 text-slate-600">{{ $quiz->duration ?? 'N/A' }} min</td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                            Active
                        </span>
                    </td>
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('teacher.quizzes.show', $quiz) }}" class="text-[#0b2d6b] hover:text-[#0a275c] text-sm">View</a>
                            <a href="{{ route('teacher.quizzes.edit', $quiz) }}" class="text-slate-500 hover:text-slate-700 text-sm">Edit</a>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-500">No quizzes created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    (function() {
        const subTabsRoot = document.currentScript.parentElement;
        const subButtons = subTabsRoot.querySelectorAll('[data-sub-tab-btn]');
        const subPanels = subTabsRoot.querySelectorAll('[data-sub-tab-panel]');

        function activateSubTab(tab) {
            subButtons.forEach((btn) => {
                const active = btn.getAttribute('data-sub-tab-btn') === tab;
                btn.classList.toggle('text-[#0b2d6b]', active);
                btn.classList.toggle('border-[#0b2d6b]', active);
                btn.classList.toggle('text-slate-500', !active);
                btn.classList.toggle('border-transparent', !active);
            });
            subPanels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.getAttribute('data-sub-tab-panel') !== tab);
            });
        }

        subButtons.forEach((btn) => {
            btn.addEventListener('click', function() {
                const target = this.getAttribute('data-sub-tab-btn');
                activateSubTab(target);
            });
        });

        // Activate first sub-tab by default
        activateSubTab('assignments');
    })();
</script>
