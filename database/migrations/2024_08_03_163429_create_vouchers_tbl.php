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
        Schema::create('vouchers_tbl', function (Blueprint $table) {
            $table->id();
            $table->text('voucher_id')->nullable();
            $table->text('name');
            $table->decimal('discount_amount', 30, 2)->default(0.00);
            $table->timestamp('expiration_start_at')->nullable();
            $table->timestamp('expiration_end_at')->nullable();
            $table->string('status')->default('ACTIVATE');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers_tbl');
    }
};
