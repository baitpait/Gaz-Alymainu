<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يقصر حساب السائق على نقطة البيع فقط.
 * أي محاولة للوصول لصفحة أخرى (حتى عبر الرابط) تُحوَّل إلى نقطة البيع.
 */
class RestrictDriverToSales
{
    /** المسارات المسموح بها للسائق. */
    private const ALLOWED = [
        'pos.index',
        'collections.index',
        'driver-expenses.index',
        'location.share',
        'profile',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDriver()) {
            $routeName = $request->route()?->getName();

            if (! in_array($routeName, self::ALLOWED, true)) {
                return redirect()->route('pos.index');
            }
        }

        return $next($request);
    }
}
