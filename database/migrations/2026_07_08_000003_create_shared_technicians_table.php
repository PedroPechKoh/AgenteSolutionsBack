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
        if (!Schema::hasTable('shared_technicians')) {
            Schema::create('shared_technicians', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('technician_user_id')->index(); // ID del usuario técnico (role_id = 2)
                $table->unsignedBigInteger('shared_with_tenant_id')->index(); // ID del tenant al que se le presta
                $table->unsignedBigInteger('shared_by_user_id')->nullable(); // ID del usuario que lo compartió
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_technicians');
    }
};
