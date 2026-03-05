<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6">
    <div class="text-2xl font-semibold">WELCOME {{ (string) ($roleTitle ?? '') }}</div>
    <div class="text-sm text-gray-500 mt-1">{{ (string) ($subtitle ?? auth()->user()->name) }}</div>
    @if (isset($info) && is_string($info) && $info !== '')
        <div class="text-xs text-gray-500 mt-1">{{ $info }}</div>
    @endif
</div>
