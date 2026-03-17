@extends('layouts.admin')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-white via-slate-50 to-white p-6 shadow-sm">
        <div class="text-3xl font-bold tracking-tight text-slate-900">Create Course</div>
        <div class="mt-1 text-base text-slate-600">Add a new course and configure schedule, teacher, and class details.</div>
    </div>

    <form method="POST" action="{{ route('admin.courses.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
        @csrf

        @include('admin.courses._form', ['course' => null])

        <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="inline-flex items-center justify-center h-11 px-6 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c] shadow-sm">Save Course</button>
            <a href="{{ route('admin.courses.index') }}" class="inline-flex items-center justify-center h-11 px-6 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        </div>
    </form>
@endsection

