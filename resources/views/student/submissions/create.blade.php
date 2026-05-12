@extends('layouts.student')

@section('content')
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-2xl font-semibold text-slate-900">
                <i data-lucide="send" class="h-5 w-5 text-[#0b2d6b]"></i>
                <span>Submit Assignment</span>
            </div>
            <div class="mt-1 text-sm text-slate-500">{{ $assignment->title ?? ('Assignment #' . $assignment->id) }}</div>
        </div>
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center h-10 px-4 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
    </div>

    <form method="POST" action="{{ route('student.assignments.submit.store', $assignment) }}" enctype="multipart/form-data" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 shadow-sm overflow-hidden">
        @csrf
        <input type="hidden" name="submission_type" id="submission_type" value="file">

        <!-- Submission Type Tabs -->
        <div class="flex border-b border-slate-200 bg-white px-6">
            <button type="button" onclick="switchTab('text')" id="text-tab" class="px-4 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">
                <span class="flex items-center gap-2">
                    <i data-lucide="file-text" class="h-4 w-4"></i>
                    Text Entry
                </span>
            </button>
            <button type="button" onclick="switchTab('file')" id="file-tab" class="px-4 py-3 text-sm font-medium border-b-2 transition-colors border-[#0b2d6b] text-[#0b2d6b]">
                <span class="flex items-center gap-2">
                    <i data-lucide="upload" class="h-4 w-4"></i>
                    File Upload
                </span>
            </button>
            <button type="button" onclick="switchTab('link')" id="link-tab" class="px-4 py-3 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">
                <span class="flex items-center gap-2">
                    <i data-lucide="link" class="h-4 w-4"></i>
                    Link Submission
                </span>
            </button>
        </div>

        <div class="p-6">
            <!-- Text Entry Panel -->
            <div id="text-panel" class="hidden">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Type your response</label>
                        
                        <!-- Editor Toolbar -->
                        <div id="editor-toolbar" class="rounded-t-xl border border-slate-300 bg-slate-50 p-2 flex flex-wrap gap-1 items-center">
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
                        <div id="editor" class="rounded-b-xl border border-t-0 border-slate-300 bg-white min-h-[300px] max-h-[500px] overflow-y-auto"></div>
                        
                        <!-- Hidden input to store content -->
                        <input type="hidden" name="text_content" id="text_content" value="">
                        
                        @error('text_content')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                        <div class="text-xs text-slate-500 mt-2">Use the toolbar to format your text. Minimum 10 characters.</div>
                    </div>
                </div>
            </div>

            <!-- File Upload Panel -->
            <div id="file-panel">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Choose file to upload</label>
                        <div class="relative">
                            <input type="file" name="file" id="file-input" class="hidden" onchange="updateFileName(this)">
                            <div onclick="document.getElementById('file-input').click()" class="cursor-pointer rounded-xl border-2 border-dashed border-slate-300 bg-white p-8 text-center hover:border-[#0b2d6b] transition-colors">
                                <i data-lucide="upload-cloud" class="h-12 w-12 text-slate-400 mx-auto mb-3"></i>
                                <div class="text-sm font-medium text-slate-700 mb-1">Click to upload or drag and drop</div>
                                <div class="text-xs text-slate-500">Maximum file size: 10MB</div>
                                <div id="selected-file-name" class="mt-3 text-sm font-medium text-[#0b2d6b] hidden"></div>
                            </div>
                            @error('file')
                                <div class="text-xs text-red-600 mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Link Submission Panel -->
            <div id="link-panel" class="hidden">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Submit link</label>
                        <input type="url" name="link_content" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#0b2d6b] focus:ring-[#0b2d6b] outline-none" placeholder="https://example.com/your-work">
                        @error('link_content')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                        <div class="text-xs text-slate-500 mt-2">Enter a valid URL (e.g., Google Docs, YouTube, GitHub, etc.)</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 bg-slate-50 -mx-6 px-6 -mb-6 pb-6">
                <a href="{{ route('student.dashboard') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] flex items-center gap-2">
                    <i data-lucide="send" class="h-4 w-4"></i>
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
                fileNameElement.textContent = 'Selected: ' + fileName;
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

