<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مصروفات السائق/السيارة (وقود، صيانة، ...): مستقلة عن المصروفات العامة.
     * تُنقِص من الرصيد النقدي لصندوق السائق (كاش أنفقه من الصندوق).
     */
    public function up(): void
    {
        Schema::create('driver_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3)->default('ILS');
            $table->enum('category', ['fuel', 'maintenance', 'other'])->default('other');
            $table->date('expense_date');
            $table->dateTime('spent_at');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['driver_user_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_expenses');
    }
};
