{{-- Create Report: Form with geolocation picker and image upload --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center space-x-2">
            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>{{ __('Submit Environmental Report') }}</span>
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:p-8">

                <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', 18.3567) }}">
                    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', 121.6323) }}">

                    {{-- Title --}}
                    <div>
                        <x-input-label for="title" :value="__('Barangay')" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')"
                                      required placeholder="barangay location Report" />
                        <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    </div>

                    {{-- Category --}}
                    <div>
                        <x-input-label for="category" :value="__('Category')" />
                        <select id="category" name="category" class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500" required>
                            <option value="">Select category</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('category')" />
                    </div>

                    {{-- Description --}}
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="5"
                                  class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500"
                                  required placeholder="Provide a detailed description of the environmental issue...">{{ old('description') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        <p class="mt-2 text-sm text-gray-600">Severity is estimated automatically from the category and description. The photo is not analyzed by an AI model.</p>
                    </div>

                    {{-- Location Map --}}
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <x-input-label :value="__('Report Location')" />
                            <button id="use-current-location" type="button" class="text-sm font-medium text-green-700 hover:text-green-900">
                                Use my current location
                            </button>
                        </div>
                        <p id="location-status" class="mt-1 text-sm text-gray-600" role="status" aria-live="polite">
                            Your browser will ask permission to pin your current location.
                        </p>
                        <div id="location-map" class="mt-1 w-full h-72 rounded-lg border border-gray-300 z-0"></div>
                        <div class="mt-2 flex space-x-4"></div>
                    </div>

                    {{-- Image Upload --}}
                    <div>
                        <x-input-label for="image" :value="__('Photo Evidence (optional, max 5MB)')" />
                        <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/jpg,image/webp"
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100" />
                        <x-input-error class="mt-2" :messages="$errors->get('image')" />
                        <div id="image-preview" class="mt-3 hidden">
                            <img id="preview-img" src="" alt="Preview" class="max-h-48 rounded-lg border border-gray-200">
                        </div>
                    </div>

                    {{-- Submit --}}
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('reports.index') }}" class="px-6 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                            Submit Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const locationStatus = document.getElementById('location-status');
        const hasPreviousLocation = @json(old('latitude') !== null && old('longitude') !== null);
        let reportMap;
        let reportMarker;
        let locationWasManuallySet = false;

        function locateUser(force = false) {
            if (!navigator.geolocation) {
                locationStatus.textContent = 'Location is unavailable in this browser. Choose a point on the map instead.';
                return;
            }

            locationStatus.textContent = 'Finding your current location...';
            navigator.geolocation.getCurrentPosition(function (position) {
                if (locationWasManuallySet && !force) return;

                const location = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };

                latitudeInput.value = location.lat.toFixed(7);
                longitudeInput.value = location.lng.toFixed(7);

                if (reportMap && reportMarker) {
                    reportMarker.setPosition(location);
                    reportMap.setCenter(location);
                    reportMap.setZoom(17);
                }

                locationStatus.textContent = 'Your current location is pinned. Drag the marker or click the map to adjust it.';
            }, function () {
                locationStatus.textContent = 'Could not access your location. Allow browser access or choose a point on the map.';
            }, {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 60000,
            });
        }

        function initCreateMap() {
            const defaultLat = parseFloat(latitudeInput.value) || 18.3567;
            const defaultLng = parseFloat(longitudeInput.value) || 121.6323;

            reportMap = new google.maps.Map(document.getElementById('location-map'), {
                center: { lat: defaultLat, lng: defaultLng },
                zoom: 14,
                mapTypeId: 'hybrid',
                mapTypeControl: true,
            });

            reportMarker = new google.maps.Marker({
                position: { lat: defaultLat, lng: defaultLng },
                map: reportMap,
                draggable: true,
                title: 'Drag to set report location',
            });

            reportMarker.addListener('dragend', function () {
                const position = reportMarker.getPosition();
                locationWasManuallySet = true;
                latitudeInput.value = position.lat().toFixed(7);
                longitudeInput.value = position.lng().toFixed(7);
                locationStatus.textContent = 'Report location set. Drag the marker or click the map to adjust it.';
            });

            reportMap.addListener('click', function (event) {
                reportMarker.setPosition(event.latLng);
                locationWasManuallySet = true;
                latitudeInput.value = event.latLng.lat().toFixed(7);
                longitudeInput.value = event.latLng.lng().toFixed(7);
                locationStatus.textContent = 'Report location set. Drag the marker or click the map to adjust it.';
            });

            if (!hasPreviousLocation) locateUser();
        }

        if (window.GOOGLE_MAPS_LOADED) { initCreateMap(); }
        else { window.addEventListener('google-maps-ready', initCreateMap); }

        document.getElementById('use-current-location').addEventListener('click', function () {
            locationWasManuallySet = false;
            locateUser(true);
        });

        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');

        imageInput.addEventListener('change', function (event) {
            const file = event.target.files[0];

            if (!file) {
                imagePreview.classList.add('hidden');
                previewImg.src = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                previewImg.src = event.target.result;
                imagePreview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    </script>
    @endpush
</x-app-layout>
