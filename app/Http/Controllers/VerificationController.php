<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Redirect;

class VerificationController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request)
    {
        $user = User::find($request->route('id'));

        if (! URL::hasValidSignature($request)) {
            return Redirect::to(config('app.frontend_url') . '/verify-email?error=invalid_signature');
        }

        if ($user->hasVerifiedEmail()) {
            return Redirect::to(config('app.frontend_url') . '/verify/success');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return Redirect::to(config('app.frontend_url') . '/verify/success');
    }

    /**
     * Resend the email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.'], 200);
    }
}
