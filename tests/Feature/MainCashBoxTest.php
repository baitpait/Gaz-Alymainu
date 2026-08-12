<?php

use App\Enums\CollectionMethod;
use App\Models\CashHandover;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\SalaryPayment;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\CashBoxService;

/**
 * Business Purpose: Main admin cash/cheque box must net driver handovers + client
 * cash/cheque inflows against supplier, salary, and company expense outflows.
 */
test('main box nets cash and cheque client inflows against supplier salary and expenses', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $client = Client::factory()->create();
    $supplier = Supplier::factory()->create();
    $employee = Employee::factory()->create();

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 1000,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cash,
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
    ]);

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 400,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cheque,
        'cheque_number' => 'CH-1',
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 200,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 150,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'check',
    ]);

    SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 300,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 50,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'check',
    ]);

    SalaryPayment::query()->create([
        'employee_id' => $employee->id,
        'period_year' => (int) now()->year,
        'period_month' => (int) now()->month,
        'base_amount' => 100,
        'bonus_amount' => 0,
        'deduction_amount' => 0,
        'net_amount' => 100,
        'currency_code' => 'ILS',
        'status' => SalaryPayment::STATUS_PAID,
        'paid_at' => now()->toDateString(),
        'method' => 'cash',
    ]);

    Expense::query()->create([
        'description' => 'قرطاسية',
        'amount' => 40,
        'currency_code' => 'ILS',
        'expense_date' => now()->toDateString(),
    ]);

    // bank/transfer must not affect physical cash/cheque buckets
    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 999,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'bank',
    ]);
    SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 888,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'transfer',
    ]);

    $box = app(CashBoxService::class)->mainBoxByCurrency()['ILS'];

    // cash: 1000 + 200 - 300 - 100 - 40 = 760
    expect($box['cash'])->toBe(760.0)
        // cheque: 400 + 150 - 50 = 500
        ->and($box['cheque'])->toBe(500.0);
});

test('main box ledger lists inflows and outflows', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $client = Client::factory()->create(['business_name' => 'عميل صندوق']);
    $supplier = Supplier::factory()->create(['business_name' => 'مورد صندوق']);

    CashHandover::query()->create([
        'driver_user_id' => $driver->id,
        'amount' => 50,
        'currency_code' => 'ILS',
        'method' => CollectionMethod::Cash,
        'handover_date' => now()->toDateString(),
        'handed_at' => now(),
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 25,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 10,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    $ledger = app(CashBoxService::class)->mainBoxLedger();

    expect($ledger->pluck('type')->all())
        ->toContain('driver_handover', 'client_payment', 'supplier_payment');
});

test('financial summary page shows main box ledger heading', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('financial-summary'))
        ->assertOk()
        ->assertSee('الصناديق النقدية', false)
        ->assertSee('حركات الصندوق الرئيسي', false);
});
