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

            {{-- National ID document --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">National ID</h3>
                <img src="{{ route('verification.id', $user) }}"
                     alt="National ID for {{ $user->name }}"
                     class="w-full max-h-[500px] rounded-lg border border-gray-200 object-contain bg-gray-50">
            </div>

            {{-- Action Buttons --}}
            @if(!$user->barangay_verified && $user->barangay_id_path)
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
            @elseif(!$user->barangay_verified)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-800">
                    Approval is unavailable until the citizen submits an ID document.
                </div>
            @else
                <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
                    <p class="text-green-800 font-medium">This user's barangay residency has already been verified.</p>
                </div>
                @if(auth()->user()->isAdmin() && $user->face_template)
                    <div class="rounded-xl border border-amber-200 bg-white p-6">
                        <h4 class="text-sm font-semibold uppercase tracking-wider text-amber-800">Face login recovery</h4>
                        <p class="mt-2 text-sm text-gray-600">Resetting removes this citizen's encrypted face template. They will need to enroll again after signing in with their password.</p>
                        <form method="POST" action="{{ route('admin.citizens.face.reset', $user) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700" onclick="return confirm('Reset face login for this citizen?')">
                                Reset face login
                            </button>
                        </form>
                    </div>
                @endif
                <div class="rounded-xl border border-red-200 bg-white p-6">
                    <h4 class="text-sm font-semibold uppercase tracking-wider text-red-800">Revoke verification</h4>
                    <p class="mt-2 text-sm text-gray-600">Revoking will remove citizen verification and delete the enrolled face template.</p>
                    <form method="POST" action="{{ route('verification.revoke', $user) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700" onclick="return confirm('Revoke verification and face login for this citizen?')">
                            Revoke verification
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
