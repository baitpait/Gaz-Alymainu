<?php

use App\Livewire\ProductForm;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
});

test('product form saves ILS pricing and defaults stock tracking on create', function () {
    $this->actingAs($this->accountant);

    Livewire::test(ProductForm::class)
        ->assertSet('is_stock_tracked', true)
        ->set('name', 'جرة غاز 12 كغ')
        ->set('product_code', 'GAS-12')
        ->set('unit', 'جرة')
        ->set('capacity_kg', '12')
        ->set('service_cost_price', '40')
        ->set('min_sale_price', '55')
        ->set('sale_price', '70')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.index'));

    $product = Product::query()->where('product_code', 'GAS-12')->first();
    expect($product)->not->toBeNull()
        ->and($product->is_stock_tracked)->toBeTrue()
        ->and($product->unit)->toBe('جرة')
        ->and((float) $product->capacity_kg)->toBe(12.0);

    $ils = ProductCurrencyPrice::query()
        ->where('product_id', $product->id)
        ->where('currency_code', 'ILS')
        ->first();

    expect($ils)->not->toBeNull()
        ->and((float) $ils->sale_price)->toBe(70.0)
        ->and((float) $ils->min_sale_price)->toBe(55.0)
        ->and((float) $ils->service_cost_price)->toBe(40.0);

    expect(
        ProductCurrencyPrice::query()->where('product_id', $product->id)->where('currency_code', '!=', 'ILS')->count()
    )->toBe(0);
});

test('product form requires sale price in shekels', function () {
    $this->actingAs($this->accountant);

    Livewire::test(ProductForm::class)
        ->set('name', 'منتج بدون سعر')
        ->set('service_cost_price', '10')
        ->set('min_sale_price', '10')
        ->set('sale_price', '')
        ->call('save')
        ->assertHasErrors(['sale_price']);

    expect(Product::query()->where('name', 'منتج بدون سعر')->exists())->toBeFalse();
});

test('product form rejects min sale above sale price', function () {
    $this->actingAs($this->accountant);

    Livewire::test(ProductForm::class)
        ->set('name', 'منتج أسعار خاطئة')
        ->set('service_cost_price', '10')
        ->set('min_sale_price', '100')
        ->set('sale_price', '50')
        ->call('save')
        ->assertHasErrors(['min_sale_price']);
});

test('product form updateOrCreate keeps non-ILS currency rows', function () {
    $this->actingAs($this->accountant);

    $product = Product::factory()->create(['name' => 'منتج متعدد']);
    ProductCurrencyPrice::query()->create([
        'product_id' => $product->id,
        'currency_code' => 'USD',
        'service_cost_price' => 1,
        'min_sale_price' => 2,
        'sale_price' => 3,
    ]);
    ProductCurrencyPrice::query()->create([
        'product_id' => $product->id,
        'currency_code' => 'ILS',
        'service_cost_price' => 10,
        'min_sale_price' => 20,
        'sale_price' => 30,
    ]);

    Livewire::test(ProductForm::class, ['product' => $product])
        ->set('sale_price', '99')
        ->set('min_sale_price', '80')
        ->set('service_cost_price', '50')
        ->call('save')
        ->assertHasNoErrors();

    expect(
        ProductCurrencyPrice::query()->where('product_id', $product->id)->where('currency_code', 'USD')->exists()
    )->toBeTrue();

    $ils = ProductCurrencyPrice::query()
        ->where('product_id', $product->id)
        ->where('currency_code', 'ILS')
        ->first();

    expect((float) $ils->sale_price)->toBe(99.0);
});
