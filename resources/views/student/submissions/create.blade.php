@extends('layouts.student')

@section('content')
    <div class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-[#0b2d6b] to-[#0a275c] flex items-center justify-center shadow-lg">
                        <i data-lucide="send" class="h-6 w-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Submit Assignment</h1>
                        <p class="mt-1 text-slate-600">{{ $assignment->title ?? ('Assignment #' . $assignment->id) }}</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('student.assignments.show', $assignment) }}" class="inline-flex items-center justify-center h-11 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>Back
            </a>
        </div>
    </div>

    @if($questions->isNotEmpty())
        {{-- Question-based submission form (essay or multiple choice) --}}
        @php $isMC = ($assignment->assignment_format ?? 'essay') === 'multiple_choice'; @endphp

        <div class="mb-6">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold {{ $isMC ? 'bg-indigo-100 text-indigo-700' : 'bg-blue-100 text-blue-700' }}">
                <i data-lucide="{{ $isMC ? 'list-checks' : 'file-text' }}" class="h-3.5 w-3.5"></i>
                {{ $isMC ? 'Multiple Choice' : 'Essay' }} &middot; {{ $questions->count() }} question{{ $questions->count() !== 1 ? 's' : '' }} &middot; {{ $assignment->max_score ?? $questions->sum('points') }} pts
            </div>
        </div>

        <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" class="space-y-5" id="questions-form">
            @csrf

            @foreach($questions as $qi => $q)
                <div class="bg-white rounded-xl shadow-sm border {{ $errors->has('answers.' . $q->id) ? 'border-red-400' : 'border-slate-200' }} overflow-hidden">
                    {{-- Question Header --}}
                    <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-[#eaf0fb] to-transparent flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="h-8 w-8 rounded-full bg-[#0b2d6b] text-white text-sm font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ $qi + 1 }}</span>
                            <p class="text-sm font-semibold text-slate-800 leading-relaxed pt-1">{{ $q->question_text }}</p>
                        </div>
                        <span class="flex-shrink-0 text-xs font-semibold text-slate-400 bg-slate-100 rounded-full px-3 py-1">{{ $q->points }} pt{{ $q->points !== 1 ? 's' : '' }}</span>
                    </div>

                    {{-- Answer Area --}}
                    <div class="p-6">
                        @if($isMC)
                            {{-- Multiple choice radio buttons --}}
                            <div class="space-y-2">
                                @foreach($q->choices as $c)
                                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-[#eaf0fb] hover:border-[#c9d7f2] px-4 py-3 cursor-pointer transition-colors group has-[:checked]:bg-[#eaf0fb] has-[:checked]:border-[#0b2d6b]">
                                        <input type="radio"
                                               name="answers[{{ $q->id }}]"
                                               value="{{ $c->id }}"
                                               {{ old('answers.' . $q->id) == $c->id ? 'checked' : '' }}
                                               class="h-4 w-4 text-[#0b2d6b] border-slate-300 focus:ring-[#0b2d6b] focus:ring-2 flex-shrink-0">
                                        <span class="text-sm text-slate-700 group-has-[:checked]:font-semibold group-has-[:checked]:text-[#0b2d6b]">{{ $c->choice_text }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            {{-- Essay textarea --}}
                            <textarea name="answers[{{ $q->id }}]"
                                      rows="5"
                                      placeholder="Write your answer here..."
                                      class="w-full rounded-lg border {{ $errors->has('answers.' . $q->id) ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20' : 'border-slate-300 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]/20' }} bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 outline-none focus:ring-2 resize-y transition-colors leading-relaxed">{{ old('answers.' . $q->id) }}</textarea>
                        @endif

                        @error('answers.' . $q->id)
                            <div class="mt-2 flex items-center gap-1.5 text-xs text-red-600">
                                <i data-lucide="alert-circle" class="h-3.5 w-3.5 flex-shrink-0"></i>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            @endforeach

            {{-- Submit Row --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-slate-500">
                        <i data-lucide="info" class="h-3.5 w-3.5 inline mr-1"></i>
                        @if($isMC)
                            All questions are required. Your score will be calculated automatically.
                        @else
                            All questions are required. Your teacher will review and grade your answers.
                        @endif
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('student.assignments.show', $assignment) }}" class="inline-flex items-center justify-center h-11 px-6 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</a>
                        <button type="submit" id="submit-btn" class="inline-flex items-center justify-center h-11 px-6 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-[#0b2d6b] focus:ring-offset-2">
                            <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <script>
            document.getElementById('questions-form').addEventListener('submit', function (e) {
                @if($isMC)
                    // Check every radio group has a selection
                    const questionIds = @json($questions->pluck('id')->values()->all());
                    for (const qid of questionIds) {
                        const radios = document.querySelectorAll('[name="answers[' + qid + ']"]');
                        const checked = Array.from(radios).some(r => r.checked);
                        if (!checked) {
                            e.preventDefault();
                            alert('Please answer all questions before submitting.');
                            return;
                        }
                    }
                @else
                    // Check every textarea has content
                    const textareas = this.querySelectorAll('textarea');
                    for (const t of textareas) {
                        if (!t.value.trim()) {
                            e.preventDefault();
                            alert('Please answer all questions before submitting.');
                            t.focus();
                            return;
                        }
                    }
                @endif
            });
        </script>

    @else
        {{-- Legacy text / file / link submission form --}}
        <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            @csrf
            <input type="hidden" name="submission_type" id="submission_type" value="file">

            <!-- Submission Type Tabs -->
            <div class="flex border-b border-slate-200 bg-gradient-to-r from-slate-50 to-transparent px-6">
                <button type="button" onclick="switchTab('text')" id="text-tab" class="px-4 py-4 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    <span>Text Entry</span>
                </button>
                <button type="button" onclick="switchTab('file')" id="file-tab" class="px-4 py-4 text-sm font-medium border-b-2 transition-colors border-[#0b2d6b] text-[#0b2d6b] flex items-center gap-2">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    <span>File Upload</span>
                </button>
                <button type="button" onclick="switchTab('link')" id="link-tab" class="px-4 py-4 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2">
                    <i data-lucide="link" class="h-4 w-4"></i>
                    <span>Link Submission</span>
                </button>
            </div>

            <div class="p-8">
                <!-- Text Entry Panel -->
                <div id="text-panel" class="hidden">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-3">Your Response</label>
                            <div id="editor-toolbar" class="rounded-t-lg border border-slate-300 bg-slate-50 p-3 flex flex-wrap gap-2 items-center">
                                <button type="button" class="ql-header ql-picker" title="Heading">
                                    <span class="ql-picker-label"></span>
                                    <span class="ql-picker-options"></span>
                                </button>
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>
                                <button type="button" class="ql-bold" title="Bold (Ctrl+B)"></button>
                                <button type="button" class="ql-italic" title="Italic (Ctrl+I)"></button>
                                <button type="button" class="ql-underline" title="Underline (Ctrl+U)"></button>
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>
                                <button type="button" class="ql-list" value="ordered" title="Numbered List"></button>
                                <button type="button" class="ql-list" value="bullet" title="Bullet List"></button>
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>
                                <button type="button" class="ql-link" title="Insert Link"></button>
                                <button type="button" class="ql-code-block" title="Code Block"></button>
                                <div class="w-px h-6 bg-slate-300 mx-1"></div>
                                <button type="button" class="ql-clean" title="Clear Formatting"></button>
                            </div>
                            <div id="editor" class="rounded-b-lg border border-t-0 border-slate-300 bg-white min-h-[350px] max-h-[500px] overflow-y-auto"></div>
                            <input type="hidden" name="text_content" id="text_content" value="">
                            @error('text_content')
                                <div class="text-xs text-red-600 mt-2 flex items-center gap-1">
                                    <i data-lucide="alert-circle" class="h-3 w-3"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="text-xs text-slate-500 mt-3 flex items-center gap-1">
                                <i data-lucide="info" class="h-3 w-3"></i>
                                Use the toolbar to format your text. Minimum 10 characters required.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Upload Panel -->
                <div id="file-panel">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-3">Upload File</label>
                            <div class="relative">
                                <input type="file" name="file" id="file-input" class="hidden" onchange="updateFileName(this)">
                                <div onclick="document.getElementById('file-input').click()" class="cursor-pointer rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-10 text-center hover:border-[#0b2d6b] hover:bg-blue-50 transition-all">
                                    <div class="flex justify-center mb-4">
                                        <div class="h-16 w-16 rounded-full bg-slate-200 flex items-center justify-center">
                                            <i data-lucide="upload-cloud" class="h-8 w-8 text-slate-500"></i>
                                        </div>
                                    </div>
                                    <div class="text-base font-semibold text-slate-900 mb-1">Click to upload or drag and drop</div>
                                    <div class="text-sm text-slate-500 mb-3">PNG, JPG, PDF, DOC, DOCX up to 10MB</div>
                                    <div id="selected-file-name" class="mt-4 text-sm font-medium text-emerald-600 hidden flex items-center justify-center gap-2">
                                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                                        <span></span>
                                    </div>
                                </div>
                                @error('file')
                                    <div class="text-xs text-red-600 mt-2 flex items-center gap-1">
                                        <i data-lucide="alert-circle" class="h-3 w-3"></i>
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Link Submission Panel -->
                <div id="link-panel" class="hidden">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-900 mb-3">Submission Link</label>
                            <input type="url" name="link_content" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-2 focus:ring-[#0b2d6b]/20 outline-none transition-colors" placeholder="https://example.com/your-work">
                            @error('link_content')
                                <div class="text-xs text-red-600 mt-2 flex items-center gap-1">
                                    <i data-lucide="alert-circle" class="h-3 w-3"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="text-xs text-slate-500 mt-3 flex items-center gap-1">
                                <i data-lucide="info" class="h-3 w-3"></i>
                                Paste a valid URL (Google Docs, YouTube, GitHub, etc.)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-8 border-t border-slate-200">
                    <a href="{{ route('student.assignments.show', $assignment) }}" class="inline-flex items-center justify-center h-11 px-6 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</a>
                    <button type="submit" id="submit-assignment-btn" class="inline-flex items-center justify-center h-11 px-6 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-[#0b2d6b] focus:ring-offset-2">
                        <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                        Send
                    </button>
                </div>
            </div>
        </form>

        <!-- Quill.js CSS -->
        <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

        <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
        <script>
            let quillEditor = null;

            function switchTab(type) {
                document.getElementById('text-panel').classList.add('hidden');
                document.getElementById('file-panel').classList.add('hidden');
                document.getElementById('link-panel').classList.add('hidden');

                ['text', 'file', 'link'].forEach(function (t) {
                    document.getElementById(t + '-tab').classList.remove('border-[#0b2d6b]', 'text-[#0b2d6b]');
                    document.getElementById(t + '-tab').classList.add('border-transparent', 'text-slate-500');
                });

                document.getElementById(type + '-panel').classList.remove('hidden');
                document.getElementById(type + '-tab').classList.remove('border-transparent', 'text-slate-500');
                document.getElementById(type + '-tab').classList.add('border-[#0b2d6b]', 'text-[#0b2d6b]');

                document.getElementById('submission_type').value = type;

                if (type === 'text' && !quillEditor) {
                    initializeQuillEditor();
                }
            }

            function initializeQuillEditor() {
                quillEditor = new Quill('#editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: { container: '#editor-toolbar' },
                        clipboard: { matchVisual: false }
                    },
                    placeholder: 'Start typing your response here...',
                    formats: ['header', 'bold', 'italic', 'underline', 'list', 'bullet', 'link', 'code-block']
                });

                quillEditor.on('text-change', function () {
                    document.getElementById('text_content').value = quillEditor.root.innerHTML;
                });

                const initial = document.getElementById('text_content').value;
                if (initial) quillEditor.root.innerHTML = initial;
            }

            function updateFileName(input) {
                const fileName = input.files[0]?.name;
                const el = document.getElementById('selected-file-name');
                if (fileName) {
                    el.querySelector('span').textContent = fileName;
                    el.classList.remove('hidden');
                } else {
                    el.classList.add('hidden');
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const dropZone = document.getElementById('file-panel').querySelector('[onclick*="file-input"]');

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (e) {
                    dropZone.addEventListener(e, function (ev) { ev.preventDefault(); ev.stopPropagation(); });
                });
                ['dragenter', 'dragover'].forEach(function (e) {
                    dropZone.addEventListener(e, function () { dropZone.classList.add('border-[#0b2d6b]', 'bg-blue-50'); });
                });
                ['dragleave', 'drop'].forEach(function (e) {
                    dropZone.addEventListener(e, function () { dropZone.classList.remove('border-[#0b2d6b]', 'bg-blue-50'); });
                });
                dropZone.addEventListener('drop', function (e) {
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        document.getElementById('file-input').files = files;
                        updateFileName(document.getElementById('file-input'));
                    }
                });
            });

            document.querySelector('form').addEventListener('submit', function (e) {
                const submissionType = document.getElementById('submission_type').value;
                if (submissionType === 'text' && quillEditor) {
                    const text = quillEditor.getText().trim();
                    if (text.length < 10) {
                        e.preventDefault();
                        alert('Text submission must be at least 10 characters long.');
                    }
                }
            });
        </script>
    @endif
@endsection
