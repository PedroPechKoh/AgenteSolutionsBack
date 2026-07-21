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
        Schema::create('job_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contratista_user_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('property_id')->nullable()->index();
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('specialty_id')->nullable()->index();
            $table->enum('status', ['open', 'in_review', 'assigned', 'completed', 'cancelled'])->default('open');
            $table->unsignedBigInteger('selected_quote_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_requests');
    }
};
