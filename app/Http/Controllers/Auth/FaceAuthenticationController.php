<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\FaceChallengeFailed;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FaceAuthenticationController extends Controller
{
    private const CHALLENGE_SESSION_KEY = 'face_auth_challenge';

    public function __construct(
        private readonly FaceRecognitionService $faceRecognition
    ) {}

    public function enrollForm(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCitizen() && $user->barangay_verified, 403);

        if ($user->face_template) {
            return redirect()->route('dashboard');
        }

        return view('auth.face-enroll', [
            'faceChallenge' => $this->challenge($request),
        ]);
    }

    public function enroll(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCitizen() && $user->barangay_verified, 403);

        if ($user->face_template) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'consent' => ['accepted'],
            'frames' => ['required', 'array', 'size:4'],
            'frames.*' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
        ]);

        $actions = $this->sessionChallenge($request);

        try {
            $frames = $this->faceRecognition->analyze(
                array_map(fn ($frame) => $frame->getRealPath(), $request->file('frames'))
            );
            $user->forceFill([
                'face_template' => $this->faceRecognition->validatedTemplate($frames, $actions),
            ])->save();
        } catch (FaceChallengeFailed $exception) {
            $this->refreshChallenge($request);

            throw ValidationException::withMessages([
                'frames' => $exception->getMessage(),
            ]);
        }

        $request->session()->forget(self::CHALLENGE_SESSION_KEY);

        return redirect()->route('dashboard')
            ->with('success', 'Face login is enabled for your verified account.');
    }

    public function challengeForm(Request $request): View|RedirectResponse
    {
        $user = $this->pendingFaceUser($request);
        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Sign in with your password to start face verification.']);
        }

        return view('auth.face-login', [
            'faceChallenge' => $this->challenge($request),
        ]);
    }

    public function verifyLogin(Request $request): RedirectResponse
    {
        $user = $this->pendingFaceUser($request);
        if (! $user) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your face verification session expired. Sign in again.']);
        }

        $request->validate([
            'frames' => ['required', 'array', 'size:4'],
            'frames.*' => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
        ]);

        $limiterKey = $this->limiterKey($request, $user);
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $this->clearPendingLogin($request);

            return redirect()->route('login')
                ->withErrors(['email' => 'Face verification is temporarily locked. Contact an administrator for identity recovery.']);
        }

        $actions = $this->sessionChallenge($request);

        try {
            $frames = $this->faceRecognition->analyze(
                array_map(fn ($frame) => $frame->getRealPath(), $request->file('frames'))
            );
            $matched = $this->faceRecognition->matches($user->face_template, $frames, $actions);
        } catch (FaceChallengeFailed $exception) {
            $matched = false;
            $failureMessage = $exception->getMessage();
        }

        if (! $matched) {
            RateLimiter::hit($limiterKey, 300);
            $this->refreshChallenge($request);

            if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
                $this->clearPendingLogin($request);

                return redirect()->route('login')
                    ->withErrors(['email' => 'Face verification failed too many times. Contact an administrator for identity recovery.']);
            }

            throw ValidationException::withMessages([
                'frames' => $failureMessage ?? 'The face did not match this account. Follow the new camera prompts and try again.',
            ]);
        }

        RateLimiter::clear($limiterKey);
        $remember = $request->session()->pull('pending_face_remember', false);
        $this->clearPendingLogin($request);
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function resetEnrollment(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isAdmin() && $user->isCitizen() && $user->barangay_verified, 404);

        $user->forceFill(['face_template' => null])->save();

        return redirect()->route('verification.show', $user)
            ->with('success', "Face login was reset for {$user->name}. They must sign in with their password and enroll again.");
    }

    private function pendingFaceUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_face_user_id');
        $expiresAt = $request->session()->get('pending_face_login_expires_at', 0);
        if (! $userId || $expiresAt <= now()->timestamp) {
            $this->clearPendingLogin($request);

            return null;
        }

        $user = User::find($userId);
        if (! $user?->isCitizen() || ! $user->barangay_verified || ! $user->face_template) {
            $this->clearPendingLogin($request);

            return null;
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function challenge(Request $request): array
    {
        $current = $request->session()->get(self::CHALLENGE_SESSION_KEY);
        if (is_array($current) && ($current['expires_at'] ?? 0) > now()->timestamp) {
            return $current['actions'];
        }

        $actions = ['face_left', 'blink', 'face_right', 'face_center'];
        for ($index = count($actions) - 1; $index > 0; $index--) {
            $swapIndex = random_int(0, $index);
            [$actions[$index], $actions[$swapIndex]] = [$actions[$swapIndex], $actions[$index]];
        }

        $request->session()->put(self::CHALLENGE_SESSION_KEY, [
            'actions' => $actions,
            'expires_at' => now()->addMinutes(3)->timestamp,
        ]);

        return $actions;
    }

    /**
     * @return list<string>
     */
    private function sessionChallenge(Request $request): array
    {
        $challenge = $request->session()->get(self::CHALLENGE_SESSION_KEY);
        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) <= now()->timestamp) {
            $this->refreshChallenge($request);

            throw ValidationException::withMessages([
                'frames' => 'The camera challenge expired. Follow the refreshed prompts and try again.',
            ]);
        }

        return $challenge['actions'];
    }

    private function refreshChallenge(Request $request): void
    {
        $request->session()->forget(self::CHALLENGE_SESSION_KEY);
        $this->challenge($request);
    }

    private function clearPendingLogin(Request $request): void
    {
        $request->session()->forget([
            'pending_face_user_id',
            'pending_face_remember',
            'pending_face_login_expires_at',
            self::CHALLENGE_SESSION_KEY,
        ]);
    }

    private function limiterKey(Request $request, User $user): string
    {
        return 'face-login:'.$user->id.'|'.$request->ip();
    }
}
