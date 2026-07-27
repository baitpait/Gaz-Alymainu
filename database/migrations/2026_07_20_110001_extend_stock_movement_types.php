<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة نوعَي حركة: التحويل العام بين المخازن (transfer) والخروج بالبيع (sale_out).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('purchase_in','load','return','transfer','sale_out','adjustment') NOT NULL");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('purchase_in','load','return','adjustment') NOT NULL");
    }
};
