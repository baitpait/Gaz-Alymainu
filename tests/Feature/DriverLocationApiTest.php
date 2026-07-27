<?php

use App\Models\User;
use App\Services\DriverLocationService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
});

test('driver can login via api and receive sanctum token', function () {
    $response = $this->postJson('/api/driver/login', [
        'email' => $this->driver->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'full_name', 'email']]);

    expect(app(DriverLocationService::class)->isSharing($this->driver->id))->toBeTrue();
});

test('non driver cannot login via driver api', function () {
    $this->postJson('/api/driver/login', [
        'email' => $this->manager->email,
        'password' => 'password',
    ])->assertForbidden();
});

test('driver can post location with bearer token', function () {
    Sanctum::actingAs($this->driver, ['*']);

    $this->postJson('/api/driver/location', [
        'latitude' => 31.9038,
        'longitude' => 35.2034,
        'accuracy' => 12.5,
    ])->assertOk()->assertJson(['ok' => true]);

    $row = app(DriverLocationService::class)->latestFor($this->driver->id);
    expect($row)->not->toBeNull()
        ->and((float) $row->latitude)->toBe(31.9038)
        ->and($row->is_sharing)->toBeTrue();
});

test('authenticated driver can mint device token from web session', function () {
    $this->actingAs($this->driver)
        ->postJson(route('driver.device-token'))
        ->assertOk()
        ->assertJsonStructure(['token', 'api_base']);
});

test('driver logout api stops sharing', function () {
    Sanctum::actingAs($this->driver, ['*']);
    app(DriverLocationService::class)->startSharing($this->driver->id);

    $this->postJson('/api/driver/logout')->assertOk();

    expect(app(DriverLocationService::class)->isSharing($this->driver->id))->toBeFalse();
});
