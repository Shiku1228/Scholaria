@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'student.dashboard', 'href' => route('student.dashboard')],
        ['label' => 'Courses', 'route' => 'student.courses.index', 'href' => route('student.courses.index')],
        ['label' => 'Assignments', 'route' => 'student.assignments.index', 'href' => route('student.assignments.index')],
        ['label' => 'Grades', 'route' => 'student.grades.index', 'href' => route('student.grades.index')],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
        'Courses' => 'book-open',
        'Assignments' => 'clipboard-list',
        'Grades' => 'graduation-cap',
    ];
@endphp

@foreach ($nav as $item)
    @php
        $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
    @endphp

    <a href="{{ $item['href'] }}"
       title="{{ $item['label'] }}"
       class="slms-nav-item w-12 h-12 flex items-center justify-center rounded-xl transition-colors {{ $isActive ? 'bg-[#0b2d6b] text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' }}">
        <i data-lucide="{{ $icons[$item['label']] ?? 'circle' }}" style="width:20px;height:20px;"></i>
        <span class="slms-nav-label ml-3">{{ $item['label'] }}</span>
        <span class="sr-only">{{ $item['label'] }}</span>
    </a>
@endforeach
