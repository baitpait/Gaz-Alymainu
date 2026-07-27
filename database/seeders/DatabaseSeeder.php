<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * بذور التطوير: مستخدم مدير بصلاحيات كاملة (محاسب + حذف المنتجات وغيره).
     * لا يُنشئ حسابًا في الإنتاج إلا إذا وضعت SEED_DEV_ADMIN=true في البيئة.
     *
     * بيانات تجريبية: SEED_DEMO_DATA=true مع بيئة محلية أو SEED_DEV_ADMIN=true.
     * أو شغّل مباشرة: php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        $allowDevAdmin = app()->isLocal()
            || filter_var(env('SEED_DEV_ADMIN', false), FILTER_VALIDATE_BOOLEAN);

        if ($allowDevAdmin) {
            $email = env('DEV_ADMIN_EMAIL', 'admin@gaz.local');
            $password = env('DEV_ADMIN_PASSWORD', 'password');

            User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => env('DEV_ADMIN_NAME', 'مدير النظام'),
                    'password' => Hash::make($password),
                    'role' => 'manager',
                    'is_active' => true,
                ]
            );
        }

        $seedDemo = filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN);

        if ($seedDemo && $allowDevAdmin) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
