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
        Schema::create('payment_tbl', function (Blueprint $table) {
            $table->id();

            $table->text('payment_id')->nullable();

            $table->text('user_id');
            $table->text('purchase_group_id');
            $table->text('user_id_menu');

            $table->string('payment_method');

            $table->text('voucher_used_group_id')->nullable();
            $table->decimal('voucher_total_discounted_amount', 30, 2)->default(0.00);

            $table->decimal('total_discounted_amount', 30, 2)
                ->default(0.00)
                ->comment('This column count only how many discount amount not included any computation');
            $table->decimal('total_amount', 30, 2)->default(0.00);

            $table->decimal('final_total_amount', 30, 2)->nullable()->default(null)
                ->comment('This column use only if voucher_used_group_id and voucher_total_discounted_amount have value. 
                                    the computation is (voucher_total_discounted_amount - total_amount) = final_total_amount');

            $table->decimal('money', 30, 2)->default(0.00);
            $table->decimal('change', 30, 2)->default(0.00);
            $table->string('status')->default('NOT PAID');

            // Date | Time
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_tbl');
    }
};
