<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تحصيل مبلغ (بلا ذمم/زبون): المبلغ + طريقة الدفع (نقدي/شيك) فقط.
     * التحصيل النقدي يدخل صندوق السائق؛ الشيك يُوثَّق منفصلًا.
     */
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->enum('method', ['cash', 'cheque']);
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3)->default('ILS');
            $table->string('cheque_number', 100)->nullable();
            $table->date('collection_date');
            $table->dateTime('collected_at');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['driver_user_id', 'collection_date', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
