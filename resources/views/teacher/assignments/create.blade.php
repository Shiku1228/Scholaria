@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="plus-circle" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Create Assignment</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">
                {{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}
            </div>
        </div>
        <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}"
            class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mb-5">
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

    <form method="POST" action="{{ route('teacher.assignments.store', $course) }}" id="assignment-form">
        @csrf

        {{-- ── Assignment Details ──────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="file-text" class="h-4 w-4 text-slate-500"></i>
                    Assignment Details
                </div>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Title <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="title" value="{{ old('title') }}"
                        placeholder="Enter assignment title"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" required />
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Description / Instructions
                    </label>
                    <textarea name="description" rows="4"
                        placeholder="Describe the assignment, instructions, requirements…"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Settings ─────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="settings-2" class="h-4 w-4 text-slate-500"></i>
                    Settings
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        <span class="flex items-center gap-2">
                            <i data-lucide="calendar-clock" class="h-4 w-4 text-amber-500"></i>
                            Due Date &amp; Time
                        </span>
                    </label>
                    <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        <span class="flex items-center gap-2">
                            <i data-lucide="tag" class="h-4 w-4 text-blue-400"></i>
                            Category
                        </span>
                    </label>
                    <select name="type"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                        <option value="assignment" {{ old('type', 'assignment') === 'assignment' ? 'selected' : '' }}>Assignment</option>
                        <option value="quiz"       {{ old('type') === 'quiz'  ? 'selected' : '' }}>Quiz</option>
                        <option value="exam"       {{ old('type') === 'exam'  ? 'selected' : '' }}>Exam</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── Assignment Format ────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="layout-list" class="h-4 w-4 text-slate-500"></i>
                    Assignment Format <span class="text-red-500">*</span>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="format-cards">
                    {{-- Essay --}}
                    <label class="format-card relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all
                        {{ old('assignment_format', 'essay') === 'essay' ? 'border-[#0b2d6b] bg-[#eaf0fb]' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <input type="radio" name="assignment_format" value="essay" class="sr-only format-radio"
                            {{ old('assignment_format', 'essay') === 'essay' ? 'checked' : '' }}>
                        <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="pencil-line" class="h-5 w-5 text-blue-600"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 text-sm">Essay Assignment</div>
                            <div class="text-xs text-slate-500 mt-0.5">Students write open-ended answers. Teacher grades manually.</div>
                        </div>
                    </label>
                    {{-- Multiple Choice --}}
                    <label class="format-card relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all
                        {{ old('assignment_format') === 'multiple_choice' ? 'border-[#0b2d6b] bg-[#eaf0fb]' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                        <input type="radio" name="assignment_format" value="multiple_choice" class="sr-only format-radio"
                            {{ old('assignment_format') === 'multiple_choice' ? 'checked' : '' }}>
                        <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="list-checks" class="h-5 w-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-slate-900 text-sm">Multiple Choice</div>
                            <div class="text-xs text-slate-500 mt-0.5">Students pick from choices. Score is calculated automatically.</div>
                        </div>
                    </label>
                </div>
                @error('assignment_format')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- ── Questions ────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <i data-lucide="help-circle" class="h-4 w-4 text-slate-500"></i>
                        Questions <span class="text-red-500">*</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500">Total:
                            <span id="total-points" class="font-semibold text-[#0b2d6b]">0</span> pts
                        </span>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <div id="questions-container" class="space-y-4"></div>

                <div id="question-min-err" class="hidden mt-2 text-xs text-red-600 flex items-center gap-1">
                    <i data-lucide="alert-circle" style="width:13px;height:13px;"></i>
                    At least one question is required.
                </div>

                <button type="button" id="add-question-btn"
                    class="mt-4 inline-flex items-center h-10 px-4 rounded-xl border-2 border-dashed border-slate-300 text-sm font-medium text-slate-600 hover:border-[#0b2d6b] hover:text-[#0b2d6b] hover:bg-[#eaf0fb] transition-colors gap-2">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Question
                </button>
            </div>
        </div>

        {{-- ── Actions ──────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('teacher.courses.show', ['course' => $course, 'tab' => 'tasks']) }}"
                class="inline-flex items-center justify-center h-11 px-6 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="x" class="h-4 w-4 mr-2"></i>Cancel
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] shadow-sm">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>Create Assignment
            </button>
        </div>
    </form>

<script>
(function () {
    const container  = document.getElementById('questions-container');
    const addQBtn    = document.getElementById('add-question-btn');
    const totalEl    = document.getElementById('total-points');
    const qMinErr    = document.getElementById('question-min-err');
    const form       = document.getElementById('assignment-form');
    const formatCards = document.querySelectorAll('.format-card');
    const formatRadios = document.querySelectorAll('.format-radio');

    // ── Format card styling ───────────────────────────────────────────────
    function syncFormatCards() {
        formatCards.forEach(function (card) {
            const radio = card.querySelector('.format-radio');
            if (radio.checked) {
                card.classList.add('border-[#0b2d6b]', 'bg-[#eaf0fb]');
                card.classList.remove('border-slate-200', 'bg-white');
            } else {
                card.classList.remove('border-[#0b2d6b]', 'bg-[#eaf0fb]');
                card.classList.add('border-slate-200', 'bg-white');
            }
        });
        const fmt = getFormat();
        container.querySelectorAll('.choices-wrap').forEach(function (wrap) {
            if (fmt === 'multiple_choice') {
                wrap.classList.remove('hidden');
                const list = wrap.querySelector('.choices-list');
                if (!list.querySelector('.choice-item')) {
                    const block = wrap.closest('.question-block');
                    addChoiceTo(list, block);
                    addChoiceTo(list, block);
                    reindex();
                }
            } else {
                wrap.classList.add('hidden');
            }
        });
    }

    formatRadios.forEach(function (r) {
        r.addEventListener('change', syncFormatCards);
    });
    formatCards.forEach(function (card) {
        card.addEventListener('click', function () {
            const radio = card.querySelector('.format-radio');
            radio.checked = true;
            syncFormatCards();
        });
    });

    // ── Get current format ────────────────────────────────────────────────
    function getFormat() {
        const checked = document.querySelector('.format-radio:checked');
        return checked ? checked.value : 'essay';
    }

    // ── Update total points ───────────────────────────────────────────────
    function updateTotal() {
        let sum = 0;
        container.querySelectorAll('.q-points').forEach(function (el) {
            sum += Math.max(0, parseInt(el.value) || 0);
        });
        if (totalEl) totalEl.textContent = sum;
    }

    // ── Reindex all names ─────────────────────────────────────────────────
    function reindex() {
        const blocks = [...container.querySelectorAll('.question-block')];
        blocks.forEach(function (block, qi) {
            block.querySelector('.q-label').textContent = 'Question ' + (qi + 1);

            const qText   = block.querySelector('.q-text');
            const qPoints = block.querySelector('.q-points');
            if (qText)   qText.name   = 'questions[' + qi + '][question_text]';
            if (qPoints) qPoints.name = 'questions[' + qi + '][points]';

            const choices  = [...block.querySelectorAll('.choice-item')];
            let checkedCI  = -1;
            choices.forEach(function (c, ci) {
                const r = c.querySelector('.c-radio');
                if (r && r.checked) checkedCI = ci;
            });
            choices.forEach(function (c, ci) {
                const cText  = c.querySelector('.c-text');
                const cRadio = c.querySelector('.c-radio');
                if (cText)  cText.name  = 'questions[' + qi + '][choices][' + ci + '][choice_text]';
                if (cRadio) {
                    cRadio.name    = 'questions[' + qi + '][correct_choice]';
                    cRadio.value   = ci;
                    cRadio.checked = (ci === checkedCI);
                }
            });
        });
        updateTotal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // ── Add choice to a block ─────────────────────────────────────────────
    function addChoiceTo(list, block) {
        const ci  = list.querySelectorAll('.choice-item').length;
        const qi  = 0; // will be fixed by reindex()
        const div = document.createElement('div');
        div.className = 'choice-item flex items-center gap-2';
        div.innerHTML =
            '<label class="flex items-center gap-1.5 shrink-0 text-xs text-slate-500 cursor-pointer" title="Mark as correct answer">'
            + '<input type="radio" class="c-radio h-4 w-4 text-[#0b2d6b] accent-[#0b2d6b]"'
            + ' name="questions[' + qi + '][correct_choice]" value="' + ci + '">'
            + '<span class="sr-only">Correct</span>'
            + '</label>'
            + '<input type="text" class="c-text flex-1 rounded-lg border-slate-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b] py-2 px-3"'
            + ' name="questions[' + qi + '][choices][' + ci + '][choice_text]"'
            + ' placeholder="Choice ' + String.fromCharCode(65 + ci) + '…" required>'
            + '<button type="button" class="remove-choice-btn h-8 w-8 shrink-0 flex items-center justify-center rounded-lg border border-slate-200'
            + ' text-slate-400 hover:text-red-500 hover:border-red-300 transition-colors">'
            + '<i data-lucide="x" class="h-4 w-4"></i></button>';

        div.querySelector('.remove-choice-btn').addEventListener('click', function () {
            const parentList = div.closest('.choices-list');
            const parentBlock = div.closest('.question-block');
            const minErr = parentBlock ? parentBlock.querySelector('.choice-min-err') : null;
            if (parentList.querySelectorAll('.choice-item').length <= 2) {
                if (minErr) minErr.classList.remove('hidden');
                return;
            }
            if (minErr) minErr.classList.add('hidden');
            div.remove();
            reindex();
        });

        list.appendChild(div);
    }

    // ── Add question ──────────────────────────────────────────────────────
    function addQuestion() {
        const qi  = container.querySelectorAll('.question-block').length;
        const fmt = getFormat();

        const block = document.createElement('div');
        block.className = 'question-block bg-slate-50 border border-slate-200 rounded-xl overflow-hidden';
        block.innerHTML =
            '<div class="flex items-center justify-between px-5 py-3 bg-white border-b border-slate-200">'
            + '<span class="q-label text-sm font-semibold text-[#0b2d6b]">Question ' + (qi + 1) + '</span>'
            + '<button type="button" class="remove-q-btn inline-flex items-center h-7 px-3 rounded-lg border border-slate-200'
            + ' text-xs font-medium text-slate-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors gap-1.5">'
            + '<i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Remove</button>'
            + '</div>'
            + '<div class="p-5 space-y-4">'
            + '<div>'
            + '<label class="block text-xs font-semibold text-slate-600 mb-1.5">Question Text <span class="text-red-500">*</span></label>'
            + '<textarea class="q-text w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none"'
            + ' name="questions[' + qi + '][question_text]" rows="2" placeholder="Enter question text…" required></textarea>'
            + '</div>'
            + '<div class="w-40">'
            + '<label class="block text-xs font-semibold text-slate-600 mb-1.5">Points <span class="text-red-500">*</span></label>'
            + '<input type="number" class="q-points w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm"'
            + ' name="questions[' + qi + '][points]" value="1" min="1" max="10000" required>'
            + '</div>'
            + '<div class="choices-wrap' + (fmt === 'multiple_choice' ? '' : ' hidden') + '">'
            + '<div class="text-xs font-semibold text-slate-600 mb-2">Answer Choices'
            + ' <span class="font-normal text-slate-400">(click the radio to mark correct)</span></div>'
            + '<div class="choices-list space-y-2"></div>'
            + '<div class="choice-min-err hidden mt-1 text-xs text-red-600">At least 2 choices are required.</div>'
            + '<div class="correct-sel-err hidden mt-1 text-xs text-red-600">Please select the correct answer.</div>'
            + '<button type="button" class="add-choice-btn mt-3 inline-flex items-center h-8 px-3 rounded-lg border border-dashed'
            + ' border-slate-300 text-xs font-medium text-slate-500 hover:border-[#0b2d6b] hover:text-[#0b2d6b] hover:bg-[#eaf0fb] transition-colors gap-1.5">'
            + '<i data-lucide="plus" class="h-3.5 w-3.5"></i> Add Choice</button>'
            + '</div>'
            + '</div>';

        block.querySelector('.remove-q-btn').addEventListener('click', function () {
            if (container.querySelectorAll('.question-block').length <= 1) {
                if (qMinErr) qMinErr.classList.remove('hidden');
                return;
            }
            if (qMinErr) qMinErr.classList.add('hidden');
            block.remove();
            reindex();
        });

        block.querySelector('.add-choice-btn').addEventListener('click', function () {
            const list = block.querySelector('.choices-list');
            addChoiceTo(list, block);
            reindex();
        });

        block.querySelector('.q-points').addEventListener('input', updateTotal);

        container.appendChild(block);

        if (fmt === 'multiple_choice') {
            const list = block.querySelector('.choices-list');
            addChoiceTo(list, block);
            addChoiceTo(list, block);
        }

        reindex();
    }

    // ── Form validation ───────────────────────────────────────────────────
    if (form) {
        form.addEventListener('submit', function (e) {
            const blocks   = [...container.querySelectorAll('.question-block')];
            let hasError   = false;

            if (blocks.length === 0) {
                if (qMinErr) qMinErr.classList.remove('hidden');
                e.preventDefault();
                return;
            }
            if (qMinErr) qMinErr.classList.add('hidden');

            if (getFormat() === 'multiple_choice') {
                blocks.forEach(function (block) {
                    const list    = block.querySelector('.choices-list');
                    const cItems  = list ? [...list.querySelectorAll('.choice-item')] : [];
                    const minErr  = block.querySelector('.choice-min-err');
                    const selErr  = block.querySelector('.correct-sel-err');

                    if (cItems.length < 2) {
                        if (minErr) minErr.classList.remove('hidden');
                        hasError = true;
                    } else {
                        if (minErr) minErr.classList.add('hidden');
                    }

                    const hasCorrect = block.querySelector('.c-radio:checked');
                    if (!hasCorrect) {
                        if (selErr) selErr.classList.remove('hidden');
                        hasError = true;
                    } else {
                        if (selErr) selErr.classList.add('hidden');
                    }
                });
            }

            if (hasError) {
                e.preventDefault();
                container.querySelector('.question-block')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                reindex();
            }
        });
    }

    // ── Boot: add first question, wire add button ─────────────────────────
    addQuestion();
    if (addQBtn) addQBtn.addEventListener('click', addQuestion);

    // Restore old() data after validation error
    @if (old('questions'))
        container.innerHTML = '';
        @foreach (old('questions', []) as $qi => $qOld)
            addQuestion();
            (function () {
                const blocks = container.querySelectorAll('.question-block');
                const block  = blocks[blocks.length - 1];
                if (!block) return;
                const qtxt = block.querySelector('.q-text');
                const qpts = block.querySelector('.q-points');
                if (qtxt) qtxt.value = {{ json_encode($qOld['question_text'] ?? '') }};
                if (qpts) qpts.value = {{ (int) ($qOld['points'] ?? 1) }};
                @if (isset($qOld['choices']))
                    const list = block.querySelector('.choices-list');
                    list.innerHTML = '';
                    @foreach ($qOld['choices'] as $ci => $cOld)
                        addChoiceTo(list, block);
                        (function () {
                            const items = list.querySelectorAll('.choice-item');
                            const item  = items[items.length - 1];
                            if (!item) return;
                            const ct = item.querySelector('.c-text');
                            if (ct) ct.value = {{ json_encode($cOld['choice_text'] ?? '') }};
                            @if ((string) ($qOld['correct_choice'] ?? '') === (string) $ci)
                                const cr = item.querySelector('.c-radio');
                                if (cr) cr.checked = true;
                            @endif
                        })();
                    @endforeach
                @endif
            })();
        @endforeach
        reindex();
    @endif
})();
</script>
@endsection
