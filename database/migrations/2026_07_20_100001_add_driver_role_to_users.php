<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة دور «سائق» (driver) إلى أدوار المستخدمين.
     * السائق يملك حساب دخول لكنه ليس محاسبًا ولا مديرًا.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite stores ENUM as CHECK — rebuild as free string so 'driver' is allowed in tests.
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_new', 32)->default('viewer');
            });
            DB::table('users')->update(['role_new' => DB::raw('role')]);
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('role_new', 'role');
            });

            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('viewer','accountant','manager','driver') NOT NULL DEFAULT 'viewer'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('viewer','accountant','manager') NOT NULL DEFAULT 'viewer'");
    }
};
