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
    Schema::create('properties', function (Blueprint $table) {
        $table->id();
        // ESTA es la columna que te faltaba:
        $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
        
        $table->string('type'); // Casa, Depto
        $table->string('custom_curp', 191)->unique();
        $table->text('address');
        $table->string('coordinates')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
