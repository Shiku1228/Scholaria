@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'student.dashboard', 'href' => route('student.dashboard')],
        ['label' => 'Courses', 'route' => 'student.courses.index', 'href' => route('student.courses.index')],
        ['label' => 'Tasks', 'route' => 'student.tasks.index', 'href' => route('student.tasks.index')],
        ['label' => 'Grades', 'route' => 'student.grades.index', 'href' => route('student.grades.index')],
        ['label' => 'Messages', 'route' => 'student.messages', 'href' => route('student.messages')],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
        'Courses' => 'book-open',
        'Tasks' => 'clipboard-list',
        'Grades' => 'graduation-cap',
        'Messages' => 'message-circle',
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
