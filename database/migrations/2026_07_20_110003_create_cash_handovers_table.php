<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تسليم نقدية من صندوق السائق إلى الصندوق الرئيسي (آخر اليوم).
     * رصيد صندوق السائق = مبيعات نقدية − إجمالي التسليمات.
     */
    public function up(): void
    {
        Schema::create('cash_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3)->default('ILS');
            $table->date('handover_date');
            $table->dateTime('handed_at');
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['driver_user_id', 'handover_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_handovers');
    }
};
