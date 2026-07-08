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
        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // Nombre de la empresa (Ej. Empresa X)
                $table->string('code', 50)->unique(); // Identificador único (Ej. AUT_01)
                $table->unsignedBigInteger('owner_user_id')->nullable()->index(); // ID del usuario dueño (role_id = 4)
                $table->string('logo_url')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 191)->nullable();
                $table->string('status', 50)->default('active'); // active, pending_approval, suspended
                $table->string('membership_type', 50)->default('standard')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
