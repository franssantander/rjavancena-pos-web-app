<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers_used_tbl', function (Blueprint $table) {
            $table->id();
            $table->text('voucher_used_id')->nullable();
            $table->text('voucher_used_group_id')->nullable();
            $table->text('payment_id');
            $table->text('voucher_code');
            $table->decimal('discount_amount', 30, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers_used_tbl');
    }
};
