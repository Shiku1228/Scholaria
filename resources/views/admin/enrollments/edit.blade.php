@extends('layouts.admin')

@section('content')
    <div>
        <div class="text-xl font-semibold">Edit Enrollment</div>
        <div class="text-sm text-gray-500">Update enrollment details</div>
    </div>

    <form method="POST" action="{{ route('admin.enrollments.update', $enrollment) }}" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf
        @method('PUT')

        @include('admin.enrollments._form')

        <div class="flex items-center gap-3">
            <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Update</button>
            <a href="{{ route('admin.enrollments.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
@endsection

