@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'teacher.dashboard', 'href' => route('teacher.dashboard')],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
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
