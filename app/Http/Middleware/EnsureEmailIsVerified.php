<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() ||
            ($request->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&
            ! $request->user()->hasVerifiedEmail())) {
            
            // For API requests, return a JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your email address is not verified.',
                    'redirect_to' => config('app.frontend_url') . '/verify-email'
                ], 403);
            }

            // For web requests, redirect to the verification notice page
            return redirect(config('app.frontend_url') . '/verify-email');
        }

        return $next($request);
    }
}
