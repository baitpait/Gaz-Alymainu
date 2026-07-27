<?php

use App\Enums\SalePaymentType;
use App\Enums\WarehouseType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DashboardSummaryService;
use Illuminate\Support\Carbon;

test('dashboard page shows gas ops sections for manager', function () {
    $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('اليوم — توزيع الغاز')
        ->assertSee('المخزون والأسطول')
        ->assertSee('ملخص مالي مختصر')
        ->assertDontSee('اختصارات تشغيلية')
        ->assertDontSee('يحتاج انتباه')
        ->assertDontSee('إجمالي الموردين');
});

test('dashboard summary includes today cash sales', function () {
    $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $product = Product::factory()->create(['is_stock_tracked' => true]);
    $warehouse = Warehouse::query()->create([
        'name' => 'سيارة لوحة',
        'code' => 'DASH-'.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);

    $today = Carbon::now()->toDateString();

    Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Cash,
        'quantity' => 2,
        'unit_price' => 50,
        'total_amount' => 100,
        'currency_code' => 'ILS',
        'sale_date' => $today,
        'sold_at' => $today.' 12:00:00',
    ]);

    $summary = app(DashboardSummaryService::class)->forDate($today);

    expect($summary['today']['sales_cash'])->toBe(100.0)
        ->and($summary['today']['sales_total'])->toBe(100.0)
        ->and($summary['today']['sales_count'])->toBe(1)
        ->and($summary['fleet']['active_drivers'])->toBe(1);
});

test('dashboard requires authentication', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
