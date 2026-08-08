<?php

use App\Enums\SalePaymentType;
use App\Enums\WarehouseType;
use App\Livewire\SalesTerminal;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DailyPriceService;
use Livewire\Livewire;

test('credit sale requires notes and stores them', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = Warehouse::query()->create([
        'name' => 'سيارة ملاحظات '.uniqid(),
        'code' => 'CN-'.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);
    $product = Product::factory()->create(['name' => 'جرة اختبار ملاحظات']);

    StockBalance::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);
    app(DailyPriceService::class)->setPrice($product->id, now()->toDateString(), 40);

    Livewire::actingAs($driver)
        ->test(SalesTerminal::class)
        ->set('qty.'.$product->id, '1')
        ->set('price.'.$product->id, '40')
        ->call('beginCreditSale', $product->id)
        ->assertSet('awaitingCreditNotesFor', $product->id)
        ->call('sell', $product->id, 'credit')
        ->assertDispatched('toast')
        ->assertSet('awaitingCreditNotesFor', $product->id);

    expect(Sale::query()->count())->toBe(0);

    Livewire::actingAs($driver)
        ->test(SalesTerminal::class)
        ->set('qty.'.$product->id, '1')
        ->set('price.'.$product->id, '40')
        ->call('beginCreditSale', $product->id)
        ->set('creditNotes.'.$product->id, 'زبون الحي الشمالي')
        ->call('sell', $product->id, 'credit')
        ->assertSet('awaitingCreditNotesFor', null);

    $sale = Sale::query()->first();
    expect($sale)->not->toBeNull()
        ->and($sale->payment_type)->toBe(SalePaymentType::Credit)
        ->and($sale->notes)->toBe('زبون الحي الشمالي');
});
