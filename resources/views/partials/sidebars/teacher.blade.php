@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'teacher.dashboard', 'href' => route('teacher.dashboard')],
        ['label' => 'My Courses', 'route' => 'teacher.courses.*', 'href' => route('teacher.courses.index')],
        ['label' => 'Students', 'route' => 'teacher.students.*', 'href' => route('teacher.students.index')],
        ['label' => 'Enrollments', 'route' => 'teacher.enrollments.*', 'href' => route('teacher.enrollments.index')],
        ['label' => 'Announcements', 'route' => 'teacher.announcements*', 'href' => route('teacher.announcements')],
        ['label' => 'Question Banks', 'route' => 'teacher.question-banks.*', 'href' => route('teacher.question-banks.index')],
        ['label' => 'Messages', 'route' => 'teacher.messages', 'href' => route('teacher.messages')],
        ['label' => 'Calendar', 'route' => 'teacher.calendar', 'href' => route('teacher.calendar')],
        ['label' => 'Attendance', 'route' => 'teacher.attendance.*', 'href' => route('teacher.attendance.index')],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
        'My Courses' => 'book-open',
        'Students' => 'users',
        'Enrollments' => 'user-plus',
        'Announcements' => 'megaphone',
        'Question Banks' => 'database',
        'Messages' => 'message-circle',
        'Calendar'   => 'calendar',
        'Attendance' => 'clipboard-check',
    ];
@endphp


@foreach ($nav as $item)
    @php
        $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
    @endphp

    <a href="{{ $item['href'] }}"
       title="{{ $item['label'] }}"
       class="slms-nav-item relative w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons[$item['label']] ?? 'circle' }}" style="width:20px;height:20px;"></i>
        @if (($item['label'] ?? '') === 'Messages')
            <span id="sidebarMessageUnreadBadge" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center {{ ((int) ($headerUnreadMessageCount ?? 0)) > 0 ? '' : 'hidden' }}">
                {{ min(99, (int) ($headerUnreadMessageCount ?? 0)) }}
            </span>
        @endif
        <span class="slms-nav-label ml-3">{{ $item['label'] }}</span>
        <span class="sr-only">{{ $item['label'] }}</span>
    </a>
@endforeach
