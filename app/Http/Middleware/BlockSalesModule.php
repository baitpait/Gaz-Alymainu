<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يحجب وحدة "المبيعات" (العملاء/الفواتير/دفعات العملاء/تسويات العملاء)
 * وتقاريرها البحتة عن كل المستخدمين بعد التحول إلى نشاط توزيع الغاز.
 *
 * الغرض: منع الوصول للمسارات المُخفاة من الواجهة حتى لو كُتب الرابط يدويًا (403).
 * لا يحذف بيانات ولا يعطّل المشتريات/الموردين.
 */
class BlockSalesModule
{
    /**
     * بادئات أسماء المسارات المحجوبة. أي مسار يبدأ بإحداها يُرفض.
     */
    private const BLOCKED_PREFIXES = [
        'clients.',
        'invoices.',
        'payments.',
        'client-adjustments.',
        'reports.sales',
        'reports.client-payments',
        'reports.client-receivables-aging',
        'reports.client-receivables-summary',
        'reports.aggregated-client-statements',
        'reports.client-adjustments',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($routeName !== null) {
            foreach (self::BLOCKED_PREFIXES as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    abort(403, 'هذه الوحدة غير مفعّلة.');
                }
            }
        }

        return $next($request);
    }
}
