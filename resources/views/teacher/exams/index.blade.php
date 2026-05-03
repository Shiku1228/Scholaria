@extends('layouts.teacher')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Exams</h1>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="course_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $filters['course_id'] == $course->id ? 'selected' : '' }}>
                                    {{ $course->title ?: $course->course_number }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @if($filters['course_id'] > 0)
                        @php
                            $selectedCourse = $courses->firstWhere('id', $filters['course_id']);
                        @endphp
                        @if($selectedCourse)
                            <a href="{{ route('teacher.exams.create', $selectedCourse) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Exam
                            </a>
                        @else
                            <a href="{{ route('teacher.courses.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-plus"></i> Select Course First
                            </a>
                        @endif
                    @else
                        <a href="{{ route('teacher.courses.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Select Course to Schedule Exam
                        </a>
                    @endif
                </div>
            </div>

            @if($exams->count() > 0)
                <div class="row">
                    @foreach($exams as $exam)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="badge bg-danger">Exam</span>
                                    <small class="text-muted">{{ $exam->course->title ?? $exam->course->course_number }}</small>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">{{ $exam->title }}</h5>
                                    <p class="card-text">{{ Str::limit($exam->description, 100) }}</p>
                                    
                                    <div class="row text-sm">
                                        <div class="col-6">
                                            <strong>Date:</strong><br>
                                            {{ $exam->exam_date ? $exam->exam_date->format('M j, Y') : 'Not set' }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Time:</strong><br>
                                            {{ $exam->exam_date ? $exam->exam_date->format('g:i A') : 'Not set' }}
                                        </div>
                                        <div class="col-6">
                                            <strong>Duration:</strong><br>
                                            {{ $exam->duration ?? 120 }} minutes
                                        </div>
                                        <div class="col-6">
                                            <strong>Max Score:</strong><br>
                                            {{ $exam->max_score ?? 100 }}
                                        </div>
                                        @if($exam->location)
                                            <div class="col-12">
                                                <strong>Location:</strong><br>
                                                {{ $exam->location }}
                                            </div>
                                        @endif
                                    </div>

                                    @if($exam->exam_date && $exam->exam_date->isPast())
                                        <div class="mt-2">
                                            <span class="badge bg-success">Completed</span>
                                        </div>
                                    @elseif($exam->exam_date && $exam->exam_date->isToday())
                                        <div class="mt-2">
                                            <span class="badge bg-warning">Today</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="{{ route('teacher.exams.show', $exam) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('teacher.exams.edit', $exam) }}" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" action="{{ route('teacher.exams.destroy', $exam) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex justify-content-center">
                    {{ $exams->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h3 class="text-muted">No Exams Found</h3>
                    <p class="text-muted">You haven't scheduled any exams yet.</p>
                    <a href="{{ route('teacher.courses.index') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Schedule Your First Exam
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
