<?php

use App\Enums\CollectionMethod;
use App\Enums\SalePaymentType;
use App\Enums\WarehouseType;
use App\Livewire\SaleList;
use App\Models\CashHandover;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DailyPriceService;
use App\Services\SalesService;
use Livewire\Livewire;

function makeEditSaleWarehouse(User $driver): Warehouse
{
    return Warehouse::query()->create([
        'name' => 'سيارة تعديل بيع '.uniqid(),
        'code' => 'ES-'.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);
}

test('accountant can update sale price and notes', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeEditSaleWarehouse($driver);
    $product = Product::factory()->create();

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    app(DailyPriceService::class)->setPrice($product->id, now()->toDateString(), 25);

    $sale = app(SalesService::class)->recordSale(
        $warehouse,
        $product,
        2,
        SalePaymentType::Credit,
        $accountant->id,
        null,
        25,
        'ملاحظة قديمة',
    );

    Livewire::actingAs($accountant)
        ->test(SaleList::class)
        ->call('startEdit', $sale->id)
        ->set('editQuantity', '2')
        ->set('editUnitPrice', '30')
        ->set('editNotes', 'ملاحظة آجل محدّثة')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $sale->refresh();
    expect((float) $sale->quantity)->toBe(2.0)
        ->and((float) $sale->unit_price)->toBe(30.0)
        ->and((float) $sale->total_amount)->toBe(60.0)
        ->and($sale->notes)->toBe('ملاحظة آجل محدّثة');
});

test('increasing sale quantity deducts extra stock from vehicle', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeEditSaleWarehouse($driver);
    $product = Product::factory()->create();

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $sale = app(SalesService::class)->recordSale(
        $warehouse,
        $product,
        2,
        SalePaymentType::Credit,
        $accountant->id,
        null,
        20,
    );

    expect((float) app(\App\Services\InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(8.0);

    app(SalesService::class)->updateSale($sale, 5, 20, 'كمية أكبر', $accountant->id);

    $sale->refresh();
    expect((float) $sale->quantity)->toBe(5.0)
        ->and((float) $sale->total_amount)->toBe(100.0)
        ->and((float) app(\App\Services\InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(5.0);
});

test('decreasing sale quantity restores stock to vehicle', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeEditSaleWarehouse($driver);
    $product = Product::factory()->create();

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $sale = app(SalesService::class)->recordSale(
        $warehouse,
        $product,
        4,
        SalePaymentType::Credit,
        $accountant->id,
        null,
        10,
    );

    app(SalesService::class)->updateSale($sale, 1, 10, null, $accountant->id);

    expect((float) $sale->fresh()->quantity)->toBe(1.0)
        ->and((float) app(\App\Services\InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(9.0);
});

test('cannot void cash sale after cash was handed over', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeEditSaleWarehouse($driver);
    $product = Product::factory()->create();

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $sale = app(SalesService::class)->recordSale(
        $warehouse,
        $product,
        1,
        SalePaymentType::Cash,
        $accountant->id,
        null,
        100,
    );

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cash,
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
        'received_by_user_id' => $accountant->id,
    ]);

    Livewire::actingAs($accountant)
        ->test(SaleList::class)
        ->call('deleteRecord', $sale->id);

    expect(Sale::query()->find($sale->id))->not->toBeNull();
});

test('cannot lower cash sale price below remaining driver cash', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeEditSaleWarehouse($driver);
    $product = Product::factory()->create();

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $sale = app(SalesService::class)->recordSale(
        $warehouse,
        $product,
        1,
        SalePaymentType::Cash,
        $accountant->id,
        null,
        100,
    );

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 80,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cash,
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
        'received_by_user_id' => $accountant->id,
    ]);

    // Remaining driver cash = 20; lowering price to 10 reduces total by 90 → blocked.
    expect(fn () => app(SalesService::class)->updateSale($sale, 1, 10, null))
        ->toThrow(\RuntimeException::class);
});

test('driver cannot open sale edit', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeEditSaleWarehouse($driver);
    $product = Product::factory()->create();

    $sale = Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Credit,
        'quantity' => 1,
        'unit_price' => 10,
        'total_amount' => 10,
        'currency_code' => 'ILS',
        'sale_date' => now()->toDateString(),
        'sold_at' => now(),
        'notes' => 'آجل',
    ]);

    Livewire::actingAs($driver)
        ->test(SaleList::class)
        ->call('startEdit', $sale->id)
        ->assertForbidden();
});
