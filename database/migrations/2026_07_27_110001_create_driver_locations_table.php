<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business Purpose: يخزّن آخر موقع معروف لكل سائق أثناء مشاركة الوردية
 * لعرضه على خريطة لوحة الإدارة في الوقت شبه الحيّ.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->boolean('is_sharing')->default(false);
            $table->dateTime('recorded_at')->nullable();
            $table->timestamps();

            $table->unique('driver_user_id');
            $table->index(['is_sharing', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
