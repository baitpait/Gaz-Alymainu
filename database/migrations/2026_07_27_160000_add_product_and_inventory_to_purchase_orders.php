<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط بنود المشتريات بالمنتجات + مخزن الاستلام وعلامة ترحيل المخزون.
     */
    public function up(): void
    {
        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('products')
                ->nullOnDelete();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('receiving_warehouse_id')
                ->nullable()
                ->after('recorded_by_user_id')
                ->constrained('warehouses')
                ->nullOnDelete();
            $table->timestamp('inventory_posted_at')
                ->nullable()
                ->after('receiving_warehouse_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receiving_warehouse_id');
            $table->dropColumn('inventory_posted_at');
        });

        Schema::table('purchase_order_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
