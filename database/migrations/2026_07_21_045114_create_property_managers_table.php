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
        Schema::create('property_managers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_user_id')->index(); // Role 7
            $table->unsignedBigInteger('autonomo_user_id')->index(); // Role 4 or 5
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('status', ['active', 'suspended', 'revoked'])->default('active');
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('grace_period_until')->nullable();
            $table->timestamps();

            // Maximum 1 active admin per Autonomo
            $table->unique(['autonomo_user_id', 'status'], 'pm_autonomo_status_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_managers');
    }
};
