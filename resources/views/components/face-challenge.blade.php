@props(['actions'])
@php($challengeId = 'face-challenge-' . \Illuminate\Support\Str::uuid())

<div id="{{ $challengeId }}" data-face-challenge data-actions="{{ json_encode($actions) }}" class="space-y-4">
    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
        <p>Allow camera access and follow each randomly ordered prompt. Keep your face centered and well lit.</p>
        <p class="mt-1 text-xs text-blue-800">The camera frames are processed by this app's server and are not saved. Face matching can make mistakes; contact an administrator if you cannot complete it.</p>
    </div>
    <p data-step-prompt class="font-medium text-gray-800" aria-live="polite"></p>
    <video data-camera-preview autoplay muted playsinline class="hidden max-h-80 w-full rounded-md bg-gray-100 object-contain"></video>
    <img data-captured-preview alt="Captured camera frame" class="hidden max-h-80 w-full rounded-md bg-gray-100 object-contain">
    <div class="flex flex-wrap gap-2">
        <button data-start-camera type="button" class="rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">Start camera</button>
        <button data-capture-frame type="button" class="hidden rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">Capture frame</button>
        <button data-next-step type="button" class="hidden rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Continue</button>
    </div>
    <p data-camera-status role="status" aria-live="polite" class="text-sm text-gray-600"></p>
    @foreach($errors->getMessages() as $field => $messages)
        @if($field === 'frames' || str_starts_with($field, 'frames.'))
            <x-input-error :messages="$messages" class="mt-2" />
        @endif
    @endforeach
</div>

@push('scripts')
<script>
    (() => {
        const capture = document.getElementById(@json($challengeId));
        const form = capture.closest('form');
        const actions = JSON.parse(capture.dataset.actions);
        const prompts = {
            face_left: 'Turn your face to your left.',
            blink: 'Blink once, then look at the camera.',
            face_right: 'Turn your face to your right.',
            face_center: 'Face forward and look directly at the camera.'
        };
        const video = capture.querySelector('[data-camera-preview]');
        const image = capture.querySelector('[data-captured-preview]');
        const prompt = capture.querySelector('[data-step-prompt]');
        const startButton = capture.querySelector('[data-start-camera]');
        const captureButton = capture.querySelector('[data-capture-frame]');
        const nextButton = capture.querySelector('[data-next-step]');
        const status = capture.querySelector('[data-camera-status]');
        let stream = null;
        let previewUrl = null;
        let step = 0;
        let frameCount = 0;
        prompt.textContent = `Frame 1 of ${actions.length}: ${prompts[actions[0]]}`;

        const stopCamera = () => {
            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
                stream = null;
            }
            video.srcObject = null;
        };

        startButton.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) {
                status.textContent = 'Camera access is unavailable. Use HTTPS or localhost in a browser with camera support.';
                return;
            }
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 960 } }
                });
                video.srcObject = stream;
                video.classList.remove('hidden');
                image.classList.add('hidden');
                startButton.classList.add('hidden');
                captureButton.classList.remove('hidden');
                status.textContent = 'Follow the prompt and hold still to capture.';
            } catch (error) {
                status.textContent = 'Camera access was not available. Allow camera permission and try again.';
            }
        });

        captureButton.addEventListener('click', () => {
            if (!video.videoWidth || !video.videoHeight) {
                status.textContent = 'Wait for the camera preview to appear, then try again.';
                return;
            }

            const scale = Math.min(1, 1280 / video.videoWidth, 960 / video.videoHeight);
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) {
                    status.textContent = 'The camera frame could not be captured. Please try again.';
                    return;
                }
                const file = new File([blob], `face-frame-${step + 1}.jpg`, { type: 'image/jpeg' });
                const transfer = new DataTransfer();
                transfer.items.add(file);
                const input = document.createElement('input');
                input.type = 'file';
                input.name = 'frames[]';
                input.hidden = true;
                input.files = transfer.files;
                form.appendChild(input);
                frameCount += 1;
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = URL.createObjectURL(blob);
                image.src = previewUrl;
                image.classList.remove('hidden');
                video.classList.add('hidden');
                captureButton.classList.add('hidden');
                startButton.classList.add('hidden');
                nextButton.classList.remove('hidden');
                status.textContent = 'Frame captured. Continue to the next prompt.';
                stopCamera();
            }, 'image/jpeg', 0.9);
        });

        nextButton.addEventListener('click', () => {
            step += 1;
            if (step >= actions.length) {
                nextButton.classList.add('hidden');
                status.textContent = 'All frames are ready. Submit to verify your face.';
                form.dataset.ready = 'true';
                return;
            }
            prompt.textContent = `Frame ${step + 1} of ${actions.length}: ${prompts[actions[step]]}`;
            image.classList.add('hidden');
            nextButton.classList.add('hidden');
            startButton.classList.remove('hidden');
        });

        form.addEventListener('submit', (event) => {
            if (frameCount !== actions.length || form.dataset.ready !== 'true') {
                event.preventDefault();
                status.textContent = 'Capture each requested frame before submitting.';
                startButton.focus();
            }
        });

        window.addEventListener('pagehide', stopCamera, { once: true });
    })();
</script>
@endpush
