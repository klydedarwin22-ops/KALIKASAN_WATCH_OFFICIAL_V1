<x-guest-layout>
    <h1 class="mb-2 text-lg font-semibold text-gray-900">Verify it’s you</h1>
    <p class="mb-5 text-sm text-gray-600">Your password was accepted. Complete the camera check to finish signing in.</p>

    <form method="POST" action="{{ route('login.face.verify') }}" enctype="multipart/form-data">
        @csrf
        <x-face-challenge :actions="$faceChallenge" />
        <x-primary-button class="mt-5">Verify and log in</x-primary-button>
        <x-input-error :messages="$errors->get('email')" class="mt-3" />
    </form>
</x-guest-layout>
