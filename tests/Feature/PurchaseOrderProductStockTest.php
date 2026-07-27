<?php

use App\Enums\StockMovementType;
use App\Enums\WarehouseType;
use App\Livewire\PurchaseOrderForm;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Livewire\Livewire;

test('purchase order can select product and post stock on issue', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::query()->create([
        'name' => 'المخزن الرئيسي',
        'type' => WarehouseType::Fixed,
        'is_active' => true,
    ]);
    $product = Product::factory()->withIlsPricing()->create([
        'name' => 'جرة غاز 12',
        'is_stock_tracked' => true,
        'unit' => 'جرة',
    ]);

    $this->actingAs($user);

    Livewire::test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('receiving_warehouse_id', (string) $warehouse->id)
        ->set('lines.0.product_search', 'جرة')
        ->call('selectProductFromAutocomplete', 0, $product->id)
        ->assertSet('lines.0.product_id', (string) $product->id)
        ->assertSet('lines.0.title', 'جرة غاز 12')
        ->set('lines.0.quantity', '5')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('purchase-orders.index'));

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    expect($po)->not->toBeNull()
        ->and($po->inventory_posted_at)->not->toBeNull()
        ->and($po->receiving_warehouse_id)->toBe($warehouse->id)
        ->and($po->lines()->first()->product_id)->toBe($product->id);

    expect(app(InventoryService::class)->balanceFor($warehouse->id, $product->id))->toBe(5.0);

    $movement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('type', StockMovementType::PurchaseIn)
        ->first();
    expect($movement)->not->toBeNull()
        ->and((float) $movement->quantity)->toBe(5.0)
        ->and($movement->to_warehouse_id)->toBe($warehouse->id);
});

test('issued purchase with stocked product requires receiving warehouse', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->withIlsPricing()->create([
        'name' => 'صنف مخزون',
        'is_stock_tracked' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('receiving_warehouse_id', '')
        ->set('lines.0.product_id', (string) $product->id)
        ->set('lines.0.title', $product->name)
        ->set('lines.0.unit_price', '40')
        ->set('lines.0.quantity', '2')
        ->call('save')
        ->assertHasErrors(['receiving_warehouse_id']);

    expect(StockBalance::query()->count())->toBe(0);
});

test('free-text purchase line without product does not require warehouse', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    $this->actingAs($user);

    Livewire::test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('lines.0.title', 'مصروف عام')
        ->set('lines.0.unit_price', '100')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('purchase-orders.index'));

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    expect($po->inventory_posted_at)->toBeNull()
        ->and($po->lines()->first()->product_id)->toBeNull();
});
