@php
    $nav = [
        ['label' => 'Dashboard', 'route' => 'student.dashboard', 'href' => route('student.dashboard')],
    ];

    $icons = [
        'Dashboard' => 'layout-dashboard',
    ];
@endphp

<div class="space-y-1">
    @foreach ($nav as $item)
        @php
            $isActive = isset($item['route']) ? request()->routeIs($item['route']) : false;
        @endphp
        <a href="{{ $item['href'] }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium {{ $isActive ? 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200' : 'text-gray-700 hover:bg-gray-50' }}">
            <span class="h-8 w-8 rounded-lg flex items-center justify-center {{ $isActive ? 'bg-violet-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                <i data-lucide="{{ $icons[$item['label']] ?? 'circle' }}" style="width:18px;height:18px;display:block;line-height:0;"></i>
            </span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>
