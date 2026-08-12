<?php

use App\Models\User;

test('financial summary page renders for authenticated user', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('financial-summary'))
        ->assertOk()
        ->assertSee('الصناديق النقدية', false)
        ->assertSee('حركات الصندوق الرئيسي', false);
});

test('financial summary page requires authentication', function () {
    $this->get(route('financial-summary'))
        ->assertRedirect(route('login'));
});
