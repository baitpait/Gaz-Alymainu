<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * إنشاء/تعديل سائق: حساب دخول (users بدور driver) + ملف موظف مرتبط.
 */
class DriverForm extends Component
{
    public ?int $userId = null;

    public string $full_name = '';

    public string $email = '';

    public string $phone_primary = '';

    public string $password = '';

    public bool $is_active = true;

    public function mount(?User $driver = null): void
    {
        abort_unless(Gate::allows('manage-drivers'), 403);

        if ($driver && $driver->exists) {
            abort_unless($driver->role === 'driver', 404);
            $this->userId = $driver->id;
            $this->full_name = $driver->full_name;
            $this->email = $driver->email;
            $this->is_active = (bool) $driver->is_active;
            $this->phone_primary = $driver->employee?->phone_primary ?? '';
        }
    }

    public function save(): void
    {
        abort_unless(Gate::allows('manage-drivers'), 403);

        $this->validate([
            'full_name' => 'required|string|max:255',
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'phone_primary' => 'nullable|string|max:30',
            'password' => $this->userId ? 'nullable|string|min:6' : 'required|string|min:6',
            'is_active' => 'boolean',
        ], [], [
            'full_name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone_primary' => 'الهاتف',
            'password' => 'كلمة المرور',
        ]);

        $wasEditing = $this->userId !== null;

        DB::transaction(function () use ($wasEditing) {
            $userData = [
                'full_name' => $this->full_name,
                'email' => $this->email,
                'role' => 'driver',
                'is_active' => $this->is_active,
            ];

            if (trim($this->password) !== '') {
                $userData['password'] = Hash::make($this->password);
            }

            if ($wasEditing) {
                $user = User::query()->findOrFail($this->userId);
                $user->update($userData);
            } else {
                $user = User::query()->create($userData);
            }

            Employee::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $this->full_name,
                    'phone_primary' => trim($this->phone_primary) !== '' ? trim($this->phone_primary) : null,
                    'email' => $this->email,
                    'job_title' => 'سائق',
                    'is_active' => $this->is_active,
                    'recorded_by_user_id' => auth()->id(),
                ],
            );
        });

        session()->flash('toast', $wasEditing ? 'تم تحديث السائق' : 'تم إضافة السائق');

        $this->redirect(route('drivers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.driver-form');
    }
}
