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
    if (!Schema::hasTable('appliances')) {
        Schema::create('appliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->string('type');
            $table->string('brand');
            $table->string('model');
            $table->string('serial_number', 191)->nullable();
            $table->boolean('has_warranty')->default(false);
            $table->string('image_path', 191)->nullable();
            $table->timestamps();
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appliances');
    }
};
