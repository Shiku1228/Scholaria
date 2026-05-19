@php
    $type = (string) ($type ?? 'event');
    $title = (string) ($title ?? '');
    $meta = (string) ($meta ?? '');
    $href = (string) ($href ?? '#');

    $badgeClass = match($type) {
        'class' => 'bg-blue-50 border-blue-100 text-blue-800',
        'assignment', 'quiz' => 'bg-amber-50 border-amber-100 text-amber-800',
        'exam' => 'bg-purple-50 border-purple-100 text-purple-800',
        'announcement' => 'bg-emerald-50 border-emerald-100 text-emerald-800',
        default => 'bg-slate-50 border-slate-100 text-slate-800',
    };
@endphp

<a href="{{ $href }}" class="block rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 transition px-4 py-3">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="font-semibold text-slate-900 truncate">{{ $title }}</div>
            <div class="mt-1 text-sm text-slate-600">{{ $meta }}</div>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-semibold border {{ $badgeClass }}">
            {{ strtoupper($type) }}
        </span>
    </div>
</a>

