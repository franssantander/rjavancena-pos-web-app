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
        Schema::create('expenses_image_tbl', function (Blueprint $table) {
            $table->id();
            $table->text('expenses_image_id')->nullable();
            $table->text('expenses_id');
            $table->text('user_id');
            $table->longText('file');
            $table->longText('original_name');
            $table->string('size');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses_image_tbl');
    }
};
