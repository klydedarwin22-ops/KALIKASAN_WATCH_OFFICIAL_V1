<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles barangay verification for citizen accounts.
 * Officers and admins can approve/reject verification requests.
 */
class VerificationController extends Controller
{
    /**
     * Show all pending verification requests (admin/officer view).
     */
    public function index(Request $request): View
    {
        $query = User::where('role', 'citizen')
            ->whereNotNull('barangay_id_path');

        // Filter by verification status
        $filter = $request->get('filter', 'pending');
        if ($filter === 'pending') {
            $query->where('barangay_verified', false);
        } elseif ($filter === 'verified') {
            $query->where('barangay_verified', true);
        }

        // Filter by barangay
        if ($request->filled('barangay')) {
            $query->where('barangay', $request->barangay);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('verification.index', [
            'users' => $users,
            'filter' => $filter,
            'selectedBarangay' => $request->barangay,
        ]);
    }

    /**
     * Show a single user's verification details.
     */
    public function show(User $user): View
    {
        abort_unless($this->hasStoredId($user), 404);

        return view('verification.show', [
            'user' => $user,
        ]);
    }

    /**
     * Display a citizen's verification ID to authorized reviewers.
     */
    public function id(User $user): BinaryFileResponse
    {
        abort_unless($this->hasStoredId($user), 404);

        return response()->file(Storage::disk('local')->path($user->barangay_id_path));
    }

    /**
     * Approve a user's barangay verification.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->hasStoredId($user) && ! $user->barangay_verified, 404);

        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'barangay_verified' => true,
            'verification_notes' => $request->notes,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return redirect()->route('verification.index')
            ->with('success', "Barangay residency for {$user->name} has been verified.");
    }

    /**
     * Reject a user's barangay verification.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_unless($this->hasStoredId($user) && ! $user->barangay_verified, 404);

        $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $idPath = $user->barangay_id_path;
        $selfiePath = $user->selfie_path;

        $user->update([
            'barangay_verified' => false,
            'barangay_id_path' => null,
            'selfie_path' => null,
            'face_template' => null,
            'verification_notes' => $request->notes,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        if ($idPath) {
            Storage::disk('local')->delete($idPath);
        }
        if ($selfiePath) {
            Storage::disk('local')->delete($selfiePath);
        }

        return redirect()->route('verification.index')
            ->with('success', "Verification for {$user->name} has been rejected.");
    }

    /**
     * Revoke an existing citizen verification and its face login enrollment.
     */
    public function revoke(User $user): RedirectResponse
    {
        abort_unless($user->isCitizen() && $user->barangay_verified, 404);

        $user->update([
            'barangay_verified' => false,
            'face_template' => null,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return redirect()->route('verification.show', $user)
            ->with('success', "Verification and face login were revoked for {$user->name}.");
    }

    /**
     * Allow a citizen to upload their National ID for verification.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'barangay_id' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $user = auth()->user();
        abort_unless($user->isCitizen() && ! $user->barangay_verified, 403);

        $previousIdPath = $user->barangay_id_path;
        $previousSelfiePath = $user->selfie_path;
        $filename = $request->file('barangay_id')->store('barangay-ids', 'local');
        if ($filename === false) {
            throw new \RuntimeException('Unable to store the citizenship verification ID.');
        }

        $user->update([
            'barangay_id_path' => $filename,
            'selfie_path' => null,
            'barangay_verified' => false, // Reset to pending on re-upload
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => null,
        ]);

        if ($previousIdPath && $previousIdPath !== $filename) {
            Storage::disk('local')->delete($previousIdPath);
        }
        if ($previousSelfiePath) {
            Storage::disk('local')->delete($previousSelfiePath);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Your National ID was submitted for staff review.');
    }

    private function hasStoredId(User $user): bool
    {
        return $user->isCitizen()
            && $user->barangay_id_path
            && Storage::disk('local')->exists($user->barangay_id_path);
    }

}
