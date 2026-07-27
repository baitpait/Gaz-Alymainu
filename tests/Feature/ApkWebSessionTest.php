<?php

use App\Models\User;
use App\Services\DriverLocationService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
});

test('apk bootstrap creates one-time session url for driver', function () {
    $response = $this->postJson('/api/apk/bootstrap-session', [
        'email' => $this->driver->email,
        'password' => 'password',
        'remember' => true,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['session_code', 'session_url', 'token', 'user' => ['id', 'email']]);

    expect($response->json('token'))->not->toBeEmpty()
        ->and(app(DriverLocationService::class)->isSharing($this->driver->id))->toBeTrue()
        ->and(Cache::has('apk_web_login:'.$response->json('session_code')))->toBeTrue();
});

test('apk bootstrap works for non-driver without sanctum token', function () {
    $response = $this->postJson('/api/apk/bootstrap-session', [
        'email' => $this->manager->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('token', null)
        ->assertJsonStructure(['session_code', 'session_url']);
});

test('apk session consume logs user in via full page redirect', function () {
    $bootstrap = $this->postJson('/api/apk/bootstrap-session', [
        'email' => $this->driver->email,
        'password' => 'password',
    ])->json();

    $this->get($bootstrap['session_url'])
        ->assertRedirect(route('pos.index'));

    $this->assertAuthenticatedAs($this->driver);
    expect(Cache::has('apk_web_login:'.$bootstrap['session_code']))->toBeFalse();
});

test('apk session code cannot be reused', function () {
    $bootstrap = $this->postJson('/api/apk/bootstrap-session', [
        'email' => $this->driver->email,
        'password' => 'password',
    ])->json();

    $this->get($bootstrap['session_url'])->assertRedirect(route('pos.index'));

    $this->post('/logout');

    $this->get($bootstrap['session_url'])
        ->assertRedirect(route('login'));
});

test('apk bootstrap rejects invalid credentials', function () {
    $this->postJson('/api/apk/bootstrap-session', [
        'email' => $this->driver->email,
        'password' => 'wrong-password',
    ])->assertUnauthorized();
});
