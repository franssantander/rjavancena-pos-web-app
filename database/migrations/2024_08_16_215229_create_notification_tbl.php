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
        Schema::create('notification_tbl', function (Blueprint $table) {
            $table->id();
            $table->text('notification_id')->nullable();
            $table->text('user_id');
            $table->text('tbl_reference_id')->nullable();
            $table->text('name')->nullable();
            $table->longText('details');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_tbl');
    }
};
