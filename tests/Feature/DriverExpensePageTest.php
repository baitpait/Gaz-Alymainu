<?php

use App\Enums\DriverExpenseCategory;
use App\Livewire\DriverExpensePage;
use App\Models\DriverExpense;
use App\Models\User;
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
        ->assertSee('إجمالي مصروفات كل السائقين')
        ->assertSee('اختر سائقًا محددًا من القائمة')
        ->assertDontSee('الرصيد النقدي المتاح')
        ->assertDontSeeHtml('wire:click="save"');
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
