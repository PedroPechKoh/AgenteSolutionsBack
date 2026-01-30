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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');              // <--- Faltaba esto
            $table->string('email', 191)->unique(); // <--- Faltaba esto
            $table->string('phone', 20)->nullable(); // <--- Faltaba esto
            $table->text('address')->nullable();     // <--- Faltaba esto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
