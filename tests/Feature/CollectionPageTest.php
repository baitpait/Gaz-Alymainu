<?php

use App\Enums\CollectionMethod;
use App\Livewire\CollectionPage;
use App\Models\CashHandover;
use App\Models\Collection;
use App\Models\User;
use App\Services\CashBoxService;
use Livewire\Livewire;

test('collections page shows all-drivers option for accountant', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $this->actingAs($accountant)
        ->get(route('collections.index'))
        ->assertOk()
        ->assertSee('كل السائقين', false)
        ->assertSee('— اختر السائق —', false)
        ->assertSee('التحصيل', false);
});

test('all drivers filter lists collections from every driver with names', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driverA = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق ألف']);
    $driverB = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق باء']);

    Collection::query()->create([
        'driver_user_id' => $driverA->id,
        'method' => CollectionMethod::Cash,
        'amount' => 40,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
        'recorded_by_user_id' => $accountant->id,
        'notes' => 'تحصيل ألف',
    ]);

    Collection::query()->create([
        'driver_user_id' => $driverB->id,
        'method' => CollectionMethod::Cheque,
        'amount' => 70,
        'currency_code' => 'ILS',
        'cheque_number' => 'CH-9',
        'collection_date' => now()->toDateString(),
        'collected_at' => now()->subMinute(),
        'recorded_by_user_id' => $accountant->id,
        'notes' => 'تحصيل باء',
    ]);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', 'all')
        ->assertSee('سائق ألف')
        ->assertSee('سائق باء')
        ->assertSee('تحصيل ألف')
        ->assertSee('تحصيل باء')
        ->assertSee('تحصيل نقدي (حسب الفلتر)')
        ->assertSee('اختر سائقًا محددًا من القائمة')
        ->assertDontSeeHtml('wire:click="save"');
});

test('collection filters narrow history by method and notes', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 20,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
        'notes' => 'كاش سوق',
    ]);

    Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cheque,
        'amount' => 55,
        'currency_code' => 'ILS',
        'cheque_number' => 'CH-55',
        'collection_date' => now()->toDateString(),
        'collected_at' => now()->subMinute(),
        'notes' => 'شيك تاجر',
    ]);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', (string) $driver->id)
        ->set('filterMethod', 'cash')
        ->set('filterSearch', 'سوق')
        ->call('applyCollectionFilters')
        ->assertSee('كاش سوق')
        ->assertDontSee('شيك تاجر');
});

test('accountant can update collection amount method notes and cheque number', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 80,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
        'notes' => 'قديم',
    ]);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', (string) $driver->id)
        ->call('startEdit', $collection->id)
        ->set('editAmount', '60')
        ->set('editMethod', 'cheque')
        ->set('editChequeNumber', 'CH-60')
        ->set('editNotes', 'محدّث')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $collection->refresh();
    expect((float) $collection->amount)->toBe(60.0)
        ->and($collection->method)->toBe(CollectionMethod::Cheque)
        ->and($collection->cheque_number)->toBe('CH-60')
        ->and($collection->notes)->toBe('محدّث')
        ->and(app(CashBoxService::class)->balance($driver->id))->toBe(0.0)
        ->and(app(CashBoxService::class)->chequeBalance($driver->id))->toBe(60.0);
});

test('driver cannot edit collections', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 10,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
    ]);

    Livewire::actingAs($driver)
        ->test(CollectionPage::class)
        ->call('startEdit', $collection->id)
        ->assertForbidden();
});

test('specific driver still shows form and today cards', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true, 'full_name' => 'سائق واحد']);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', (string) $driver->id)
        ->assertSee('تسجيل تحصيل')
        ->assertSee('تحصيل نقدي اليوم')
        ->assertDontSee('اختر سائقًا محددًا من القائمة');
});

test('accountant can soft-delete collection and cash balance drops', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 40,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
        'recorded_by_user_id' => $accountant->id,
        'notes' => 'كاش قابل للحذف',
    ]);

    $cash = app(CashBoxService::class);
    expect($cash->balance($driver->id))->toBe(40.0);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', (string) $driver->id)
        ->call('deleteRecord', $collection->id)
        ->assertDispatched('toast');

    expect(Collection::query()->find($collection->id))->toBeNull()
        ->and(Collection::withTrashed()->find($collection->id)?->trashed())->toBeTrue()
        ->and($cash->balance($driver->id))->toBe(0.0);
});

test('driver cannot delete collections', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 10,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
        'recorded_by_user_id' => $driver->id,
    ]);

    Livewire::actingAs($driver)
        ->test(CollectionPage::class)
        ->call('deleteRecord', $collection->id)
        ->assertForbidden();

    expect(Collection::query()->find($collection->id))->not->toBeNull();
});

test('cannot void cash collection after it was handed over', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 100,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
    ]);

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cash,
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
        'received_by_user_id' => $accountant->id,
    ]);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', (string) $driver->id)
        ->call('deleteRecord', $collection->id)
        ->assertDispatched('toast');

    expect(Collection::query()->find($collection->id))->not->toBeNull()
        ->and(app(CashBoxService::class)->balance($driver->id))->toBe(0.0);
});

test('cannot lower cash collection below remaining driver cash after handover', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cash,
        'amount' => 100,
        'currency_code' => 'ILS',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
    ]);

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 80,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cash,
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
        'received_by_user_id' => $accountant->id,
    ]);

    Livewire::actingAs($accountant)
        ->test(CollectionPage::class)
        ->set('driverUserId', (string) $driver->id)
        ->call('startEdit', $collection->id)
        ->set('editAmount', '70')
        ->set('editMethod', 'cash')
        ->call('saveEdit')
        ->assertHasErrors('editAmount');

    $collection->refresh();
    expect((float) $collection->amount)->toBe(100.0);
});

test('cannot void cheque collection after it was handed over', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $collection = Collection::query()->create([
        'driver_user_id' => $driver->id,
        'method' => CollectionMethod::Cheque,
        'amount' => 50,
        'currency_code' => 'ILS',
        'cheque_number' => 'CH-50',
        'collection_date' => now()->toDateString(),
        'collected_at' => now(),
    ]);

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 50,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cheque,
        'cheque_number' => 'CH-50',
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
        'received_by_user_id' => $accountant->id,
    ]);

    expect(fn () => app(CashBoxService::class)->voidCollection($collection))
        ->toThrow(RuntimeException::class);

    expect(Collection::query()->find($collection->id))->not->toBeNull();
});
