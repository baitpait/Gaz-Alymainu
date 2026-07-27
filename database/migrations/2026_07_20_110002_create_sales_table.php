<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * توثيق بيع مبسّط: بلا اسم زبون. يخصم من مخزون السيارة بسعر اليوم.
     * payment_type: نقدي (يدخل صندوق السائق) أو على الحساب.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('driver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('payment_type', ['cash', 'credit']);
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_price', 15, 4);
            $table->decimal('total_amount', 15, 4);
            $table->char('currency_code', 3)->default('ILS');
            $table->date('sale_date');
            $table->dateTime('sold_at');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['warehouse_id', 'sale_date']);
            $table->index(['driver_user_id', 'sale_date', 'payment_type']);
            $table->index(['sale_date', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
