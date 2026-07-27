<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * سعر بيع الصنف لكل يوم (يتغيّر يوميًا). العملة الافتراضية شيكل (ILS).
     * فريد على (الصنف، التاريخ، العملة).
     */
    public function up(): void
    {
        Schema::create('product_daily_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('price_date');
            $table->char('currency_code', 3)->default('ILS');
            $table->decimal('sale_price', 15, 4);
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'price_date', 'currency_code'], 'product_daily_prices_unique');
            $table->index(['price_date', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_daily_prices');
    }
};
