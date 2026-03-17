@php
    $items = is_array($items ?? null) ? $items : [];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    @foreach ($items as $item)
        @php
            $label = (string) data_get($item, 'label', '');
            $value = data_get($item, 'value', 'â€”');
            $icon = (string) data_get($item, 'icon', 'activity');
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-start justify-between gap-4">
            <div>
                <div class="text-xs text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-semibold text-gray-900 mt-2">{{ is_numeric($value) ? number_format((float) $value) : $value }}</div>
            </div>
            <div class="h-11 w-11 rounded-2xl bg-[#eaf0fb] flex items-center justify-center">
                <i data-lucide="{{ $icon }}" style="width:20px;height:20px;color:rgb(109 40 217);"></i>
            </div>
        </div>
    @endforeach
</div>

