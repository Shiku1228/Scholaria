@extends('layouts.teacher')

@section('content')
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="pencil" class="h-6 w-6 text-[#0b2d6b]"></i>
                <span>Edit Assignment</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">
                {{ $course->course_number ?? $course->title ?? ('Course #' . $course->id) }}
            </div>
        </div>
        <a href="{{ route('teacher.assignments.show', [$course, $assignment]) }}"
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

    @if ($hasSubmissions)
        <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 mb-5">
            <i data-lucide="alert-triangle" style="width:16px;height:16px;" class="flex-shrink-0 mt-0.5"></i>
            <div>
                <span class="font-semibold">Students have already submitted this assignment.</span>
                Questions and choices are locked to protect existing submissions.
                You can still update the title, description, due date, and category.
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('teacher.assignments.update', [$course, $assignment]) }}" id="assignment-form">
        @csrf
        @method('PUT')

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
                    <input type="text" name="title" value="{{ old('title', $assignment->title) }}"
                        placeholder="Enter assignment title"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description / Instructions</label>
                    <textarea name="description" rows="4"
                        placeholder="Describe the assignment, instructions, requirements…"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none">{{ old('description', $assignment->description) }}</textarea>
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
                    @php
                        $dueDateValue = old('due_date');
                        if (!$dueDateValue && $assignment->due_date) {
                            try { $dueDateValue = \Carbon\Carbon::parse($assignment->due_date)->format('Y-m-d\TH:i'); } catch (\Throwable) {}
                        }
                    @endphp
                    <input type="datetime-local" name="due_date" value="{{ $dueDateValue }}"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        <span class="flex items-center gap-2">
                            <i data-lucide="tag" class="h-4 w-4 text-blue-400"></i>
                            Category
                        </span>
                    </label>
                    @php $currentType = old('type', $assignment->type ?? 'assignment'); @endphp
                    <select name="type"
                        class="w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm">
                        <option value="assignment" {{ $currentType === 'assignment' ? 'selected' : '' }}>Assignment</option>
                        <option value="quiz"       {{ $currentType === 'quiz'       ? 'selected' : '' }}>Quiz</option>
                        <option value="exam"       {{ $currentType === 'exam'       ? 'selected' : '' }}>Exam</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── Assignment Format ────────────────────────────────────────── --}}
        @php $currentFormat = old('assignment_format', $assignment->assignment_format ?? 'essay'); @endphp
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                    <i data-lucide="layout-list" class="h-4 w-4 text-slate-500"></i>
                    Assignment Format
                </div>
            </div>
            <div class="p-5">
                @if ($hasSubmissions)
                    <input type="hidden" name="assignment_format" value="{{ $currentFormat }}">
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <div class="h-9 w-9 rounded-lg {{ $currentFormat === 'multiple_choice' ? 'bg-emerald-100' : 'bg-blue-100' }} flex items-center justify-center">
                            <i data-lucide="{{ $currentFormat === 'multiple_choice' ? 'list-checks' : 'pencil-line' }}"
                               class="h-5 w-5 {{ $currentFormat === 'multiple_choice' ? 'text-emerald-600' : 'text-blue-600' }}"></i>
                        </div>
                        <span class="font-medium">{{ $currentFormat === 'multiple_choice' ? 'Multiple Choice' : 'Essay Assignment' }}</span>
                        <span class="text-slate-400 text-xs">(locked — submissions exist)</span>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="format-cards">
                        <label class="format-card relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all
                            {{ $currentFormat === 'essay' ? 'border-[#0b2d6b] bg-[#eaf0fb]' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <input type="radio" name="assignment_format" value="essay" class="sr-only format-radio"
                                {{ $currentFormat === 'essay' ? 'checked' : '' }}>
                            <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="pencil-line" class="h-5 w-5 text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 text-sm">Essay Assignment</div>
                                <div class="text-xs text-slate-500 mt-0.5">Students write open-ended answers. Teacher grades manually.</div>
                            </div>
                        </label>
                        <label class="format-card relative flex items-start gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all
                            {{ $currentFormat === 'multiple_choice' ? 'border-[#0b2d6b] bg-[#eaf0fb]' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <input type="radio" name="assignment_format" value="multiple_choice" class="sr-only format-radio"
                                {{ $currentFormat === 'multiple_choice' ? 'checked' : '' }}>
                            <div class="h-10 w-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="list-checks" class="h-5 w-5 text-emerald-600"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-slate-900 text-sm">Multiple Choice</div>
                                <div class="text-xs text-slate-500 mt-0.5">Students pick from choices. Score is calculated automatically.</div>
                            </div>
                        </label>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Questions ────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-5">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <i data-lucide="help-circle" class="h-4 w-4 text-slate-500"></i>
                        Questions
                    </div>
                    @if (!$hasSubmissions)
                        <span class="text-xs text-slate-500">Total:
                            <span id="total-points" class="font-semibold text-[#0b2d6b]">0</span> pts
                        </span>
                    @endif
                </div>
            </div>
            <div class="p-5">
                @if ($hasSubmissions)
                    {{-- Read-only display with hidden inputs so form submits correctly --}}
                    @forelse ($questions as $qi => $q)
                        <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden mb-4">
                            <div class="px-5 py-3 bg-white border-b border-slate-200 flex items-center gap-3">
                                <span class="text-sm font-semibold text-[#0b2d6b]">Question {{ $qi + 1 }}</span>
                                <span class="text-xs text-slate-400">{{ $q->points }} pt{{ $q->points !== 1 ? 's' : '' }}</span>
                            </div>
                            <div class="p-5 space-y-3">
                                <input type="hidden" name="questions[{{ $qi }}][question_text]" value="{{ $q->question_text }}">
                                <input type="hidden" name="questions[{{ $qi }}][points]" value="{{ $q->points }}">
                                <p class="text-sm text-slate-800">{{ $q->question_text }}</p>
                                @if ($q->choices->isNotEmpty())
                                    <div class="space-y-1.5 mt-3">
                                        @foreach ($q->choices as $ci => $c)
                                            <input type="hidden" name="questions[{{ $qi }}][choices][{{ $ci }}][choice_text]" value="{{ $c->choice_text }}">
                                            @if ($c->is_correct)
                                                <input type="hidden" name="questions[{{ $qi }}][correct_choice]" value="{{ $ci }}">
                                            @endif
                                            <div class="flex items-center gap-2 text-sm {{ $c->is_correct ? 'text-emerald-700 font-medium' : 'text-slate-600' }}">
                                                <span class="h-5 w-5 rounded-full border-2 flex items-center justify-center text-xs
                                                    {{ $c->is_correct ? 'border-emerald-500 bg-emerald-50' : 'border-slate-300 bg-white' }}">
                                                    @if ($c->is_correct) ✓ @endif
                                                </span>
                                                {{ $c->choice_text }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No questions were added to this assignment.</p>
                    @endforelse
                @else
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
                @endif
            </div>
        </div>

        {{-- ── Actions ──────────────────────────────────────────────────── --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('teacher.assignments.show', [$course, $assignment]) }}"
                class="inline-flex items-center justify-center h-11 px-6 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="x" class="h-4 w-4 mr-2"></i>Cancel
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] shadow-sm">
                <i data-lucide="save" class="h-4 w-4 mr-2"></i>Save Changes
            </button>
        </div>
    </form>

@php
$questionsForJs = $questions->map(function ($q) {
    return [
        'question_text' => $q->question_text,
        'points'        => $q->points,
        'choices'       => $q->choices->map(function ($c) {
            return ['choice_text' => $c->choice_text, 'is_correct' => (bool) $c->is_correct];
        })->values()->all(),
    ];
})->values()->all();
@endphp
@if (!$hasSubmissions)
<script>
(function () {
    const container    = document.getElementById('questions-container');
    const addQBtn      = document.getElementById('add-question-btn');
    const totalEl      = document.getElementById('total-points');
    const qMinErr      = document.getElementById('question-min-err');
    const form         = document.getElementById('assignment-form');
    const formatCards  = document.querySelectorAll('.format-card');
    const formatRadios = document.querySelectorAll('.format-radio');

    function syncFormatCards() {
        formatCards.forEach(function (card) {
            const radio = card.querySelector('.format-radio');
            if (radio && radio.checked) {
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
                    addChoiceTo(list, block); addChoiceTo(list, block); reindex();
                }
            } else {
                wrap.classList.add('hidden');
            }
        });
    }
    formatRadios.forEach(function (r) { r.addEventListener('change', syncFormatCards); });
    formatCards.forEach(function (c) {
        c.addEventListener('click', function () {
            const r = c.querySelector('.format-radio'); if (r) { r.checked = true; syncFormatCards(); }
        });
    });

    function getFormat() {
        const checked = document.querySelector('.format-radio:checked');
        return checked ? checked.value : 'essay';
    }

    function updateTotal() {
        let sum = 0;
        container.querySelectorAll('.q-points').forEach(function (el) { sum += Math.max(0, parseInt(el.value) || 0); });
        if (totalEl) totalEl.textContent = sum;
    }

    function reindex() {
        const blocks = [...container.querySelectorAll('.question-block')];
        blocks.forEach(function (block, qi) {
            block.querySelector('.q-label').textContent = 'Question ' + (qi + 1);
            const qText = block.querySelector('.q-text'); const qPts = block.querySelector('.q-points');
            if (qText) qText.name = 'questions[' + qi + '][question_text]';
            if (qPts)  qPts.name  = 'questions[' + qi + '][points]';
            const choices = [...block.querySelectorAll('.choice-item')];
            let checkedCI = -1;
            choices.forEach(function (c, ci) { const r = c.querySelector('.c-radio'); if (r && r.checked) checkedCI = ci; });
            choices.forEach(function (c, ci) {
                const ct = c.querySelector('.c-text'); const cr = c.querySelector('.c-radio');
                if (ct) ct.name = 'questions[' + qi + '][choices][' + ci + '][choice_text]';
                if (cr) { cr.name = 'questions[' + qi + '][correct_choice]'; cr.value = ci; cr.checked = (ci === checkedCI); }
            });
        });
        updateTotal();
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function addChoiceTo(list, block) {
        const ci = list.querySelectorAll('.choice-item').length;
        const div = document.createElement('div');
        div.className = 'choice-item flex items-center gap-2';
        div.innerHTML = '<label class="flex items-center gap-1.5 shrink-0 cursor-pointer" title="Mark as correct">'
            + '<input type="radio" class="c-radio h-4 w-4 accent-[#0b2d6b]" name="q[correct]" value="' + ci + '"></label>'
            + '<input type="text" class="c-text flex-1 rounded-lg border-slate-200 text-sm focus:border-[#0b2d6b] focus:ring-[#0b2d6b] py-2 px-3"'
            + ' placeholder="Choice ' + String.fromCharCode(65 + ci) + '…" required>'
            + '<button type="button" class="remove-choice-btn h-8 w-8 shrink-0 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-300 transition-colors">'
            + '<i data-lucide="x" class="h-4 w-4"></i></button>';
        div.querySelector('.remove-choice-btn').addEventListener('click', function () {
            const pl = div.closest('.choices-list'); const pb = div.closest('.question-block');
            const me = pb ? pb.querySelector('.choice-min-err') : null;
            if (pl.querySelectorAll('.choice-item').length <= 2) { if (me) me.classList.remove('hidden'); return; }
            if (me) me.classList.add('hidden'); div.remove(); reindex();
        });
        list.appendChild(div);
    }

    function addQuestion() {
        const qi  = container.querySelectorAll('.question-block').length;
        const fmt = getFormat();
        const block = document.createElement('div');
        block.className = 'question-block bg-slate-50 border border-slate-200 rounded-xl overflow-hidden';
        block.innerHTML =
            '<div class="flex items-center justify-between px-5 py-3 bg-white border-b border-slate-200">'
            + '<span class="q-label text-sm font-semibold text-[#0b2d6b]">Question ' + (qi + 1) + '</span>'
            + '<button type="button" class="remove-q-btn inline-flex items-center h-7 px-3 rounded-lg border border-slate-200 text-xs font-medium text-slate-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition-colors gap-1.5">'
            + '<i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Remove</button></div>'
            + '<div class="p-5 space-y-4">'
            + '<div><label class="block text-xs font-semibold text-slate-600 mb-1.5">Question Text <span class="text-red-500">*</span></label>'
            + '<textarea class="q-text w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm resize-none"'
            + ' name="questions[' + qi + '][question_text]" rows="2" required></textarea></div>'
            + '<div class="w-40"><label class="block text-xs font-semibold text-slate-600 mb-1.5">Points <span class="text-red-500">*</span></label>'
            + '<input type="number" class="q-points w-full rounded-lg border-slate-200 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] text-sm"'
            + ' name="questions[' + qi + '][points]" value="1" min="1" max="10000" required></div>'
            + '<div class="choices-wrap' + (fmt === 'multiple_choice' ? '' : ' hidden') + '">'
            + '<div class="text-xs font-semibold text-slate-600 mb-2">Answer Choices <span class="font-normal text-slate-400">(click radio to mark correct)</span></div>'
            + '<div class="choices-list space-y-2"></div>'
            + '<div class="choice-min-err hidden mt-1 text-xs text-red-600">At least 2 choices required.</div>'
            + '<div class="correct-sel-err hidden mt-1 text-xs text-red-600">Please select the correct answer.</div>'
            + '<button type="button" class="add-choice-btn mt-3 inline-flex items-center h-8 px-3 rounded-lg border border-dashed border-slate-300 text-xs font-medium text-slate-500 hover:border-[#0b2d6b] hover:text-[#0b2d6b] hover:bg-[#eaf0fb] transition-colors gap-1.5">'
            + '<i data-lucide="plus" class="h-3.5 w-3.5"></i> Add Choice</button></div></div>';

        block.querySelector('.remove-q-btn').addEventListener('click', function () {
            if (container.querySelectorAll('.question-block').length <= 1) { if (qMinErr) qMinErr.classList.remove('hidden'); return; }
            if (qMinErr) qMinErr.classList.add('hidden'); block.remove(); reindex();
        });
        block.querySelector('.add-choice-btn').addEventListener('click', function () {
            addChoiceTo(block.querySelector('.choices-list'), block); reindex();
        });
        block.querySelector('.q-points').addEventListener('input', updateTotal);
        container.appendChild(block);
        if (fmt === 'multiple_choice') {
            const list = block.querySelector('.choices-list'); addChoiceTo(list, block); addChoiceTo(list, block);
        }
        reindex();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            const blocks = [...container.querySelectorAll('.question-block')];
            let hasError = false;
            if (blocks.length === 0) { if (qMinErr) qMinErr.classList.remove('hidden'); e.preventDefault(); return; }
            if (qMinErr) qMinErr.classList.add('hidden');
            if (getFormat() === 'multiple_choice') {
                blocks.forEach(function (block) {
                    const list   = block.querySelector('.choices-list');
                    const cItems = list ? [...list.querySelectorAll('.choice-item')] : [];
                    const minErr = block.querySelector('.choice-min-err');
                    const selErr = block.querySelector('.correct-sel-err');
                    if (cItems.length < 2) { if (minErr) minErr.classList.remove('hidden'); hasError = true; } else { if (minErr) minErr.classList.add('hidden'); }
                    if (!block.querySelector('.c-radio:checked')) { if (selErr) selErr.classList.remove('hidden'); hasError = true; } else { if (selErr) selErr.classList.add('hidden'); }
                });
            }
            if (hasError) { e.preventDefault(); } else { reindex(); }
        });
    }

    // Pre-load existing questions
    const existingQuestions = @json($questionsForJs);

    if (existingQuestions.length > 0) {
        existingQuestions.forEach(function (qData) {
            addQuestion();
            const blocks = container.querySelectorAll('.question-block');
            const block  = blocks[blocks.length - 1];
            if (!block) return;
            const qtxt = block.querySelector('.q-text'); const qpts = block.querySelector('.q-points');
            if (qtxt) qtxt.value = qData.question_text;
            if (qpts) qpts.value = qData.points;
            if (qData.choices && qData.choices.length > 0) {
                const list = block.querySelector('.choices-list');
                list.innerHTML = '';
                qData.choices.forEach(function (cData) {
                    addChoiceTo(list, block);
                    const items = list.querySelectorAll('.choice-item');
                    const item  = items[items.length - 1];
                    if (!item) return;
                    const ct = item.querySelector('.c-text');
                    if (ct) ct.value = cData.choice_text;
                    if (cData.is_correct) { const cr = item.querySelector('.c-radio'); if (cr) cr.checked = true; }
                });
            }
        });
        reindex();
    } else {
        addQuestion();
    }

    if (addQBtn) addQBtn.addEventListener('click', addQuestion);
})();
</script>
@endif
@endsection
