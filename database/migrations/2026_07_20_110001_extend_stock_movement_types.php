<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إضافة نوعَي حركة: التحويل العام بين المخازن (transfer) والخروج بالبيع (sale_out).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('purchase_in','load','return','transfer','sale_out','adjustment') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('purchase_in','load','return','adjustment') NOT NULL");
    }
};
