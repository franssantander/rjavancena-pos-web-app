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
        Schema::create('users_info_tbl', function (Blueprint $table) {
            // Ids
            $table->id();
            $table->text('user_info_id')->nullable();
            $table->text('user_id');
            
            // Profile Picture
            $table->longText('image')->nullable();

            // Personal Information
            $table->longText('first_name')->nullable();
            $table->longText('middle_name')->nullable();
            $table->longText('last_name')->nullable();

            // $table->string('suffix')->nullable();
            // $table->string('gender')->nullable();
            // $table->timestamp('birth_date')->nullable();    

            // Contacts
            // $table->longText('contact_number')->nullable();
            // $table->longText('contact_email')->nullable();

            // Address
            $table->longText('address_1')->nullable();
            $table->longText('address_2')->nullable();
            $table->longText('region_code')->nullable();  
            $table->longText('province_code')->nullable();
            $table->longText('city_or_municipality_code')->nullable();
            $table->longText('barangay_code')->nullable();
            $table->longText('region_name')->nullable();
            $table->longText('province_name')->nullable();
            $table->longText('city_or_municipality_name')->nullable();
            $table->longText('barangay_name')->nullable();
            $table->longText('description_location')->nullable();

            // Date | Time
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_info_tbl');
    }
};
