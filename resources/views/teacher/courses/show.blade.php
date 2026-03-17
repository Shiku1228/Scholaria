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

    <div class="flex items-start justify-end gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('teacher.courses.index') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</a>
        </div>
    </div>

    @php
        $cover = (string) ($course->cover_image ?? '');
    @endphp
    <div class="mt-6 h-[280px] rounded-3xl overflow-hidden border border-slate-200 bg-cover bg-center relative"
         style="{{ $cover !== '' ? 'background-image:url(' . e(asset('storage/' . ltrim($cover, '/'))) . ');' : '' }}">
        @if ($cover === '')
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-500"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-b from-slate-900/45 via-slate-900/15 to-slate-900/35"></div>
        @endif
        <div class="absolute inset-x-0 bottom-0 p-6">
            <div class="text-5xl font-extrabold tracking-tight text-white drop-shadow-sm">{{ $course->title ?: $course->course_number ?: ('Course #' . $course->id) }}</div>
            <div class="text-3xl text-white/90 mt-1 drop-shadow-sm">{{ $course->course_number ?? '' }}</div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100" data-tabs-root>
        <div class="p-4 sm:p-5 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-6 text-sm font-semibold">
                <button type="button" data-tab-btn="overview" class="tab-btn text-[#4f46e5] border-b-2 border-[#4f46e5] pb-2">Overview</button>
                <button type="button" data-tab-btn="resources" class="tab-btn text-slate-500 hover:text-slate-700 pb-2 border-b-2 border-transparent">Resources</button>
                <button type="button" data-tab-btn="discussion" class="tab-btn text-slate-500 hover:text-slate-700 pb-2 border-b-2 border-transparent">Discussion</button>
                <button type="button" data-tab-btn="students" class="tab-btn text-slate-500 hover:text-slate-700 pb-2 border-b-2 border-transparent">Students</button>
                <a href="{{ route('teacher.assignments.index', $course) }}" class="text-slate-500 hover:text-slate-700 pb-2 border-b-2 border-transparent">Assignments</a>
                <a href="{{ route('teacher.announcements.index', $course) }}" class="text-slate-500 hover:text-slate-700 pb-2 border-b-2 border-transparent">Announcements</a>
            </div>
        </div>

        <div data-tab-panel="overview" class="p-4 sm:p-6">
            <div class="text-lg font-semibold text-slate-900">Course Overview</div>
            <div class="text-sm text-slate-500">Visible to enrolled students.</div>
            @if (!empty($canEditOverview))
                @if (!empty((string) ($course->overview ?? '')) && !old('overview'))
                    <div id="overviewReadOnly" class="mt-4">
                        <div class="text-base leading-8 text-slate-700 whitespace-pre-line">{{ (string) $course->overview }}</div>
                        <button type="button" id="editOverviewToggle" class="mt-4 inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Edit Overview</button>
                    </div>
                    <form id="overviewEditForm" method="POST" action="{{ route('teacher.courses.overview.update', $course) }}" class="mt-4 space-y-3 hidden">
                        @csrf
                        <textarea name="overview" rows="6" class="w-full rounded-xl border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="Write course overview, key takeaways, and expectations...">{{ old('overview', (string) ($course->overview ?? '')) }}</textarea>
                        @error('overview')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
                        <div class="flex items-center gap-2">
                            <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Save Overview</button>
                            <button type="button" id="cancelOverviewEdit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-slate-700 hover:bg-gray-50">Cancel</button>
                        </div>
                    </form>
                @else
                    <form method="POST" action="{{ route('teacher.courses.overview.update', $course) }}" class="mt-4 space-y-3">
                        @csrf
                        <textarea name="overview" rows="6" class="w-full rounded-xl border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="Write course overview, key takeaways, and expectations...">{{ old('overview', (string) ($course->overview ?? '')) }}</textarea>
                        @error('overview')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
                        <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Save Overview</button>
                    </form>
                @endif
            @else
                <div class="mt-3 text-sm text-red-600">Course overview field is not available yet. Run migrations first.</div>
            @endif
        </div>

        <div data-tab-panel="resources" class="p-4 sm:p-6 hidden">
            <div class="text-sm font-semibold text-slate-900">Resources</div>
            <div class="text-xs text-slate-500">Upload documents (PDF or DOCX only).</div>
            <form method="POST" action="{{ route('teacher.courses.resources.store', $course) }}" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <div class="md:col-span-1">
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Resource title" class="h-10 w-full rounded-xl border-gray-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" required>
                    @error('title')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
                </div>
                <div class="md:col-span-1">
                    <input type="file" name="resource_file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="h-10 w-full rounded-xl border-gray-200 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[#eaf0fb] file:px-3 file:py-2 file:text-[#0b2d6b] file:font-semibold" required>
                    @error('resource_file')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
                </div>
                <div class="md:col-span-1">
                    <button type="submit" class="inline-flex items-center justify-center h-10 w-full px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Upload Resource</button>
                </div>
            </form>

            <div class="mt-4 space-y-3">
                @forelse ($resources as $resource)
                    <a href="{{ asset('storage/' . ltrim((string) $resource->file_path, '/')) }}" target="_blank" class="flex items-center justify-between rounded-xl border border-gray-200 px-4 py-3 hover:bg-slate-50">
                        <div>
                            <div class="text-sm font-semibold text-slate-900">{{ $resource->title }}</div>
                            <div class="text-xs text-slate-500">{{ $resource->file_name }} • {{ number_format(((int) $resource->file_size) / 1024, 0) }} KB</div>
                        </div>
                        <span class="text-xs font-semibold text-[#4f46e5]">Download</span>
                    </a>
                @empty
                    <div class="text-sm text-slate-500">No resources uploaded yet.</div>
                @endforelse
            </div>
        </div>

        <div data-tab-panel="discussion" class="p-4 sm:p-6 hidden">
            <form method="POST" action="{{ route('teacher.courses.discussions.store', $course) }}" class="mt-4">
                @csrf
                <textarea name="content" rows="2" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="Ask a question or post an update...">{{ old('content') }}</textarea>
                @error('content')<div class="mt-1 text-xs text-red-600">{{ $message }}</div>@enderror
                <button type="submit" class="mt-3 inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Post Discussion
                </button>
            </form>

            <div class="mt-5 space-y-3">
                @forelse ($discussions as $post)
                    @php
                        $initial = strtoupper(substr((string) ($post->user->name ?? 'U'), 0, 1));
                    @endphp
                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-9 w-9 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-sm font-bold">{{ $initial }}</div>
                                <div>
                                    <div class="text-lg font-semibold text-slate-900">{{ $post->user->name ?? 'User' }}</div>
                                    <div class="text-xs text-slate-500">{{ $post->created_at?->diffForHumans() ?? '' }}</div>
                                </div>
                            </div>
                            <div class="relative">
                                <button type="button" class="discussion-menu-toggle h-8 w-8 rounded-full hover:bg-slate-100 text-slate-500" data-menu-id="menu-post-{{ $post->id }}">⋮</button>
                                <div id="menu-post-{{ $post->id }}" class="hidden absolute right-0 mt-1 w-28 rounded-lg border border-slate-200 bg-white shadow-lg z-20">
                                    @if ((int) ($post->user_id ?? 0) === (int) auth()->id())
                                        <button type="button" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 edit-toggle" data-edit-id="edit-post-{{ $post->id }}">Edit</button>
                                    @endif
                                    <form method="POST" action="{{ route('teacher.courses.discussions.destroy', [$course, $post]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 text-base text-slate-800 whitespace-pre-line">{{ $post->content }}</div>
                        @if ((int) ($post->user_id ?? 0) === (int) auth()->id())
                            <form id="edit-post-{{ $post->id }}" method="POST" action="{{ route('teacher.courses.discussions.update', [$course, $post]) }}" class="hidden mt-3">
                                @csrf
                                @method('PATCH')
                                <textarea name="content" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">{{ $post->content }}</textarea>
                                <div class="mt-2 flex items-center gap-2">
                                    <button type="submit" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold">Save</button>
                                    <button type="button" class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 edit-toggle" data-edit-id="edit-post-{{ $post->id }}">Cancel</button>
                                </div>
                            </form>
                        @endif
                        <div class="mt-3 pt-2 border-t border-slate-200 flex items-center gap-4 text-xs text-slate-600">
                            <button type="button" class="reply-toggle font-semibold text-[#0b2d6b]" data-reply-form-id="reply-form-{{ $post->id }}">Reply</button>
                            <span>{{ $post->replies->count() }} Replies</span>
                        </div>

                        <form id="reply-form-{{ $post->id }}" method="POST" action="{{ route('teacher.courses.discussions.store', $course) }}" class="mt-3 hidden">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $post->id }}">
                            <textarea name="content" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]" placeholder="Write a reply..."></textarea>
                            <button type="submit" class="mt-2 inline-flex items-center justify-center h-9 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold hover:bg-[#0a275c]">Post Reply</button>
                        </form>

                        @if ($post->replies->isNotEmpty())
                            <div class="mt-4 space-y-2">
                                @foreach ($post->replies as $reply)
                                    <div class="ml-5 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="text-sm font-semibold text-slate-900">{{ $reply->user->name ?? 'User' }}</div>
                                            <div class="flex items-center gap-2">
                                                <div class="text-xs text-slate-500">{{ $reply->created_at?->diffForHumans() ?? '' }}</div>
                                                <div class="relative">
                                                    <button type="button" class="discussion-menu-toggle h-7 w-7 rounded-full hover:bg-slate-200 text-slate-500" data-menu-id="menu-reply-{{ $reply->id }}">⋮</button>
                                                    <div id="menu-reply-{{ $reply->id }}" class="hidden absolute right-0 mt-1 w-28 rounded-lg border border-slate-200 bg-white shadow-lg z-20">
                                                        @if ((int) ($reply->user_id ?? 0) === (int) auth()->id())
                                                            <button type="button" class="w-full text-left px-3 py-2 text-xs text-slate-700 hover:bg-slate-50 edit-toggle" data-edit-id="edit-reply-{{ $reply->id }}">Edit</button>
                                                        @endif
                                                        <form method="POST" action="{{ route('teacher.courses.discussions.destroy', [$course, $reply]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-1 text-sm text-slate-700 whitespace-pre-line">{{ $reply->content }}</div>
                                        @if ((int) ($reply->user_id ?? 0) === (int) auth()->id())
                                            <form id="edit-reply-{{ $reply->id }}" method="POST" action="{{ route('teacher.courses.discussions.update', [$course, $reply]) }}" class="hidden mt-2">
                                                @csrf
                                                @method('PATCH')
                                                <textarea name="content" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">{{ $reply->content }}</textarea>
                                                <div class="mt-2 flex items-center gap-2">
                                                    <button type="submit" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-[#0b2d6b] text-white text-xs font-semibold">Save</button>
                                                    <button type="button" class="inline-flex items-center justify-center h-8 px-3 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 edit-toggle" data-edit-id="edit-reply-{{ $reply->id }}">Cancel</button>
                                                </div>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-sm text-slate-500">No discussion posts yet.</div>
                @endforelse
            </div>
        </div>

        <div data-tab-panel="students" class="p-4 sm:p-6 hidden">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <div class="text-lg font-semibold text-slate-900">Enrolled Students</div>
                    <div class="text-sm text-slate-500">Students currently enrolled in this course.</div>
                </div>
                <span class="inline-flex items-center h-8 px-3 rounded-lg bg-[#eaf0fb] text-[#0b2d6b] text-xs font-semibold">
                    {{ $students->count() }} students
                </span>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                            <th class="py-3 px-4">Student</th>
                            <th class="py-3 px-4">Email</th>
                            <th class="py-3 px-4">Student #</th>
                            <th class="py-3 px-4">Enrolled Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $enrollment)
                            @php
                                $student = $enrollment->student;
                            @endphp
                            <tr class="text-slate-700">
                                <td class="py-3 px-4 font-medium text-slate-900">{{ $student->name ?? 'Student' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $student->email ?? '--' }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ $student->student_number ?? '--' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ $enrollment->enrolled_at ? \Illuminate\Support\Carbon::parse($enrollment->enrolled_at)->format('Y-m-d') : '--' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-10 px-4 text-center text-sm text-slate-500">No students enrolled yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const root = document.querySelector('[data-tabs-root]');
            if (!root) return;
            const buttons = root.querySelectorAll('[data-tab-btn]');
            const panels = root.querySelectorAll('[data-tab-panel]');

            function activate(tab) {
                buttons.forEach((btn) => {
                    const active = btn.getAttribute('data-tab-btn') === tab;
                    btn.classList.toggle('text-[#4f46e5]', active);
                    btn.classList.toggle('border-[#4f46e5]', active);
                    btn.classList.toggle('text-slate-500', !active);
                    btn.classList.toggle('border-transparent', !active);
                });
                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.getAttribute('data-tab-panel') !== tab);
                });
            }

            buttons.forEach((btn) => {
                btn.addEventListener('click', function () {
                    const target = this.getAttribute('data-tab-btn');
                    activate(target);
                    if (history && history.replaceState) {
                        history.replaceState(null, '', '#' + target);
                    }
                });
            });

            const initial = (location.hash || '').replace('#', '');
            const allowed = ['overview', 'resources', 'discussion', 'students'];
            activate(allowed.includes(initial) ? initial : 'overview');

            const editBtn = document.getElementById('editOverviewToggle');
            const cancelBtn = document.getElementById('cancelOverviewEdit');
            const readOnly = document.getElementById('overviewReadOnly');
            const editForm = document.getElementById('overviewEditForm');
            if (editBtn && cancelBtn && readOnly && editForm) {
                editBtn.addEventListener('click', function () {
                    readOnly.classList.add('hidden');
                    editForm.classList.remove('hidden');
                });
                cancelBtn.addEventListener('click', function () {
                    editForm.classList.add('hidden');
                    readOnly.classList.remove('hidden');
                });
            }

            document.querySelectorAll('.reply-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-reply-form-id');
                    const form = document.getElementById(id);
                    if (!form) return;
                    form.classList.toggle('hidden');
                });
            });

            document.querySelectorAll('.discussion-menu-toggle').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const id = this.getAttribute('data-menu-id');
                    const menu = document.getElementById(id);
                    if (!menu) return;
                    menu.classList.toggle('hidden');
                });
            });

            document.querySelectorAll('.edit-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = this.getAttribute('data-edit-id');
                    const form = document.getElementById(id);
                    if (!form) return;
                    form.classList.toggle('hidden');
                });
            });

            document.addEventListener('click', function () {
                document.querySelectorAll('[id^="menu-post-"],[id^="menu-reply-"]').forEach(function (menu) {
                    menu.classList.add('hidden');
                });
            });
        })();
    </script>
@endsection
