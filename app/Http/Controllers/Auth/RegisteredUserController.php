<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Handles user registration with role and barangay fields.
 * Only citizen registration is public; officer/admin accounts are created by admins.
 */
class RegisteredUserController extends Controller
{
    /**
     * List of barangays in the Municipality of Apparri.
     */
    public const BARANGAYS = [
        'Bangag', 'Binalan', 'Bisagu', 'Bukig', 'Bulala Norte',
        'Bulala Sur', 'Caagaman', 'Centro 1 (Pob.)', 'Centro 2 (Pob.)',
        'Centro 3 (Pob.)', 'Centro 4 (Pob.)', 'Centro 5 (Pob.)',
        'Centro 6 (Pob.)', 'Centro 7 (Pob.)', 'Centro 8 (Pob.)',
        'Centro 9 (Pob.)', 'Centro 10 (Pob.)', 'Centro 11 (Pob.)',
        'Centro 12 (Pob.)', 'Centro 13 (Pob.)', 'Centro 14 (Pob.)',
        'Centro 15 (Pob.)', 'Dodan', 'Fugu', 'Gaddang',
        'Linao', 'Mabanguc', 'Macanaya', 'Maura',
        'Minanga', 'Navagan', 'Paddaya', 'Pagota',
        'Paruddun Norte', 'Paruddun Sur', 'Plaza', 'Punta',
        'San Antonio', 'San Jose', 'San Juan', 'San Rafael',
        'Sanja', 'Tallungan', 'Toran', 'Zinundungan',
    ];

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register', [
            'barangays' => self::BARANGAYS,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'barangay' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'citizen', // Public registration always creates citizens
            'barangay' => $request->barangay,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
