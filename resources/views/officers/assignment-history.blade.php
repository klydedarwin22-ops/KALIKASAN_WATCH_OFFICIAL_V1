<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assignment History: {{ $officer->name }}</h2>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-green-700 hover:text-green-900">Back to dashboard</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Report</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Previous Officer</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Assigned To</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Assigned By</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td class="px-5 py-4 text-sm">
                                        @if($assignment->report)
                                            <a href="{{ route('reports.show', $assignment->report) }}" class="font-medium text-green-700 hover:text-green-900">
                                                #{{ $assignment->report->id }} {{ $assignment->report->title }}
                                            </a>
                                        @else
                                            Deleted report
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $assignment->previousOfficer?->name ?? 'Initial assignment' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $assignment->assignedOfficer?->name ?? 'Unknown' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $assignment->assignedBy?->name ?? 'Historical record' }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $assignment->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">No assignment history.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($assignments->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4">{{ $assignments->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>