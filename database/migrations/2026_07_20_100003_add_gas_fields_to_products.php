<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * حقول خاصة بأصناف الغاز:
     * - is_stock_tracked: هل يُتتبَّع مخزونه في المخازن (جرات الغاز = true).
     * - unit: وحدة القياس (جرة).
     * - capacity_kg: سعة الجرة بالكيلوغرام.
     * - category: تصنيف الصنف.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_stock_tracked')->default(false)->after('description');
            $table->string('unit', 32)->nullable()->after('is_stock_tracked');
            $table->decimal('capacity_kg', 8, 2)->nullable()->after('unit');
            $table->string('category', 64)->nullable()->after('capacity_kg');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_stock_tracked', 'unit', 'capacity_kg', 'category']);
        });
    }
};
