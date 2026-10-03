<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

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
        return view('verification.show', [
            'user' => $user,
        ]);
    }

    /**
     * Approve a user's barangay verification.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
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
        $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        // Remove the uploaded ID
        if ($user->barangay_id_path) {
            Storage::disk('public')->delete($user->barangay_id_path);
        }

        $user->update([
            'barangay_verified' => false,
            'barangay_id_path' => null,
            'verification_notes' => $request->notes,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return redirect()->route('verification.index')
            ->with('success', "Verification for {$user->name} has been rejected.");
    }

    /**
     * Allow a citizen to upload their Barangay ID for verification.
     */
    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'barangay_id' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ]);

        $user = auth()->user();

        // Remove old file if re-uploading
        if ($user->barangay_id_path) {
            Storage::disk('public')->delete($user->barangay_id_path);
        }

        $filename = 'barangay-ids/' . uniqid('bid_') . '.' . $request->file('barangay_id')->extension();
        $request->file('barangay_id')->storeAs('public', $filename);

        $user->update([
            'barangay_id_path' => $filename,
            'barangay_verified' => false, // Reset to pending on re-upload
            'verified_at' => null,
            'verified_by' => null,
            'verification_notes' => null,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Barangay ID uploaded successfully. An officer will review your verification soon.');
    }
}
