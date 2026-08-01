<?php

namespace App\Services;

use App\Enums\CollectionMethod;
use App\Enums\SalePaymentType;
use App\Models\Collection;
use App\Models\MarketDebtSetting;
use App\Models\Sale;

/**
 * دين السوق على مستوى الشركة (بلا عملاء):
 * صافي = رصيد افتتاحي + مبيعات على الحساب − تحصيل نقدي (من تاريخ الافتتاح).
 * الشيكات خارج المعادلة. الصافي السالب = فائض تحصيل.
 */
class MarketDebtService
{
    /**
     * @return array{
     *     opening_amount: float,
     *     as_of_date: string,
     *     currency_code: string,
     *     notes: ?string,
     *     credit_sales_total: float,
     *     cash_collections_total: float,
     *     net_market_debt: float,
     *     is_over_collected: bool
     * }
     */
    public function summary(?MarketDebtSetting $setting = null): array
    {
        $setting ??= MarketDebtSetting::current();
        $asOf = $setting->as_of_date->toDateString();

        $creditSales = (float) Sale::query()
            ->where('payment_type', SalePaymentType::Credit->value)
            ->whereDate('sale_date', '>=', $asOf)
            ->sum('total_amount');

        $cashCollections = (float) Collection::query()
            ->where('method', CollectionMethod::Cash->value)
            ->whereDate('collection_date', '>=', $asOf)
            ->sum('amount');

        $opening = (float) $setting->opening_amount;
        $net = round($opening + $creditSales - $cashCollections, 2);

        return [
            'opening_amount' => round($opening, 2),
            'as_of_date' => $asOf,
            'currency_code' => $setting->currency_code ?: 'ILS',
            'notes' => $setting->notes,
            'credit_sales_total' => round($creditSales, 2),
            'cash_collections_total' => round($cashCollections, 2),
            'net_market_debt' => $net,
            'is_over_collected' => $net < 0,
        ];
    }
}
