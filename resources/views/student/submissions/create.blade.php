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
                        
                        <!-- Editor Toolbar -->
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
                        
                        <!-- Editor Container -->
                        <div id="editor" class="rounded-b-lg border border-t-0 border-slate-300 bg-white min-h-[350px] max-h-[500px] overflow-y-auto"></div>
                        
                        <!-- Hidden input to store content -->
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
                <button type="submit" class="inline-flex items-center justify-center h-11 px-6 rounded-lg bg-gradient-to-r from-[#0b2d6b] to-[#0a275c] text-white text-sm font-semibold hover:shadow-lg transition-all">
                    <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                    Submit Assignment
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
            // Hide all panels
            document.getElementById('text-panel').classList.add('hidden');
            document.getElementById('file-panel').classList.add('hidden');
            document.getElementById('link-panel').classList.add('hidden');
            
            // Reset tab styles
            document.getElementById('text-tab').classList.remove('border-[#0b2d6b]', 'text-[#0b2d6b]');
            document.getElementById('text-tab').classList.add('border-transparent', 'text-slate-500');
            document.getElementById('file-tab').classList.remove('border-[#0b2d6b]', 'text-[#0b2d6b]');
            document.getElementById('file-tab').classList.add('border-transparent', 'text-slate-500');
            document.getElementById('link-tab').classList.remove('border-[#0b2d6b]', 'text-[#0b2d6b]');
            document.getElementById('link-tab').classList.add('border-transparent', 'text-slate-500');
            
            // Show selected panel and highlight tab
            document.getElementById(type + '-panel').classList.remove('hidden');
            document.getElementById(type + '-tab').classList.remove('border-transparent', 'text-slate-500');
            document.getElementById(type + '-tab').classList.add('border-[#0b2d6b]', 'text-[#0b2d6b]');
            
            // Update hidden input
            document.getElementById('submission_type').value = type;
            
            // Initialize Quill editor when text tab is opened
            if (type === 'text' && !quillEditor) {
                initializeQuillEditor();
            }
        }
        
        function initializeQuillEditor() {
            quillEditor = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: '#editor-toolbar',
                        handlers: {
                            // Custom handlers if needed
                        }
                    },
                    clipboard: {
                        matchVisual: false
                    }
                },
                placeholder: 'Start typing your response here...',
                formats: [
                    'header', 'bold', 'italic', 'underline', 'list', 'bullet',
                    'link', 'code-block', 'clean'
                ]
            });
            
            // Update hidden input when content changes
            quillEditor.on('text-change', function() {
                const content = quillEditor.root.innerHTML;
                document.getElementById('text_content').value = content;
            });
            
            // Set initial content
            const initialContent = document.getElementById('text_content').value;
            if (initialContent) {
                quillEditor.root.innerHTML = initialContent;
            }
        }
        
        function updateFileName(input) {
            const fileName = input.files[0]?.name;
            const fileNameElement = document.getElementById('selected-file-name');
            
            if (fileName) {
                fileNameElement.querySelector('span').textContent = fileName;
                fileNameElement.classList.remove('hidden');
            } else {
                fileNameElement.classList.add('hidden');
            }
        }
        
        // Initialize file drag and drop
        document.addEventListener('DOMContentLoaded', function() {
            const filePanel = document.getElementById('file-panel');
            const dropZone = filePanel.querySelector('[onclick*="file-input"]');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.add('border-[#0b2d6b]', 'bg-blue-50');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => {
                    dropZone.classList.remove('border-[#0b2d6b]', 'bg-blue-50');
                }, false);
            });
            
            dropZone.addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    document.getElementById('file-input').files = files;
                    updateFileName(document.getElementById('file-input'));
                }
            }, false);
        });
        
        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const submissionType = document.getElementById('submission_type').value;
            
            if (submissionType === 'text' && quillEditor) {
                const text = quillEditor.getText().trim();
                if (text.length < 10) {
                    e.preventDefault();
                    alert('Text submission must be at least 10 characters long.');
                    return false;
                }
            }
        });
    </script>
@endsection

