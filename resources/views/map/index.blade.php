{{-- Map View: Full-page Google Maps with report markers and filtering --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center space-x-2">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ __('Environmental Reports Map') }}</span>
        </h2>
    </x-slot>

    @push('styles')
    <style>
        #reports-map { height: calc(100vh - 180px); min-height: 500px; }
    </style>
    @endpush

    <div class="py-6 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                {{-- Map Legend --}}
                <div class="p-4 border-b border-gray-100 flex flex-wrap items-center gap-4 text-sm">
                    <span class="font-medium text-gray-700">Legend:</span>
                    <span class="flex items-center gap-1">
                        <span class="inline-block w-3 h-3 rounded-full bg-yellow-400"></span> Pending
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span> Investigating
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span> Resolved
                    </span>
                    <span class="ml-auto text-gray-500">{{ $reports->count() }} reports plotted</span>
                </div>
                <div id="reports-map" class="z-0"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function initMap() {
            const reports = @json($reports);

            const statusColors = {
                'pending':       '#eab308',
                'investigating': '#3b82f6',
                'resolved':      '#22c55e',
            };

            const map = new google.maps.Map(document.getElementById('reports-map'), {
                center: { lat: 18.3567, lng: 121.6323 },
                zoom: 13,
                mapTypeId: 'hybrid',
                mapTypeControl: true,
                mapTypeControlOptions: {
                    style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                    position: google.maps.ControlPosition.TOP_RIGHT,
                },
            });

            const bounds = new google.maps.LatLngBounds();
            const infoWindow = new google.maps.InfoWindow();

            reports.forEach(function (report) {
                const color = statusColors[report.status] || '#6b7280';
                const position = { lat: parseFloat(report.latitude), lng: parseFloat(report.longitude) };

                const marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: report.title,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: color,
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                        scale: 8,
                    },
                });

                const severity = report.severity.charAt(0).toUpperCase() + report.severity.slice(1);
                const status = report.status.charAt(0).toUpperCase() + report.status.slice(1);

                const content = `
                    <div style="min-width:200px;font-family:sans-serif;">
                        <h3 style="font-weight:600;font-size:14px;margin:0 0 4px;">${report.title}</h3>
                        <p style="font-size:12px;color:#6b7280;margin:0 0 4px;">${report.category} &middot; ${severity} severity</p>
                        <p style="font-size:12px;color:#6b7280;margin:0 0 8px;">Status: <strong>${status}</strong></p>
                        <a href="/reports/${report.id}" style="color:#16a34a;font-size:12px;font-weight:500;text-decoration:none;">View Report →</a>
                    </div>`;

                marker.addListener('click', function () {
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                });

                bounds.extend(position);
            });

            if (reports.length > 0) {
                map.fitBounds(bounds, { top: 30, right: 30, bottom: 30, left: 30 });
            }
        }

        if (window.GOOGLE_MAPS_LOADED) { initMap(); }
        else { window.addEventListener('google-maps-ready', initMap); }
    </script>
    @endpush
</x-app-layout>
