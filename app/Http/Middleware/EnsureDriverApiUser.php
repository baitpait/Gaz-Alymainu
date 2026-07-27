<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Business Purpose: يقيّد مسارات API على حسابات السائق النشطة فقط.
 */
class EnsureDriverApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isDriver() || ! $user->is_active) {
            return response()->json(['message' => 'غير مصرح — حساب سائق نشط مطلوب.'], 403);
        }

        return $next($request);
    }
}
