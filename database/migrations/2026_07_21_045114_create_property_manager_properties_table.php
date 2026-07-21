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
        Schema::create('property_manager_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_manager_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_manager_properties');
    }
};
