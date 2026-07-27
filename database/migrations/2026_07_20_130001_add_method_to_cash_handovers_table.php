<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * السحب من السائق قد يكون كاش أو شيك.
     * الكاش يدخل الرصيد النقدي للصندوق الرئيسي، والشيك يُوثَّق كحركة مالية منفصلة.
     */
    public function up(): void
    {
        Schema::table('cash_handovers', function (Blueprint $table) {
            $table->enum('method', ['cash', 'cheque'])->default('cash')->after('currency_code');
            $table->string('cheque_number')->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('cash_handovers', function (Blueprint $table) {
            $table->dropColumn(['method', 'cheque_number']);
        });
    }
};
