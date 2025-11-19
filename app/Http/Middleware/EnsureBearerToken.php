<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBearerToken
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if ($authHeader && !str_starts_with(strtolower($authHeader), 'bearer ')) {
            $request->headers->set('Authorization', 'Bearer ' . trim($authHeader));
        }

        return $next($request);
    }
}

