{{-- Show Report: Full details, status management, officer assignment, and comments --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center space-x-2">
                <a href="{{ route('reports.index') }}" class="text-green-600 hover:text-green-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <span>Report #{{ $report->id }}</span>
            </h2>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                    {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $report->status === 'investigating' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $report->status === 'resolved' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $report->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                    Report status: {{ ucfirst($report->status) }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Report Details Card --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $report->title }}</h3>

                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $report->category_label }}
                            </span>
                            <div class="inline-flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-600">Impact Severity</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $report->severity === 'high' ? 'bg-red-100 text-red-800' : ($report->severity === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ ucfirst($report->severity) }}
                                </span>
                            </div>
                        </div>

                        <div class="prose prose-sm max-w-none text-gray-700 mb-6">
                            <p>{{ $report->description }}</p>
                        </div>

                        @if($report->status === 'rejected' && $report->rejection_reason)
                            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                                <h4 class="text-sm font-semibold text-red-800">Reason for rejection</h4>
                                <p class="mt-1 text-sm text-red-700">{{ $report->rejection_reason }}</p>
                            </div>
                        @endif

                        {{-- Image --}}
                        @if($report->image_path)
                            <div class="mb-6">
                                <img src="{{ asset('storage/' . $report->image_path) }}"
                                     alt="Report evidence photo"
                                     class="rounded-lg border border-gray-200 max-h-96 w-auto">
                            </div>
                        @endif

                        {{-- Location Map --}}
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-2">Location</h4>
                            <div id="report-map" class="w-full h-64 rounded-lg border border-gray-200 z-0"></div>
                            <p class="mt-1 text-xs text-gray-500">Coordinates: {{ $report->latitude }}, {{ $report->longitude }}</p>
                        </div>
                    </div>

                    {{-- Comments Section --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4">
                            Comments ({{ $report->comments->count() }})
                        </h4>

                        {{-- Comment List --}}
                        <div class="space-y-4 mb-6">
                            @forelse($report->comments as $comment)
                                <div class="flex space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <span class="text-green-700 text-xs font-bold">{{ strtoupper(substr($comment->user->name, 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-1 bg-gray-50 rounded-lg p-3">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium text-gray-900">{{ $comment->user->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-sm text-gray-700">{{ $comment->message }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">No comments yet. Be the first to comment.</p>
                            @endforelse
                        </div>

                        {{-- Add Comment Form --}}
                        @if(Auth::user()->isCitizen() || Auth::user()->isAdmin())
                            <form method="POST" action="{{ route('reports.comment', $report) }}" class="border-t border-gray-100 pt-4">
                                @csrf
                                <div>
                                    <textarea name="message" rows="3"
                                              class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm"
                                              placeholder="Write a comment..." required>{{ old('message') }}</textarea>
                                    <x-input-error class="mt-1" :messages="$errors->get('message')" />
                                </div>
                                <div class="mt-2 flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                        Post Comment
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">

                    {{-- Report Info Card --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="text-sm font-semibold text-gray-800 mb-4 uppercase tracking-wider">Report Details</h4>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs text-gray-500">Submitted by</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $report->user->name }}</dd>
                            </div>
                            @if($report->user->barangay)
                            <div>
                                <dt class="text-xs text-gray-500">Barangay</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $report->user->barangay }}</dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-xs text-gray-500">Submitted on</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $report->created_at->format('F d, Y \\a\\t h:i A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-500">Resolution Time</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    @if($report->status === 'resolved' && $report->updated_at)
                                        {{ $report->updated_at->format('F d, Y \\a\\t h:i A') }}
                                    @else
                                        Pending
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    @if(Auth::user()->isCitizen() || Auth::user()->isAdmin() || Auth::user()->isOfficer())
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h4 class="text-sm font-semibold text-gray-800 mb-4 uppercase tracking-wider">Completion Details</h4>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-xs text-gray-500">Estimated Days to Finish</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        @if($report->estimated_days_to_finish)
                                            {{ $report->estimated_days_to_finish }} day(s)
                                        @else
                                            Not set
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Completion Status</dt>
                                    <dd class="text-sm font-medium text-gray-900">
                                        {{ $report->is_completed ? 'Completed' : 'Not finished yet' }}
                                    </dd>
                                </div>
                                @if($report->completion_proof)
                                    <div>
                                        <dt class="text-xs text-gray-500">Completion Proof</dt>
                                        <dd class="mt-2">
                                            <img src="{{ asset('storage/' . $report->completion_proof) }}" alt="Completion proof" class="max-h-56 rounded-lg border border-gray-200 object-cover">
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    {{-- Officer/Admin Actions --}}
                    @if(Auth::user()->isAdmin() || (Auth::user()->isOfficer() && (! $report->assigned_to || $report->assigned_to === Auth::id())))
                        {{-- Update Status --}}
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-4 uppercase tracking-wider">Officer Actions</h4>
                            <form method="POST" action="{{ route('reports.update-status', $report) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')
                                <label for="report-status" class="block text-xs font-medium text-gray-600 mb-2">Report Status</label>
                                <select id="report-status" name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm mb-3">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ $report->status === $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="rejection_reason" class="block text-xs font-medium text-gray-600 mb-2">
                                    Rejection Reason (required when rejecting)
                                </label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="3" maxlength="1000"
                                          class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm mb-1"
                                          placeholder="Explain why this report is being rejected">{{ old('rejection_reason', $report->rejection_reason) }}</textarea>
                                @error('rejection_reason')
                                    <p class="text-xs text-red-600 mb-3">{{ $message }}</p>
                                @enderror

                                @if(Auth::user()->isOfficer())
                                    <label for="estimated_days_to_finish" class="block text-xs font-medium text-gray-600 mb-2">
                                        Estimated Days to Finish (1–5 days)
                                    </label>
                                    <select id="estimated_days_to_finish" name="estimated_days_to_finish"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm mb-3">
                                        <option value="">Select estimate</option>
                                        @foreach(range(1, 5) as $days)
                                            <option value="{{ $days }}" {{ (string) old('estimated_days_to_finish', $report->estimated_days_to_finish) === (string) $days ? 'selected' : '' }}>
                                                {{ $days }} {{ Str::plural('day', $days) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('estimated_days_to_finish')
                                        <p class="text-xs text-red-600 mb-3">{{ $message }}</p>
                                    @enderror

                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 mb-3">
                                        <input type="checkbox" name="is_completed" value="1" {{ old('is_completed', $report->is_completed ? '1' : '0') == '1' ? 'checked' : '' }}>
                                        Mark as complete
                                    </label>

                                    <label for="completion_proof" class="block text-xs font-medium text-gray-600 mb-2">
                                        Completion Proof Photo (single image only)
                                    </label>
                                    <input id="completion_proof"
                                           name="completion_proof"
                                           type="file"
                                           accept="image/*"
                                           class="w-full border border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm mb-3 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-medium file:text-blue-700">

                                    @if($report->completion_proof)
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 mb-3">
                                            <input type="checkbox" name="remove_completion_proof" value="1">
                                            Remove current proof photo
                                        </label>
                                    @endif

                                    @error('completion_proof')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                @endif

                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                    Update Status
                                </button>
                            </form>
                        </div>

                        {{-- Assign Officer --}}
                        @if(Auth::user()->isAdmin())
                            @if(count($officers) > 0)
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                    <h4 class="text-sm font-semibold text-gray-800 mb-4 uppercase tracking-wider">
                                        {{ $report->assigned_to ? 'Reassign Officer' : 'Assign Officer' }}
                                    </h4>
                                    <form method="POST" action="{{ route('reports.assign', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="assigned_to" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 text-sm mb-3">
                                            <option value="">Select officer</option>
                                            @foreach($officers as $officer)
                                                <option value="{{ $officer->id }}" {{ $report->assigned_to === $officer->id ? 'selected' : '' }}>
                                                    {{ $officer->name }} ({{ $officer->phone }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                                            {{ $report->assigned_to ? 'Reassign Officer' : 'Assign Officer' }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                    Officers must add a mobile number to their profile before they can receive assignment SMS notifications.
                                </div>
                            @endif
                        @endif
                    @endif

                    {{-- Actions (Edit/Delete) --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
                        @can('update', $report)
                            <a href="{{ route('reports.edit', $report) }}" class="block w-full text-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                                Edit Report
                            </a>
                        @endcan
                        @can('delete', $report)
                            <form method="POST" action="{{ route('reports.destroy', $report) }}"
                                  onsubmit="return confirm('Are you sure you want to delete this report? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-4 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 transition">
                                    Delete Report
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function initShowMap() {
            const lat = {{ $report->latitude }};
            const lng = {{ $report->longitude }};

            const map = new google.maps.Map(document.getElementById('report-map'), {
                center: { lat: lat, lng: lng },
                zoom: 15,
                mapTypeId: 'hybrid',
                mapTypeControl: true,
            });

            const marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                title: '{{ addslashes($report->title) }}',
            });

            const infoWindow = new google.maps.InfoWindow({
                content: '<strong>{{ addslashes($report->title) }}</strong>',
            });
            infoWindow.open(map, marker);
        }

        if (window.GOOGLE_MAPS_LOADED) { initShowMap(); }
        else { window.addEventListener('google-maps-ready', initShowMap); }
    </script>
    @endpush
</x-app-layout>
