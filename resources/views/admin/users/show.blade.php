@extends('layouts.dashboard', [
    'title' => 'View User Details',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('content')
    <div class="text-xl font-semibold">View User Details</div>
    <div class="text-sm text-gray-500">View user account information and profile details</div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        <!-- Basic Information -->
        <div class="space-y-4">
            <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        {{ $user->name }}
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                        {{ $user->email }}
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <div class="mt-1">
                    @if($user->trashed())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Deleted
                        </span>
                    @elseif($user->email_verified_at)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Verified
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Not Verified
                        </span>
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Roles</label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    @if(method_exists($user, 'getRoleNames'))
                        {{ $user->getRoleNames()->implode(', ') }}
                    @else
                        No roles assigned
                    @endif
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Created At</label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    {{ $user->created_at->format('M d, Y g:i A') }}
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    {{ $user->updated_at->format('M d, Y g:i A') }}
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        @if($user->profile)
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                
                @if($user->student)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Student Number</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->student->student_number }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Year Level</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->student->year_level }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->student->program }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">College</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->student->college }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Enrollment Date</label>
                        <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            {{ $user->student->enrollment_date->format('M d, Y') }}
                        </div>
                    </div>
                @endif

                @if($user->teacher)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Employee ID</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->teacher->employee_id }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hire Date</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->teacher->hire_date->format('M d, Y') }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Program</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->teacher->program }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Specialization</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->teacher->specialization }}
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">College</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                {{ $user->teacher->college }}
                            </div>
                        </div>
                    </div>
                @endif

                @if($user->admin)
                    @if(method_exists($user, 'getRoleNames'))
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Granular Role</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                @php
                                    $granularRole = $user->getRoleNames()->diff(['Admin', 'Teacher', 'Student'])->first();
                                    echo $granularRole ?: 'No granular role assigned';
                                @endphp
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-gray-500">No profile information available</div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="mt-8 flex items-center gap-3">
            @can('users.edit')
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    Edit User
                </a>
            @endcan
            
            @if($user->trashed())
                @can('users.delete')
                    <form method="POST" action="{{ route('admin.users.restore', $user) }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-green-600 text-white text-sm font-semibold hover:bg-green-700">
                            Restore User
                        </button>
                    </form>
                @endcan
            @endif

            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Back to Users
            </a>
        </div>
    </div>
@endsection
