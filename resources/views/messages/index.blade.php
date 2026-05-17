@php
    $user = auth()->user();
    $isTeacher = $user && method_exists($user, 'hasRole') && $user->hasRole('Teacher');
    $isStudent = $user && method_exists($user, 'hasRole') && $user->hasRole('Student');
    $layout = $isTeacher ? 'layouts.teacher' : ($isStudent ? 'layouts.student' : 'layouts.dashboard');
@endphp

@extends($layout)

@section('content')
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="text-xl font-semibold text-slate-900">Messages</div>
            <div class="mt-1 text-sm text-slate-500">Select a course to enter its group and private chats.</div>
        </div>

        @if ($courses->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-sm text-slate-500">
                No accessible courses for messaging yet.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($courses as $course)
                    <a href="{{ route('messages.courses.show', ['course' => $course['id']]) }}"
                       class="block rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-[#c9d7f2] hover:bg-[#f8fbff] transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900 truncate">{{ $course['name'] }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $course['member_count'] }} members</div>
                            </div>
                            @if (($course['unread_count'] ?? 0) > 0)
                                <span class="inline-flex min-w-[20px] h-[20px] px-1 items-center justify-center rounded-full bg-[#0b2d6b] text-white text-[11px] font-bold">
                                    {{ $course['unread_count'] }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-3 text-xs text-slate-500 line-clamp-2">{{ $course['latest_preview'] }}</div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endsection

