<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business Purpose: يدعم نظام الأجر اليومي — يخزّن عدد أيام العمل وأجرة اليوم (لقطة)
 * حتى يُشتق الأساسي = daily_rate × worked_days. فارغة للموظفين الشهريين/بارت تايم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->unsignedTinyInteger('worked_days')->nullable()->after('base_amount');
            $table->decimal('daily_rate', 15, 4)->nullable()->after('worked_days');
        });
    }

    public function down(): void
    {
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn(['worked_days', 'daily_rate']);
        });
    }
};
