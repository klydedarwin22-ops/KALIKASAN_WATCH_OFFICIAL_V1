<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCitizenFaceEnrollment
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user?->isCitizen()
            && $user->barangay_verified
            && ! $user->face_template
            && ! $request->routeIs('face.enroll', 'face.enroll.store', 'logout')
        ) {
            return redirect()->route('face.enroll');
        }

        return $next($request);
    }
}
