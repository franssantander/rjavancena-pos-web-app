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
        Schema::create('inventory_product_lost_image_tbl', function (Blueprint $table) {
            $table->id();

            $table->text('inventory_product_lost_image_id')->nullable();
            $table->text('inventory_product_lost_id');

            $table->text('image');


            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_product_lost_image_tbl');
    }
};
