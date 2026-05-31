@extends('layouts.student')

@section('content')
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-3xl font-extrabold tracking-tight text-slate-900">NEXT UP</div>
                    <div class="mt-1 text-sm text-slate-500">Due soon, not started, needs review, and recommended items.</div>
                </div>

                <form method="GET" action="{{ route('student.next-up') }}" class="flex items-center gap-3">
                    <label class="text-xs text-slate-500" for="days">Window (days)</label>
                    <select name="days" id="days" class="h-10 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        @foreach([3,7,14,30] as $d)
                            <option value="{{ $d }}" {{ (int)($days ?? 7) === (int)$d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center h-10 px-4 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">Apply</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            @php
                $panels = [
                    'dueSoon' => ['title' => 'Due soon', 'items' => $dueSoon ?? [] , 'bg' => 'bg-blue-50 border-blue-100 text-blue-900'],
                    'notStarted' => ['title' => 'Not started', 'items' => $notStarted ?? [] , 'bg' => 'bg-amber-50 border-amber-100 text-amber-900'],
                    'needsReview' => ['title' => 'Needs review', 'items' => $needsReview ?? [] , 'bg' => 'bg-rose-50 border-rose-100 text-rose-900'],
                    'recommended' => ['title' => 'Recommended', 'items' => $recommended ?? [] , 'bg' => 'bg-emerald-50 border-emerald-100 text-emerald-900'],
                ];
            @endphp

            @foreach($panels as $key => $p)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-gray-100">
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm font-semibold">{{ $p['title'] }}</div>
                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $key === 'dueSoon' ? 'bg-blue-100 text-blue-700' : ($key === 'notStarted' ? 'bg-amber-100 text-amber-700' : ($key === 'needsReview' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700')) }}">
                                {{ count($p['items'] ?? []) }}
                            </div>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5 space-y-3">
                        @forelse($p['items'] as $item)
                            <div class="rounded-xl border border-slate-200 p-3 hover:bg-slate-50 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900 leading-snug">{{ $item['title'] ?? '' }}</div>
                                        @php
                                            $type = strtolower((string)($item['type'] ?? 'assignment'));
                                            $courseId = (int)($item['course_id'] ?? 0);
                                        @endphp
                                        <div class="mt-1 text-xs text-slate-500">
                                            Type: {{ $type }}
                                            @if(!empty($item['due']))
                                                • Due: {{ 
                                                    
                                                    $item['due'] instanceof \Carbon\Carbon ? $item['due']->format('M d, Y') : (string)$item['due']
                                                }}
                                            @endif
                                        </div>
                                    </div>

                                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700">
                                        {{ strtoupper($type) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500">No items in this category.</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection

