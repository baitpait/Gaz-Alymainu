<?php

use App\Livewire\ProfilePage;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('manager can open profile page', function () {
    $user = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('الملف الشخصي')
        ->assertSee('تغيير كلمة المرور');
});

test('driver can open profile page', function () {
    $user = User::factory()->create(['role' => 'driver', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('تغيير كلمة المرور');
});

test('user can change own password from profile', function () {
    $user = User::factory()->create([
        'role' => 'driver',
        'is_active' => true,
        'password' => Hash::make('old-pass-1'),
    ]);

    Livewire::actingAs($user)
        ->test(ProfilePage::class)
        ->set('current_password', 'old-pass-1')
        ->set('password', 'new-pass-9')
        ->set('password_confirmation', 'new-pass-9')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $user->refresh();
    expect(Hash::check('new-pass-9', $user->password))->toBeTrue();
});

test('wrong current password is rejected', function () {
    $user = User::factory()->create([
        'role' => 'manager',
        'is_active' => true,
        'password' => Hash::make('correct-pass'),
    ]);

    Livewire::actingAs($user)
        ->test(ProfilePage::class)
        ->set('current_password', 'wrong-pass')
        ->set('password', 'new-pass-9')
        ->set('password_confirmation', 'new-pass-9')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});
