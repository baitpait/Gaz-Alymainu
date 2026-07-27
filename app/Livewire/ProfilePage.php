<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Livewire\Component;

/**
 * Business Purpose: Let any signed-in user (manager, accountant, driver, viewer)
 * view their account basics and change their own password without admin help.
 */
class ProfilePage extends Component
{
    public string $full_name = '';

    public string $email = '';

    public string $role_label = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $user = auth()->user();
        $this->full_name = $user->full_name;
        $this->email = $user->email;
        $this->role_label = match ($user->role) {
            'manager' => 'مدير',
            'accountant' => 'محاسب',
            'driver' => 'سائق',
            default => 'مشاهد',
        };
    }

    /**
     * Business Purpose: Verify the current password then set a new one for the
     * authenticated user only — used from the profile screen on web and APK WebView.
     */
    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [], [
            'current_password' => 'كلمة المرور الحالية',
            'password' => 'كلمة المرور الجديدة',
            'password_confirmation' => 'تأكيد كلمة المرور',
        ]);

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'كلمة المرور الحالية غير صحيحة.');

            return;
        }

        $user->update(['password' => $this->password]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', message: 'تم تغيير كلمة المرور بنجاح', type: 'success');
    }

    public function render()
    {
        return view('livewire.profile-page');
    }
}
