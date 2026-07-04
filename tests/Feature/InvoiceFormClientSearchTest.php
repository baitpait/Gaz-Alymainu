<?php

use App\Livewire\InvoiceForm;
use App\Livewire\InvoiceList;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

test('invoice form add line survives whole lines array sync', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Livewire::actingAs($accountant)
        ->test(InvoiceForm::class)
        ->call('addLine')
        ->assertCount('lines', 2);
});

test('invoice form filters clients by search text', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا للإعلام', 'phone_primary' => '+972-50-111']);
    Client::factory()->create(['business_name' => 'متجر بيتا', 'phone_primary' => '+972-52-222']);

    Livewire::actingAs($accountant)
        ->test(InvoiceForm::class)
        ->set('clientSearch', 'ألفا')
        ->assertSee('شركة ألفا للإعلام')
        ->assertDontSee('متجر بيتا');
});

test('invoice list modal filters clients by phone in search', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'عميل نافذة', 'phone_primary' => '0599887766']);
    Client::factory()->create(['business_name' => 'عميل آخر', 'phone_primary' => '0500111222']);

    Livewire::actingAs($accountant)
        ->test(InvoiceList::class)
        ->call('openCreate')
        ->set('clientSearch', '0599887766')
        ->assertSee('عميل نافذة')
        ->assertDontSee('عميل آخر');
});
