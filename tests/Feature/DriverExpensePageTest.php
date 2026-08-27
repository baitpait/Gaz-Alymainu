<?php

use App\Enums\DriverExpenseCategory;
use App\Enums\SalePaymentType;
use App\Enums\WarehouseType;
use App\Livewire\DriverExpensePage;
use App\Models\DriverExpense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CashBoxService;
use Livewire\Livewire;

test('driver expenses page shows all-drivers option for accountant', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $this->actingAs($accountant)
        ->get(route('driver-expenses.index'))
        ->assertOk()
        ->assertSee('كل السائقين', false)
        ->assertSee('— اختر السائق —', false);
});

test('all drivers filter lists expenses from every driver with names', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driverA = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق ألف']);
    $driverB = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق باء']);

    DriverExpense::query()->create([
        'driver_user_id' => $driverA->id,
        'amount' => 10,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Fuel,
        'expense_date' => now()->toDateString(),
        'spent_at' => now(),
        'recorded_by_user_id' => $accountant->id,
        'notes' => 'مصروف ألف',
    ]);

    DriverExpense::query()->create([
        'driver_user_id' => $driverB->id,
        'amount' => 25,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Other,
        'expense_date' => now()->toDateString(),
        'spent_at' => now()->subMinute(),
        'recorded_by_user_id' => $accountant->id,
        'notes' => 'مصروف باء',
    ]);

    Livewire::actingAs($accountant)
        ->test(DriverExpensePage::class)
        ->set('driverUserId', 'all')
        ->assertSee('سائق ألف')
        ->assertSee('سائق باء')
        ->assertSee('مصروف ألف')
        ->assertSee('مصروف باء')
        ->assertSee('إجمالي المصروفات (حسب الفلتر)')
        ->assertSee('اختر سائقًا محددًا من القائمة')
        ->assertDontSee('الرصيد النقدي المتاح')
        ->assertDontSeeHtml('wire:click="save"');
});

test('expense filters narrow history by category and notes', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    DriverExpense::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 10,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Fuel,
        'expense_date' => now()->toDateString(),
        'spent_at' => now(),
        'notes' => 'بنزين طريق',
    ]);

    DriverExpense::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 40,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Maintenance,
        'expense_date' => now()->toDateString(),
        'spent_at' => now()->subMinute(),
        'notes' => 'صيانة فرامل',
    ]);

    Livewire::actingAs($accountant)
        ->test(DriverExpensePage::class)
        ->set('driverUserId', (string) $driver->id)
        ->set('filterCategory', 'fuel')
        ->set('filterSearch', 'بنزين')
        ->call('applyExpenseFilters')
        ->assertSee('بنزين طريق')
        ->assertDontSee('صيانة فرامل');
});

test('accountant can update driver expense amount and notes', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = Warehouse::query()->create([
        'name' => 'سيارة تعديل مصروف '.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);
    $product = Product::factory()->withIlsPricing()->create();

    Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Cash,
        'quantity' => 1,
        'unit_price' => 100,
        'total_amount' => 100,
        'currency_code' => 'ILS',
        'sale_date' => now()->toDateString(),
        'sold_at' => now(),
    ]);

    $expense = DriverExpense::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 20,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Fuel,
        'expense_date' => now()->toDateString(),
        'spent_at' => now(),
        'notes' => 'قديم',
    ]);

    Livewire::actingAs($accountant)
        ->test(DriverExpensePage::class)
        ->set('driverUserId', (string) $driver->id)
        ->call('startEdit', $expense->id)
        ->set('editAmount', '35')
        ->set('editCategory', 'maintenance')
        ->set('editNotes', 'محدّث')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $expense->refresh();
    expect((float) $expense->amount)->toBe(35.0)
        ->and($expense->category)->toBe(DriverExpenseCategory::Maintenance)
        ->and($expense->notes)->toBe('محدّث')
        ->and(app(CashBoxService::class)->balance($driver->id))->toBe(65.0);
});

test('driver cannot edit driver expenses', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $expense = DriverExpense::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 10,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Other,
        'expense_date' => now()->toDateString(),
        'spent_at' => now(),
    ]);

    Livewire::actingAs($driver)
        ->test(DriverExpensePage::class)
        ->call('startEdit', $expense->id)
        ->assertForbidden();
});

test('specific driver still shows form and balance cards', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق واحد']);

    Livewire::actingAs($accountant)
        ->test(DriverExpensePage::class)
        ->set('driverUserId', (string) $driver->id)
        ->assertSee('تسجيل مصروف')
        ->assertSee('الرصيد النقدي المتاح')
        ->assertDontSee('إجمالي مصروفات كل السائقين');
});

test('accountant can soft-delete driver expense and cash balance is restored', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $warehouse = Warehouse::query()->create([
        'name' => 'سيارة مصروف '.uniqid(),
        'type' => WarehouseType::Vehicle,
        'assigned_user_id' => $driver->id,
        'is_active' => true,
    ]);
    $product = Product::factory()->withIlsPricing()->create();

    Sale::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'driver_user_id' => $driver->id,
        'payment_type' => SalePaymentType::Cash,
        'quantity' => 1,
        'unit_price' => 100,
        'total_amount' => 100,
        'currency_code' => 'ILS',
        'sale_date' => now()->toDateString(),
        'sold_at' => now(),
        'recorded_by_user_id' => $accountant->id,
    ]);

    $expense = DriverExpense::query()->create([
        'driver_user_id' => $driver->id,
        'warehouse_id' => $warehouse->id,
        'amount' => 30,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Fuel,
        'expense_date' => now()->toDateString(),
        'spent_at' => now(),
        'recorded_by_user_id' => $accountant->id,
        'notes' => 'بنزين',
    ]);

    $cash = app(CashBoxService::class);
    expect($cash->balance($driver->id))->toBe(70.0);

    Livewire::actingAs($accountant)
        ->test(DriverExpensePage::class)
        ->set('driverUserId', (string) $driver->id)
        ->call('deleteRecord', $expense->id)
        ->assertDispatched('toast');

    expect(DriverExpense::query()->find($expense->id))->toBeNull()
        ->and(DriverExpense::withTrashed()->find($expense->id)?->trashed())->toBeTrue()
        ->and($cash->balance($driver->id))->toBe(100.0);
});

test('driver cannot delete driver expenses', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $expense = DriverExpense::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 10,
        'currency_code' => 'ILS',
        'category' => DriverExpenseCategory::Other,
        'expense_date' => now()->toDateString(),
        'spent_at' => now(),
        'recorded_by_user_id' => $driver->id,
    ]);

    Livewire::actingAs($driver)
        ->test(DriverExpensePage::class)
        ->call('deleteRecord', $expense->id)
        ->assertForbidden();

    expect(DriverExpense::query()->find($expense->id))->not->toBeNull();
});
