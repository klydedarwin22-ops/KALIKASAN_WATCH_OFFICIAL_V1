{{-- Edit Report: Edit form with pre-populated data and map --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center space-x-2">
            <a href="{{ route('reports.show', $report) }}" class="text-green-600 hover:text-green-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <span>{{ __('Edit Report') }} #{{ $report->id }}</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:p-8">

                <form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Title --}}
                    <div>
                        <x-input-label for="title" :value="__('Report Title')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                                      :value="old('title', $report->title)" required />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    {{-- Category & Severity --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="category" :value="__('Category')" />
                            <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500" required>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ old('category', $report->category) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('category')" />
                        </div>

                        <div>
                            <x-input-label for="severity" :value="__('Impact Severity')" />
                            <p class="mt-1 text-xs text-gray-500">Level: Low, Medium, or High. Separate from report status.</p>
                            <select id="severity" name="severity" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500" required>
                                @foreach($severities as $severity)
                                    <option value="{{ $severity }}" {{ old('severity', $report->severity) === $severity ? 'selected' : '' }}>{{ ucfirst($severity) }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('severity')" />
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="5"
                                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500"
                                  required>{{ old('description', $report->description) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    </div>

                    {{-- Location Map --}}
                    <div>
                        <x-input-label :value="__('Location (click on the map to update)')" />
                        <div id="location-map" class="mt-1 w-full h-72 rounded-lg border border-gray-300 z-0"></div>
                        <div class="mt-2 flex space-x-4">
                            <div class="flex-1">
                                <x-input-label for="latitude" :value="__('Latitude')" />
                                <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full bg-gray-50"
                                              :value="old('latitude', $report->latitude)" required readonly />
                            </div>
                            <div class="flex-1">
                                <x-input-label for="longitude" :value="__('Longitude')" />
                                <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full bg-gray-50"
                                              :value="old('longitude', $report->longitude)" required readonly />
                            </div>
                        </div>
                    </div>

                    {{-- Current Image --}}
                    @if($report->image_path)
                        <div>
                            <x-input-label :value="__('Current Photo')" />
                            <img src="{{ asset('storage/' . $report->image_path) }}" alt="Current photo" class="mt-1 max-h-48 rounded-lg border border-gray-200">
                        </div>
                    @endif

                    {{-- Image Upload --}}
                    <div>
                        <x-input-label for="image" :value="__('Replace Photo (optional, max 5MB)')" />
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('reports.show', $report) }}" class="px-6 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                            Update Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function initEditMap() {
            const lat = parseFloat(document.getElementById('latitude').value);
            const lng = parseFloat(document.getElementById('longitude').value);

            const map = new google.maps.Map(document.getElementById('location-map'), {
                center: { lat: lat, lng: lng },
                zoom: 15,
                mapTypeId: 'hybrid',
                mapTypeControl: true,
            });

            const marker = new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map,
                draggable: true,
                title: 'Drag to update location',
            });

            marker.addListener('dragend', function () {
                const pos = marker.getPosition();
                document.getElementById('latitude').value = pos.lat().toFixed(7);
                document.getElementById('longitude').value = pos.lng().toFixed(7);
            });

            map.addListener('click', function (e) {
                marker.setPosition(e.latLng);
                document.getElementById('latitude').value = e.latLng.lat().toFixed(7);
                document.getElementById('longitude').value = e.latLng.lng().toFixed(7);
            }); 
        }

        if (window.GOOGLE_MAPS_LOADED) { initEditMap(); }
        else { window.addEventListener('google-maps-ready', initEditMap); }
    </script>
    @endpush
</x-app-layout>
