{{-- Verification Review: View user details and approve/reject verification --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-2">
            <a href="{{ route('verification.index') }}" class="text-green-600 hover:text-green-800">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Verify: {{ $user->name }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- User Info Card --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">User Information</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider">Full Name</dt>
                        <dd class="text-sm font-medium text-gray-900 mt-1">{{ $user->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider">Email</dt>
                        <dd class="text-sm font-medium text-gray-900 mt-1">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider">Barangay</dt>
                        <dd class="text-sm font-medium text-gray-900 mt-1">{{ $user->barangay ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider">Registered</dt>
                        <dd class="text-sm font-medium text-gray-900 mt-1">{{ $user->created_at->format('F d, Y \\a\\t h:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider">Current Status</dt>
                        <dd class="mt-1">
                            @if($user->barangay_verified)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✓ Verified
                                </span>
                                @if($user->verifier)
                                    <span class="text-xs text-gray-500 ml-2">by {{ $user->verifier->name }} on {{ $user->verified_at->format('M d, Y') }}</span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending Verification
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase tracking-wider">Reports Submitted</dt>
                        <dd class="text-sm font-medium text-gray-900 mt-1">{{ $user->reports()->count() }}</dd>
                    </div>
                </dl>

                @if($user->verification_notes)
                    <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                        <dt class="text-xs text-gray-500 uppercase tracking-wider mb-1">Previous Notes</dt>
                        <dd class="text-sm text-gray-700">{{ $user->verification_notes }}</dd>
                    </div>
                @endif
            </div>

            {{-- Barangay ID Image --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Barangay ID / Certificate of Residency</h3>
                @if($user->barangay_id_path)
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <img src="{{ asset('storage/' . $user->barangay_id_path) }}"
                             alt="Barangay ID for {{ $user->name }}"
                             class="w-full max-h-[500px] object-contain bg-gray-50">
                    </div>
                @else
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">No Barangay ID has been uploaded by this user.</p>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            @if(!$user->barangay_verified)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Approve --}}
                <div class="bg-white rounded-xl shadow-sm border border-green-200 p-6">
                    <h4 class="text-sm font-semibold text-green-800 uppercase tracking-wider mb-3">Approve Verification</h4>
                    <form method="POST" action="{{ route('verification.approve', $user) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="block text-sm text-gray-700 mb-1">Notes (optional)</label>
                            <textarea name="notes" rows="2"
                                      class="w-full border-gray-300 rounded-lg text-sm focus:ring-green-500 focus:border-green-500"
                                      placeholder="e.g., ID verified, matches barangay records"></textarea>
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition"
                                onclick="return confirm('Approve barangay verification for {{ $user->name }}?')">
                            ✓ Approve
                        </button>
                    </form>
                </div>

                {{-- Reject --}}
                <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
                    <h4 class="text-sm font-semibold text-red-800 uppercase tracking-wider mb-3">Reject Verification</h4>
                    <form method="POST" action="{{ route('verification.reject', $user) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label class="block text-sm text-gray-700 mb-1">Reason (required)</label>
                            <textarea name="notes" rows="2" required
                                      class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500"
                                      placeholder="e.g., ID is blurry, does not match selected barangay"></textarea>
                            <x-input-error class="mt-1" :messages="$errors->get('notes')" />
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition"
                                onclick="return confirm('Reject verification for {{ $user->name }}? Their uploaded ID will be removed.')">
                            ✗ Reject
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                <p class="text-green-800 font-medium">This user's barangay residency has already been verified.</p>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
