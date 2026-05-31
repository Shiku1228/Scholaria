@extends('layouts.dashboard', [
    'title' => 'Student Calendar',
    'sidebarPartial' => 'partials.sidebars.student',
])

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">Calendar</h1>
                    <p class="mt-1 text-sm sm:text-base text-slate-500">
                        Agenda from <span class="font-semibold text-slate-700">{{ $windowStart }}</span> to <span class="font-semibold text-slate-700">{{ $windowEnd }}</span>
                    </p>
                </div>

                <form method="GET" class="flex items-center gap-3">
                    <label class="text-xs text-slate-500" for="days">Window (days)</label>
                    <select name="days" id="days" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        @foreach([7,14,30,60] as $d)
                            <option value="{{ $d }}" {{ (int) ($windowDays ?? 30) === (int) $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Apply</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-6 border-b border-slate-200">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-lg font-bold text-slate-900">Agenda</div>
                    <div class="text-xs text-slate-500">Grouped by date</div>
                </div>
            </div>

            <div class="p-4 sm:p-6 space-y-6">
                @if (empty($agenda))
                    <div class="text-sm text-slate-500">No events found in this window.</div>
                @else
                    @foreach($agenda as $date => $items)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="text-xs font-extrabold uppercase tracking-wide text-slate-500">{{ $date }}</div>
                                <div class="text-xs text-slate-500">{{ count($items) }} item{{ count($items) === 1 ? '' : 's' }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($items as $item)
                                    @include('partials.calendar.mini-event-card', [
                                        'type' => (string) ($item['type'] ?? 'event'),
                                        'title' => (string) ($item['title'] ?? ''),
                                        'meta' => (string) ($item['meta'] ?? ''),
                                        'href' => (string) ($item['href'] ?? '#'),
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection

