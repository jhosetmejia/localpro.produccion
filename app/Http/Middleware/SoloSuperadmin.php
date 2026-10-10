<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoloSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(Auth::check() && Auth::user()->es_superadmin, 403);
        return $next($request);
    }
}
