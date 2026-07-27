<?php

use App\Livewire\PurchaseOrderForm;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\PurchaseOrderPaymentAllocationService;
use Livewire\Livewire;

function poTestProduct(string $name = 'منتج اختبار'): Product
{
    return Product::factory()->withIlsPricing()->create([
        'name' => $name,
        'is_stock_tracked' => false,
    ]);
}

test('purchase order create shows payment collection when document is issued', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->assertSee('حالة الدفع');
});

test('creating issued unpaid purchase order does not create supplier payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();
    $product = poTestProduct('مواد طباعة');

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.product_id', (string) $product->id)
        ->set('lines.0.unit_price', '500')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('purchase-orders.index'));

    expect(PurchaseOrder::query()->where('supplier_id', $supplier->id)->count())->toBe(1);
    expect(SupplierPayment::query()->where('supplier_id', $supplier->id)->count())->toBe(0);

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po);
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::UNPAID);
});

test('creating issued paid purchase order creates full supplier payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();
    $product = poTestProduct('حبر');

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'paid')
        ->set('payment_method', 'cash')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.product_id', (string) $product->id)
        ->set('lines.0.unit_price', '800')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('purchase-orders.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect((float) $payment->amount)->toBe(800.0);

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po);
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::PAID);
});

test('creating issued partial purchase order creates partial supplier payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();
    $product = poTestProduct('ورق');

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'partial')
        ->set('payment_amount', '300')
        ->set('payment_method', 'transfer')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.product_id', (string) $product->id)
        ->set('lines.0.unit_price', '1000')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('purchase-orders.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect((float) $payment->amount)->toBe(300.0);

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po);
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::PARTIAL);
});

test('fifo allocation marks older purchase orders paid before newer ones', function () {
    $supplier = Supplier::factory()->create();

    $older = PurchaseOrder::query()->create([
        'supplier_id' => $supplier->id,
        'document_date' => now()->subDays(10)->format('Y-m-d'),
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'discount_amount' => 0,
        'status' => 'issued',
    ]);

    $newer = PurchaseOrder::query()->create([
        'supplier_id' => $supplier->id,
        'document_date' => now()->subDays(2)->format('Y-m-d'),
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'discount_amount' => 0,
        'status' => 'issued',
    ]);

    SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 500,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    $service = new PurchaseOrderPaymentAllocationService;
    expect($service->forPurchaseOrder($older)['status'])->toBe(PurchaseOrderPaymentAllocationService::PAID);
    expect($service->forPurchaseOrder($newer)['status'])->toBe(PurchaseOrderPaymentAllocationService::UNPAID);
});
