<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إضافة دور «سائق» (driver) إلى أدوار المستخدمين.
     * السائق يملك حساب دخول لكنه ليس محاسبًا ولا مديرًا.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('viewer','accountant','manager','driver') NOT NULL DEFAULT 'viewer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('viewer','accountant','manager') NOT NULL DEFAULT 'viewer'");
    }
};
