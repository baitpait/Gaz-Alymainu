<?php

use App\Enums\SalePaymentType;
use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Livewire\SaleList;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DailyPriceService;
use App\Services\SalesService;
use Livewire\Livewire;

function makeVoidSaleWarehouse(User $driver): Warehouse
{
    return Warehouse::query()->create([
        'name' => 'سيارة إلغاء بيع '.uniqid(),
        'code' => 'VS-'.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);
}

test('accountant sees delete button on sales list', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('sales.index'))
        ->assertOk()
        ->assertSee('سجل المبيعات');
});

test('voiding a sale soft-deletes it and restores warehouse stock', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeVoidSaleWarehouse($driver);
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
        3,
        SalePaymentType::Cash,
        $accountant->id,
        null,
        25,
    );

    expect((float) app(\App\Services\InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(7.0);

    Livewire::actingAs($accountant)
        ->test(SaleList::class)
        ->call('deleteRecord', $sale->id)
        ->assertHasNoErrors();

    expect(Sale::query()->find($sale->id))->toBeNull()
        ->and(Sale::withTrashed()->find($sale->id)?->trashed())->toBeTrue()
        ->and((float) app(\App\Services\InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(10.0);

    expect(StockMovement::query()
        ->where('type', StockMovementType::Adjustment)
        ->where('to_warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->where('notes', 'إلغاء بيع #'.$sale->id)
        ->exists())->toBeTrue();
});

test('driver cannot void a sale', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = makeVoidSaleWarehouse($driver);
    $product = Product::factory()->create();

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

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
    ]);

    Livewire::actingAs($driver)
        ->test(SaleList::class)
        ->call('deleteRecord', $sale->id)
        ->assertForbidden();

    expect(Sale::query()->find($sale->id))->not->toBeNull();
});
