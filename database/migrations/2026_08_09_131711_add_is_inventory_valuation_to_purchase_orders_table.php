<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('is_inventory_valuation')->default(false)->after('inventory_posted_at');
        });

        // Mark existing valuation documents (notes start with تقييم مخزون)
        DB::table('purchase_orders')
            ->whereNull('deleted_at')
            ->where('notes', 'like', 'تقييم مخزون%')
            ->update(['is_inventory_valuation' => true]);
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('is_inventory_valuation');
        });
    }
};
