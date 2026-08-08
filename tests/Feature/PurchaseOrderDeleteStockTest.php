<?php

use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Livewire\PurchaseOrderForm;
use App\Livewire\PurchaseOrderList;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Product;
use App\Services\InventoryService;
use Livewire\Livewire;

test('deleting posted purchase order reverses warehouse stock', function () {
    $manager = User::factory()->create(['is_active' => true, 'role' => 'manager']);
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::query()->create([
        'name' => 'مخزن حذف مشتريات',
        'type' => WarehouseType::Fixed,
        'is_active' => true,
    ]);
    $product = Product::factory()->withIlsPricing()->create([
        'name' => 'صنف حذف مشتريات',
        'is_stock_tracked' => true,
    ]);

    $this->actingAs($manager);

    Livewire::test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('receiving_warehouse_id', (string) $warehouse->id)
        ->set('lines.0.product_id', (string) $product->id)
        ->set('lines.0.quantity', '5')
        ->call('save')
        ->assertHasNoErrors();

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    expect($po->inventory_posted_at)->not->toBeNull()
        ->and(app(InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(5.0);

    Livewire::test(PurchaseOrderList::class)
        ->call('confirmDelete', $po->id)
        ->call('delete')
        ->assertDispatched('toast');

    expect(PurchaseOrder::query()->find($po->id))->toBeNull()
        ->and(PurchaseOrder::withTrashed()->find($po->id)?->trashed())->toBeTrue()
        ->and(app(InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(0.0);

    expect(StockMovement::query()
        ->where('type', StockMovementType::Adjustment)
        ->where('from_warehouse_id', $warehouse->id)
        ->where('product_id', $product->id)
        ->where('notes', 'like', 'إلغاء فاتورة مشتريات #%')
        ->exists())->toBeTrue();
});

test('deleting purchase order is blocked when stock was already sold', function () {
    $manager = User::factory()->create(['is_active' => true, 'role' => 'manager']);
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::query()->create([
        'name' => 'مخزن بيع بعد شراء',
        'type' => WarehouseType::Fixed,
        'is_active' => true,
    ]);
    $product = Product::factory()->withIlsPricing()->create([
        'name' => 'صنف نفد',
        'is_stock_tracked' => true,
    ]);

    $this->actingAs($manager);

    Livewire::test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('receiving_warehouse_id', (string) $warehouse->id)
        ->set('lines.0.product_id', (string) $product->id)
        ->set('lines.0.quantity', '3')
        ->call('save')
        ->assertHasNoErrors();

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    app(InventoryService::class)->adjustToQuantity($warehouse, $product, 0, $manager->id, 'تصفير للاختبار');

    Livewire::test(PurchaseOrderList::class)
        ->call('confirmDelete', $po->id)
        ->call('delete');

    expect(PurchaseOrder::query()->find($po->id))->not->toBeNull()
        ->and($po->fresh()->inventory_posted_at)->not->toBeNull();
});
