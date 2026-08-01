<?php

use App\Enums\CollectionMethod;
use App\Enums\SalePaymentType;
use App\Enums\WarehouseType;
use App\Livewire\MarketDebtPage;
use App\Models\Collection;
use App\Models\MarketDebtSetting;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\MarketDebtService;
use Livewire\Livewire;

test('market debt page is available for accountant', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('market-debt.index'))
        ->assertOk()
        ->assertSee('دين السوق')
        ->assertSee('مجموع البيع على الحساب')
        ->assertSee('صافي دين السوق');
});

test('market debt page is forbidden for driver', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $this->actingAs($driver)
        ->get(route('market-debt.index'))
        ->assertRedirect(route('pos.index'));
});

test('market debt formula uses opening credit sales and cash collections only', function () {
    MarketDebtSetting::query()->delete();
    MarketDebtSetting::query()->create([
        'opening_amount' => 1000,
        'as_of_date' => '2026-08-01',
        'currency_code' => 'ILS',
    ]);

    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = Warehouse::query()->create([
        'name' => 'سيارة دين سوق',
        'code' => 'MD-'.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);
    $product = Product::factory()->create();

    Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Credit,
        'quantity' => 1,
        'unit_price' => 200,
        'total_amount' => 200,
        'currency_code' => 'ILS',
        'sale_date' => '2026-08-02',
        'sold_at' => '2026-08-02 10:00:00',
    ]);

    // نقدي — لا يدخل دين السوق
    Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Cash,
        'quantity' => 1,
        'unit_price' => 50,
        'total_amount' => 50,
        'currency_code' => 'ILS',
        'sale_date' => '2026-08-02',
        'sold_at' => '2026-08-02 11:00:00',
    ]);

    // قبل تاريخ الافتتاح — لا يدخل
    Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Credit,
        'quantity' => 1,
        'unit_price' => 999,
        'total_amount' => 999,
        'currency_code' => 'ILS',
        'sale_date' => '2026-07-20',
        'sold_at' => '2026-07-20 10:00:00',
    ]);

    Collection::query()->create([
        'driver_user_id' => $driver->id,
        'warehouse_id' => $warehouse->id,
        'method' => CollectionMethod::Cash,
        'amount' => 300,
        'currency_code' => 'ILS',
        'collection_date' => '2026-08-03',
        'collected_at' => '2026-08-03 12:00:00',
    ]);

    // شيك — خارج المعادلة
    Collection::query()->create([
        'driver_user_id' => $driver->id,
        'warehouse_id' => $warehouse->id,
        'method' => CollectionMethod::Cheque,
        'amount' => 500,
        'currency_code' => 'ILS',
        'cheque_number' => 'CH-1',
        'collection_date' => '2026-08-03',
        'collected_at' => '2026-08-03 13:00:00',
    ]);

    $summary = app(MarketDebtService::class)->summary();

    expect($summary['opening_amount'])->toBe(1000.0)
        ->and($summary['credit_sales_total'])->toBe(200.0)
        ->and($summary['cash_collections_total'])->toBe(300.0)
        ->and($summary['net_market_debt'])->toBe(900.0)
        ->and($summary['is_over_collected'])->toBeFalse();
});

test('market debt shows over-collection when cash exceeds opening plus credit', function () {
    MarketDebtSetting::query()->delete();
    MarketDebtSetting::query()->create([
        'opening_amount' => 100,
        'as_of_date' => '2026-08-01',
        'currency_code' => 'ILS',
    ]);

    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 250,
        'currency_code' => 'ILS',
        'collection_date' => '2026-08-05',
        'collected_at' => '2026-08-05 12:00:00',
    ]);

    $summary = app(MarketDebtService::class)->summary();

    expect($summary['net_market_debt'])->toBe(-150.0)
        ->and($summary['is_over_collected'])->toBeTrue();
});

test('accountant can save market debt opening settings', function () {
    MarketDebtSetting::query()->delete();
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(MarketDebtPage::class)
        ->set('openingAmount', '1500.50')
        ->set('asOfDate', '2026-08-01')
        ->set('notes', 'دين سوق قبل الإطلاق')
        ->call('saveOpening')
        ->assertHasNoErrors();

    $row = MarketDebtSetting::current();
    expect((float) $row->opening_amount)->toBe(1500.5)
        ->and($row->as_of_date->toDateString())->toBe('2026-08-01')
        ->and($row->notes)->toBe('دين سوق قبل الإطلاق')
        ->and($row->updated_by_user_id)->toBe($user->id);
});
