<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('usuario.id')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
