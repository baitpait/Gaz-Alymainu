<?php

use App\Enums\SalePaymentType;
use App\Enums\WarehouseType;
use App\Livewire\SaleList;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function makeSaleWarehouse(?User $driver = null): Warehouse
{
    return Warehouse::query()->create([
        'name' => 'سيارة اختبار '.uniqid(),
        'code' => 'WH-'.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver?->id,
        'is_active' => true,
    ]);
}

function makeSaleRow(Warehouse $warehouse, Product $product, array $overrides = []): Sale
{
    return Sale::query()->create(array_merge([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $warehouse->assigned_user_id,
        'payment_type' => SalePaymentType::Cash,
        'quantity' => 1,
        'unit_price' => 10,
        'total_amount' => 10,
        'currency_code' => 'ILS',
        'sale_date' => '2026-07-15',
        'sold_at' => '2026-07-15 10:00:00',
    ], $overrides));
}

test('sales list page shows filter form', function () {
    $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertSee('تطبيق الفلاتر')
        ->assertSee('نوع الدفع')
        ->assertSee('من تاريخ البيع');
});

test('sales list filters by payment type and product', function () {
    $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeSaleWarehouse($driver);

    $productA = Product::factory()->create(['name' => 'أسطوانة فلتر أ']);
    $productB = Product::factory()->create(['name' => 'أسطوانة فلتر ب']);

    makeSaleRow($warehouse, $productA, [
        'payment_type' => SalePaymentType::Cash,
    ]);
    makeSaleRow($warehouse, $productB, [
        'payment_type' => SalePaymentType::Credit,
    ]);

    Livewire::actingAs($user)
        ->test(SaleList::class)
        ->set('filterPaymentType', 'cash')
        ->call('applySaleFilters')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->product_id === (int) $productA->id)
        ->call('clearSaleFilters')
        ->set('filterProductId', (string) $productB->id)
        ->call('applySaleFilters')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->product_id === (int) $productB->id);
});

test('sales list filters by date range and search', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق البحث']);
    $warehouse = makeSaleWarehouse($driver);
    $productEarly = Product::factory()->create(['name' => 'منتج مبكر']);
    $productLate = Product::factory()->create(['name' => 'منتج متأخر']);

    makeSaleRow($warehouse, $productEarly, [
        'sale_date' => '2026-07-01',
        'sold_at' => '2026-07-01 09:00:00',
    ]);
    makeSaleRow($warehouse, $productLate, [
        'sale_date' => '2026-07-20',
        'sold_at' => '2026-07-20 09:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(SaleList::class)
        ->set('filterDateFrom', '2026-07-10')
        ->set('filterDateTo', '2026-07-31')
        ->call('applySaleFilters')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->product_id === (int) $productLate->id)
        ->call('clearSaleFilters')
        ->set('search', 'سائق البحث')
        ->call('applySaleFilters')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 2);
});
