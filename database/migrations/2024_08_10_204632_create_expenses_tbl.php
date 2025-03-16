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
        Schema::create('expenses_tbl', function (Blueprint $table) {
            $table->id();
            $table->text('expenses_id')->nullable();
            $table->text('name');
            $table->decimal('amount', 30, 2)->default(0.00);
            $table->timestamp('date_of_expense')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses_tbl');
    }
};
