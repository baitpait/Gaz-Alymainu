<?php

use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Livewire\StockMovementList;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function makeStockWarehouse(string $name): Warehouse
{
    return Warehouse::query()->create([
        'name' => $name,
        'code' => 'SM-'.uniqid(),
        'type' => WarehouseType::Fixed,
        'is_active' => true,
    ]);
}

function makeStockMovement(Product $product, array $overrides = []): StockMovement
{
    $from = $overrides['from_warehouse_id'] ?? makeStockWarehouse('مخزن من '.uniqid())->id;
    $to = $overrides['to_warehouse_id'] ?? makeStockWarehouse('مخزن إلى '.uniqid())->id;

    return StockMovement::query()->create(array_merge([
        'type' => StockMovementType::Load,
        'from_warehouse_id' => $from,
        'to_warehouse_id' => $to,
        'product_id' => $product->id,
        'quantity' => 5,
        'moved_at' => '2026-07-15 10:00:00',
        'driver_user_id' => null,
        'notes' => null,
    ], $overrides));
}

test('stock movements page shows filter form', function () {
    $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('stock-movements.index'))
        ->assertOk()
        ->assertSee('تطبيق الفلاتر')
        ->assertSee('نوع الحركة')
        ->assertSee('من تاريخ الحركة');
});

test('stock movements list filters by type and product', function () {
    $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $productA = Product::factory()->create(['name' => 'صنف حركة أ']);
    $productB = Product::factory()->create(['name' => 'صنف حركة ب']);

    $load = makeStockMovement($productA, ['type' => StockMovementType::Load]);
    $adjust = makeStockMovement($productB, ['type' => StockMovementType::Adjustment]);

    Livewire::actingAs($user)
        ->test(StockMovementList::class)
        ->set('filterType', StockMovementType::Load->value)
        ->call('applyStockMovementFilters')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->id === (int) $load->id)
        ->call('clearStockMovementFilters')
        ->set('filterProductId', (string) $productB->id)
        ->call('applyStockMovementFilters')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->id === (int) $adjust->id);
});

test('stock movements list filters by warehouse and date range', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $product = Product::factory()->create(['name' => 'صنف تاريخ']);
    $warehouse = makeStockWarehouse('مخزن فلتر خاص');

    $early = makeStockMovement($product, [
        'type' => StockMovementType::PurchaseIn,
        'from_warehouse_id' => null,
        'to_warehouse_id' => $warehouse->id,
        'moved_at' => '2026-07-01 09:00:00',
    ]);
    $late = makeStockMovement($product, [
        'type' => StockMovementType::PurchaseIn,
        'from_warehouse_id' => null,
        'to_warehouse_id' => $warehouse->id,
        'moved_at' => '2026-07-20 09:00:00',
    ]);
    makeStockMovement($product, [
        'type' => StockMovementType::Adjustment,
        'moved_at' => '2026-07-20 11:00:00',
    ]);

    Livewire::actingAs($user)
        ->test(StockMovementList::class)
        ->set('filterWarehouseId', (string) $warehouse->id)
        ->set('filterDateFrom', '2026-07-10')
        ->set('filterDateTo', '2026-07-31')
        ->call('applyStockMovementFilters')
        ->assertViewHas('rows', function ($rows) use ($late, $early) {
            return $rows->count() === 1
                && (int) $rows->first()->id === (int) $late->id
                && ! $rows->contains('id', $early->id);
        });
});
