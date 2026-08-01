<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إعدادات دين السوق الافتتاحي (سجل واحد للشركة).
     * صافي الدين = افتتاحي + مبيعات على الحساب − تحصيل نقدي (من تاريخ الافتتاح).
     */
    public function up(): void
    {
        if (Schema::hasTable('market_debt_settings')) {
            return;
        }

        Schema::create('market_debt_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('opening_amount', 15, 4)->default(0);
            $table->date('as_of_date');
            $table->char('currency_code', 3)->default('ILS');
            $table->text('notes')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_debt_settings');
    }
};
