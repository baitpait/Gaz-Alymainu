<?php

use App\Models\User;

/**
 * Business Purpose: Guard the shared mobile shell so every authenticated page
 * scrolls inside main and reserves space for the fixed bottom nav.
 */
test('app shell exposes scrollable main for all roles', function () {
    $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    $this->actingAs($viewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('app-main-scroll', false)
        ->assertSee('app-shell', false)
        ->assertDontSee('has-mobile-bottom-nav', false);
});

test('drivers and accountants get mobile bottom-nav spacing class', function () {
    $driver = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $this->actingAs($driver)
        ->get(route('pos.index'))
        ->assertOk()
        ->assertSee('has-mobile-bottom-nav', false)
        ->assertSee('app-bottom-nav', false)
        ->assertSee('app-main-scroll', false);
});
