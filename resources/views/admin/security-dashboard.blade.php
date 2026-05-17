@extends('layouts.dashboard', [
    'title' => 'Security Dashboard',
    'sidebarPartial' => 'partials.sidebars.admin',
])

@section('title')
    Security Dashboard
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Security Dashboard</h1>
                <p class="mt-2 text-sm text-slate-600">Monitor security events, alerts, and system integrity</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <form id="timeframeForm" method="GET" action="{{ route('admin.security-dashboard.index') }}" class="flex items-center gap-3">
                    <select id="timeframe" name="timeframe" class="h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                        <option value="1h" {{ $timeframe === '1h' ? 'selected' : '' }}>Last Hour</option>
                        <option value="6h" {{ $timeframe === '6h' ? 'selected' : '' }}>Last 6 Hours</option>
                        <option value="24h" {{ $timeframe === '24h' ? 'selected' : '' }}>Last 24 Hours</option>
                        <option value="7d" {{ $timeframe === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                        <option value="30d" {{ $timeframe === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        <i data-lucide="refresh-cw" class="h-4 w-4 mr-2"></i> Refresh
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Security Metrics Overview -->
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-red-500 to-red-600">
            <div class="text-base text-red-100">Critical Events</div>
            <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($metrics['critical_events'] ?? 0)) }}</div>
            <div class="mt-2 text-sm text-red-100">Last {{ $timeframe }}</div>
        </div>

        <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-amber-500 to-orange-500">
            <div class="text-base text-amber-100">High Events</div>
            <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($metrics['high_events'] ?? 0)) }}</div>
            <div class="mt-2 text-sm text-amber-100">Last {{ $timeframe }}</div>
        </div>

        <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-blue-500 to-indigo-600">
            <div class="text-base text-blue-100">Medium Events</div>
            <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($metrics['medium_events'] ?? 0)) }}</div>
            <div class="mt-2 text-sm text-blue-100">Last {{ $timeframe }}</div>
        </div>

        <div class="rounded-3xl p-6 text-white shadow-lg bg-gradient-to-br from-emerald-500 to-teal-600">
            <div class="text-base text-emerald-100">Low Events</div>
            <div class="mt-2 text-5xl font-extrabold">{{ number_format((int) ($metrics['low_events'] ?? 0)) }}</div>
            <div class="mt-2 text-sm text-emerald-100">Last {{ $timeframe }}</div>
        </div>
    </section>

    <!-- Security Score and Status -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-semibold text-slate-900">Security Score</div>
            <div class="mt-3 flex items-center gap-3">
                <div class="text-4xl font-extrabold {{ $metrics['security_score'] >= 80 ? 'text-emerald-600' : ($metrics['security_score'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $metrics['security_score'] }}/100
                </div>
                <span class="text-xs text-slate-500">Higher is better</span>
            </div>
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full rounded-full {{ $metrics['security_score'] >= 80 ? 'bg-emerald-500' : ($metrics['security_score'] >= 60 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $metrics['security_score'] }}%"></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-semibold text-slate-900">Unresolved Events</div>
            <div class="mt-3 text-4xl font-extrabold {{ $metrics['unresolved_events'] > 0 ? 'text-red-600' : 'text-slate-400' }}">
                {{ number_format((int) ($metrics['unresolved_events'] ?? 0)) }}
            </div>
            <div class="mt-2 text-xs text-slate-500">Requires action</div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="text-sm font-semibold text-slate-900">Active Sessions</div>
            <div class="mt-3 text-4xl font-extrabold text-slate-900">
                {{ number_format((int) ($metrics['active_sessions'] ?? 0)) }}
            </div>
            <div class="mt-2 text-xs text-slate-500">Currently logged in</div>
        </div>
    </section>

    <!-- Recent Security Alerts -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between gap-3">
            <div class="flex items-start gap-2.5">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i data-lucide="bell" class="h-4 w-4"></i>
                </span>
                <div>
                    <div class="text-xl font-bold text-slate-900">Recent Security Alerts</div>
                    <div class="text-sm text-slate-500">Latest security events and notifications</div>
                </div>
            </div>
            <button onclick="refreshAlerts()" class="inline-flex items-center justify-center h-9 px-4 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="refresh-cw" class="h-4 w-4 mr-2"></i> Refresh
            </button>
        </div>
        <div class="p-4 sm:p-5">
            @if(empty($alerts))
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                    <i data-lucide="shield-check" class="h-12 w-12 mx-auto mb-3 text-emerald-500"></i>
                    <p>No security alerts in the selected timeframe</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                                <th class="py-3 px-4">Time</th>
                                <th class="py-3 px-4">Severity</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4">Message</th>
                                <th class="py-3 px-4">IP Address</th>
                                <th class="py-3 px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($alerts as $alert)
                                @php
                                    $badgeClass = match($alert['severity']) {
                                        'critical' => 'bg-red-50 text-red-700 border-red-200',
                                        'high' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'medium' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="text-xs text-slate-500">{{ $alert['created_at']->format('H:i') }}</div>
                                        <div class="text-xs text-slate-400">{{ $alert['created_at']->format('M j, Y') }}</div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center h-7 px-3 rounded-lg border text-xs font-medium {{ $badgeClass }}">
                                            {{ strtoupper($alert['severity']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">{{ $alert['type'] }}</td>
                                    <td class="py-3 px-4">{{ Str::limit($alert['message'], 80) }}</td>
                                    <td class="py-3 px-4"><code class="text-xs bg-slate-100 px-2 py-1 rounded">{{ $alert['data']['ip_address'] ?? 'N/A' }}</code></td>
                                    <td class="py-3 px-4">
                                        @if($alert['severity'] === 'critical')
                                            <button onclick="resolveEvent({{ $alert['id'] }})" class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700">
                                                <i data-lucide="alert-triangle" class="h-3.5 w-3.5 mr-1.5"></i> Resolve
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    <!-- Activity Log -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between gap-3">
            <div class="flex items-start gap-2.5">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="list" class="h-4 w-4"></i>
                </span>
                <div>
                    <div class="text-xl font-bold text-slate-900">Activity Log</div>
                    <div class="text-sm text-slate-500">Recent system activities and events</div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="activityTimeframe" onchange="refreshActivityLog()" class="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-700 focus:border-[#0b2d6b] focus:ring-[#0b2d6b]">
                    <option value="1h">Last Hour</option>
                    <option value="6h">Last 6 Hours</option>
                    <option value="24h" selected>Last 24 Hours</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
                <button onclick="refreshActivityLog()" class="inline-flex items-center justify-center h-9 px-3 rounded-lg border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="refresh-cw" class="h-4 w-4 mr-2"></i> Refresh
                </button>
            </div>
        </div>
        <div class="p-4 sm:p-5">
            @if(empty($activityLogData['recent_activities']))
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                    <i data-lucide="list" class="h-12 w-12 mx-auto mb-3 text-blue-500"></i>
                    <p>No activities in the selected timeframe</p>
                </div>
            @else
                <div class="space-y-4">
                    <!-- Activity Summary -->
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                        <div class="text-sm text-slate-600">
                            Total Activities: <span class="font-semibold text-slate-900">{{ number_format((int) ($activityLogData['total_activities'] ?? 0)) }}</span>
                        </div>
                        <div class="text-sm text-slate-600">
                            Showing: <span class="font-semibold text-slate-900">{{ count($activityLogData['recent_activities']) }}</span> recent activities
                        </div>
                    </div>
                    
                    <!-- Activity List -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
                                    <th class="py-3 px-4">Time</th>
                                    <th class="py-3 px-4">User</th>
                                    <th class="py-3 px-4">Activity</th>
                                    <th class="py-3 px-4">IP Address</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($activityLogData['recent_activities'] as $activity)
                                    @php
                                        $statusClass = match($activity->severity ?? 'info') {
                                            'critical' => 'bg-red-50 text-red-700 border-red-200',
                                            'high' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'medium' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'low' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <tr class="text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors">
                                        <td class="py-3 px-4">
                                            <div class="text-xs text-slate-500">{{ $activity->created_at->format('H:i') }}</div>
                                            <div class="text-xs text-slate-400">{{ $activity->created_at->format('M j, Y') }}</div>
                                        </td>
                                        <td class="py-3 px-4">
                                            @if($activity->user)
                                                <div class="flex items-center gap-2">
                                                    <div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center">
                                                        <span class="text-xs font-medium text-slate-600">
                                                            {{ strtoupper(substr($activity->user->name ?? '?', 0, 1)) }}
                                                        </span>
                                                    </div>
                                                    <span class="text-sm font-medium">{{ $activity->user->name ?? 'Unknown' }}</span>
                                                </div>
                                            @else
                                                <span class="text-sm text-slate-500">System</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="text-sm font-medium">{{ $activity->event_type ?? 'Unknown' }}</div>
                                            @if($activity->description)
                                                <div class="text-xs text-slate-500 mt-1">{{ Str::limit($activity->description, 60) }}</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4">
                                            <code class="text-xs bg-slate-100 px-2 py-1 rounded">{{ $activity->ip_address ?? 'N/A' }}</code>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center h-6 px-2 rounded text-xs font-medium {{ $statusClass }}">
                                                {{ ucfirst($activity->severity ?? 'info') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <button onclick="viewActivityDetails('{{ $activity->id }}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- View All Activities Button -->
                    <div class="mt-4 text-center">
                        <button onclick="viewAllActivities()" class="inline-flex items-center justify-center h-10 px-6 rounded-lg bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                            <i data-lucide="list" class="h-4 w-4 mr-2"></i> View All Activities
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="p-4 sm:p-5 border-b border-slate-100">
            <div class="flex items-start gap-2.5">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <i data-lucide="settings" class="h-4 w-4"></i>
                </span>
                <div>
                    <div class="text-xl font-bold text-slate-900">Quick Actions</div>
                    <div class="text-sm text-slate-500">Common security management tasks</div>
                </div>
            </div>
        </div>
        <div class="p-4 sm:p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <button onclick="generateReport('weekly')" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    <i data-lucide="file-down" class="h-4 w-4 mr-2"></i> Generate Weekly Report
                </button>
                <button onclick="generateReport('monthly')" class="inline-flex items-center justify-center h-11 px-5 rounded-xl bg-[#0b2d6b] text-white text-sm font-semibold hover:bg-[#0a275c]">
                    <i data-lucide="file-down" class="h-4 w-4 mr-2"></i> Generate Monthly Report
                </button>
                <button onclick="testAlertSystem()" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="bell" class="h-4 w-4 mr-2"></i> Test Alert System
                </button>
                <button onclick="viewFailedLogins()" class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i data-lucide="shield" class="h-4 w-4 mr-2"></i> View Failed Logins
                </button>
            </div>
        </div>
    </section>
</div>

<script>
let refreshInterval = {{ $refreshInterval ?? 30 }} * 1000; // Convert to milliseconds

function refreshAlerts() {
    fetch(`{{ route('admin.security-dashboard.alerts') }}?limit=20`)
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data) || data.success) {
                location.reload();
            }
        })
        .catch(error => console.error('Error refreshing alerts:', error));
}

function generateReport(type) {
    window.location.href = `{{ route('admin.security-dashboard.report') }}?type=${type}`;
}

function testAlertSystem() {
    if (confirm('Send a test security alert?')) {
        fetch(`{{ route('admin.security-dashboard.test-alert') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test alert sent successfully!');
                refreshAlerts();
            } else {
                alert('Failed to send test alert: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error sending test alert: ' + error.message);
        });
    }
}

function viewFailedLogins() {
    window.open(`{{ route('admin.security-dashboard.report') }}?type=failed_logins`, '_blank');
}

function refreshActivityLog() {
    const timeframe = document.getElementById('activityTimeframe').value;
    fetch(`{{ route('admin.security-dashboard.activity-log') }}?timeframe=${timeframe}&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.activities) {
                // Update the activity log section
                updateActivityLogDisplay(data);
            }
        })
        .catch(error => console.error('Error refreshing activity log:', error));
}

function updateActivityLogDisplay(data) {
    const tbody = document.querySelector('tbody');
    if (!tbody) return;
    
    // Clear existing rows
    tbody.innerHTML = '';
    
    // Add new rows
    data.activities.forEach(activity => {
        const row = document.createElement('tr');
        row.className = 'text-slate-700 bg-slate-50/60 hover:bg-slate-100/60 transition-colors';
        
        const severityClass = {
            'critical': 'bg-red-50 text-red-700 border-red-200',
            'high': 'bg-amber-50 text-amber-700 border-amber-200',
            'medium': 'bg-blue-50 text-blue-700 border-blue-200',
            'low': 'bg-emerald-50 text-emerald-700 border-emerald-200',
        }[activity.severity] || 'bg-slate-100 text-slate-700 border-slate-200';
        
        row.innerHTML = `
            <td class="py-3 px-4">
                <div class="text-xs text-slate-500">${new Date(activity.created_at).toLocaleTimeString()}</div>
                <div class="text-xs text-slate-400">${new Date(activity.created_at).toLocaleDateString()}</div>
            </td>
            <td class="py-3 px-4">
                ${activity.user ? `
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center">
                            <span class="text-xs font-medium text-slate-600">
                                ${(activity.user.name || '?').charAt(0).toUpperCase()}
                            </span>
                        </div>
                        <span class="text-sm font-medium">${activity.user.name || 'Unknown'}</span>
                    </div>
                ` : '<span class="text-sm text-slate-500">System</span>'}
            </td>
            <td class="py-3 px-4">
                <div class="text-sm font-medium">${activity.event_type || 'Unknown'}</div>
                ${activity.description ? `<div class="text-xs text-slate-500 mt-1">${activity.description.substring(0, 60)}${activity.description.length > 60 ? '...' : ''}</div>` : ''}
            </td>
            <td class="py-3 px-4">
                <code class="text-xs bg-slate-100 px-2 py-1 rounded">${activity.ip_address || 'N/A'}</code>
            </td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center h-6 px-2 rounded text-xs font-medium ${severityClass}">
                    ${(activity.severity || 'info').charAt(0).toUpperCase() + (activity.severity || 'info').slice(1)}
                </span>
            </td>
            <td class="py-3 px-4">
                <button onclick="viewActivityDetails('{{ $activity->id }}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Details
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Update summary
    const summaryDiv = document.querySelector('.flex.items-center.justify-between.p-3.bg-slate-50');
    if (summaryDiv) {
        summaryDiv.innerHTML = `
            <div class="text-sm text-slate-600">
                Total Activities: <span class="font-semibold text-slate-900">${data.pagination ? data.pagination.total : 0}</span>
            </div>
            <div class="text-sm text-slate-600">
                Showing: <span class="font-semibold text-slate-900">${data.activities ? data.activities.length : 0}</span> recent activities
            </div>
        `;
    }
}

function viewActivityDetails(id) {
    console.log('viewActivityDetails called with ID:', id);
    if (!id) {
        console.error('Activity ID is required');
        return;
    }
    // This would open a modal or navigate to a detailed view
    window.open(`/admin/security-dashboard/event/${id}`, '_blank');
}

function viewAllActivities() {
    window.open(`{{ route('admin.security-dashboard.activity-log') }}`, '_blank');
}

// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
@endsection
