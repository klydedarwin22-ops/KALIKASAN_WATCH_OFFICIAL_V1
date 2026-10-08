<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Set up face login</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
            <section class="rounded-xl border border-green-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Enroll after ID verification</h3>
                <p class="mt-2 text-sm text-gray-600">Your National ID has been approved. Complete this randomized camera check to create an encrypted face template for future logins.</p>

                <form method="POST" action="{{ route('face.enroll.store') }}" enctype="multipart/form-data" class="mt-5">
                    @csrf
                    <x-face-challenge :actions="$faceChallenge" />

                    <label class="mt-5 flex items-start gap-3 text-sm text-gray-700">
                        <input type="checkbox" name="consent" value="1" required class="mt-1 rounded border-gray-300 text-green-700 focus:ring-green-600">
                        <span>I consent to storing an encrypted face template for login verification. Captured camera frames are used for this check and are not retained. The template will be deleted if my account is deleted or my citizen verification is revoked. I understand face matching can make mistakes and can contact an administrator for identity recovery.</span>
                    </label>
                    <x-input-error :messages="$errors->get('consent')" class="mt-2" />
                    <x-primary-button class="mt-5">Enroll face login</x-primary-button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
