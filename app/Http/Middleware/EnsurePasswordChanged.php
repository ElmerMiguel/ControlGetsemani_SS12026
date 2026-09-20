<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->must_change_password) {
            if (! $request->routeIs('password.cambiar', 'password.cambiar.update', 'logout')) {
                return redirect()->route('password.cambiar');
            }
        }

        return $next($request);
    }
}
