<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center space-x-2">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <span>{{ __('Dashboard') }}</span>
        </h2>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-100 via-gray-200 to-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status Overview Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                {{-- Total Reports --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Reports</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Pending --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Pending</p>
                            <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $stats['pending'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Investigating --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Investigating</p>
                            <p class="text-3xl font-bold text-blue-600 mt-1">{{ $stats['investigating'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Resolved --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Resolved</p>
                            <p class="text-3xl font-bold text-green-600 mt-1">{{ $stats['resolved'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Rejected --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Rejected</p>
                            <p class="text-3xl font-bold text-red-600 mt-1">{{ $stats['rejected'] }}</p>
                        </div>
                        <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Charts Row (admin/officer only) --}}
            @if(Auth::user()->isAdmin() || Auth::user()->isOfficer())
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Reports by Category --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Reports by Category</h3>
                    @if($reportsByCategory->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($reportsByCategory as $item)
                                @php
                                    $max = $reportsByCategory->max('count');
                                    $percentage = $max > 0 ? ($item->count / $max) * 100 : 0;
                                @endphp
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700">{{ \App\Models\Report::CATEGORIES[$item->category] ?? $item->category }}</span>
                                        <span class="font-medium text-gray-900">{{ $item->count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-green-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No reports yet.</p>
                    @endif
                </div>

                {{-- Reports by Barangay --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Reports by Barangay</h3>
                    @if($reportsByBarangay->isNotEmpty())
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach($reportsByBarangay as $item)
                                @php
                                    $max = $reportsByBarangay->max('count');
                                    $percentage = $max > 0 ? ($item->count / $max) * 100 : 0;
                                @endphp
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-700">{{ $item->barangay }}</span>
                                        <span class="font-medium text-gray-900">{{ $item->count }}</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2">
                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm">No reports yet.</p>
                    @endif
                </div>
            </div>
            @endif

            @if($officerAccountability->isNotEmpty() || Auth::user()->isAdmin())
                <section class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="border-b border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-800">Officer Accountability</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Officer</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Online Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Assigned Reports</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Completed</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">In Progress</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Overdue</th>
                                    @if(Auth::user()->isAdmin())
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($officerAccountability as $entry)
                                    <tr>
                                        <td class="px-5 py-4 text-sm">
                                            <div class="font-medium text-gray-900">{{ $entry->officer->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $entry->officer->phone ?? 'No phone number' }}</div>
                                            @if($entry->attention_count > 0)
                                                <div class="mt-2 text-xs font-medium text-amber-700">{{ $entry->attention_count }} report{{ $entry->attention_count === 1 ? '' : 's' }} require attention</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-sm">
                                            <span class="inline-flex items-center gap-2 font-medium {{ $entry->officer->is_online ? 'text-green-700' : 'text-red-700' }}">
                                                <span class="h-2.5 w-2.5 rounded-full {{ $entry->officer->is_online ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                                {{ $entry->officer->is_online ? 'Online' : 'Offline' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $entry->assigned_count }}</td>
                                        <td class="px-5 py-4 text-sm text-gray-700">{{ $entry->completed_count }}</td>
                                        <td class="px-5 py-4 text-sm text-gray-700">{{ $entry->in_progress_count }}</td>
                                        <td class="px-5 py-4 text-sm {{ $entry->overdue_count > 0 ? 'font-semibold text-red-700' : 'text-gray-700' }}">{{ $entry->overdue_count }}</td>
                                        @if(Auth::user()->isAdmin())
                                            <td class="px-5 py-4">
                                                <div class="flex flex-wrap gap-2 text-xs font-medium">
                                                    <a href="{{ route('admin.officers.history', $entry->officer) }}" class="text-green-700 hover:text-green-900">View Assignment History</a>
                                                    @if($entry->officer->phone)
                                                        <form method="POST" action="{{ route('admin.officers.notify', $entry->officer) }}">
                                                            @csrf
                                                            <button type="submit" class="text-blue-700 hover:text-blue-900">Notify Officer</button>
                                                        </form>
                                                    @else
                                                        <span class="text-gray-400" title="Officer must add a phone number">Notify Officer</span>
                                                    @endif
                                                    @if(config('services.twilio.supervisor_phone'))
                                                        <form method="POST" action="{{ route('admin.officers.escalate', $entry->officer) }}">
                                                            @csrf
                                                            <button type="submit" class="text-amber-700 hover:text-amber-900">Escalate to Supervisor</button>
                                                        </form>
                                                    @else
                                                        <span class="text-gray-400" title="Set SUPERVISOR_PHONE to enable escalation">Escalate to Supervisor</span>
                                                    @endif
                                                    <a href="{{ route('reports.index', ['assigned_to' => $entry->officer->id]) }}" class="text-indigo-700 hover:text-indigo-900">Reassign Report</a>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                    @if($entry->overdue_reports->isNotEmpty())
                                        <tr class="bg-amber-50/60">
                                            <td colspan="{{ Auth::user()->isAdmin() ? 7 : 6 }}" class="px-5 py-3">
                                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-amber-900">
                                                    @foreach($entry->overdue_reports as $report)
                                                        <a href="{{ route('reports.show', $report) }}" class="underline underline-offset-2">#{{ $report->id }} {{ $report->title }} · due {{ $report->estimated_due_at->format('M d, Y') }}</a>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="{{ Auth::user()->isAdmin() ? 7 : 6 }}" class="px-5 py-10 text-center text-sm text-gray-500">
                                            No officers found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif

            @if(Auth::user()->isOfficer())
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">Recently Assigned</h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @forelse($recentAssignedReports as $report)
                            <div class="px-6 py-4 flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ Str::limit($report->title, 60) }}</p>
                                    <div class="text-xs text-gray-500 space-y-1">
                                        <div>Reported: {{ $report->created_at->format('M d, Y h:i A') }}</div>
                                        <div>
                                            @if($report->status === 'resolved' && $report->updated_at)
                                                Resolved: {{ $report->updated_at->format('M d, Y h:i A') }}
                                            @else
                                                Resolution: Pending
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $report->status === 'investigating' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $report->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $report->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                    <a href="{{ route('reports.show', $report) }}" class="text-sm text-green-600 hover:text-green-800 font-medium">View</a>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-gray-500 text-sm">No assigned reports yet.</div>
                        @endforelse
                    </div>
                </div>
            @endif

            @if(Auth::user()->isAdmin() || Auth::user()->isOfficer() || Auth::user()->isCitizen())
                {{-- Recent Reports Table --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Recent Reports</h3>
                            <a href="{{ route('reports.index') }}" class="text-sm text-green-600 hover:text-green-800 font-medium">View All →</a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barangay</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Impact Severity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Status</th>
                                    @if(Auth::user()->isOfficer())
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Officer</th>
                                    @endif
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentReports as $report)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ Str::limit($report->title, 40) }}</div>
                                            @if($report->user)
                                                <div class="text-xs text-gray-500">by {{ $report->user->name }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $report->category_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $report->severity === 'high' ? 'bg-red-100 text-red-800' : ($report->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                                {{ ucfirst($report->severity) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $report->status === 'investigating' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $report->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $report->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ ucfirst($report->status) }}
                                            </span>
                                        </td>
                                        @if(Auth::user()->isOfficer())
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $report->assignedOfficer?->name ?? 'Unassigned' }}
                                            </td>
                                        @endif
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>{{ $report->created_at->format('M d, Y') }}</div>
                                            <div>{{ $report->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('reports.show', $report) }}" class="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ Auth::user()->isOfficer() ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="mt-2 text-sm">No reports found.</p>
                                            @if(Auth::user()->isCitizen())
                                                <a href="{{ route('reports.create') }}" class="mt-3 inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                                    Submit Your First Report
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
