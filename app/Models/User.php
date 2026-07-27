<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['full_name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isAccountant(): bool
    {
        return in_array($this->role, ['accountant', 'manager']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    /** ملف الموظف المرتبط بحساب الدخول (للسائقين). */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    /** السيارة (مخزن متحرك) المسندة لهذا المستخدم إن كان سائقًا. */
    public function assignedVehicle(): HasOne
    {
        return $this->hasOne(Warehouse::class, 'assigned_user_id')
            ->where('type', 'vehicle');
    }
}
