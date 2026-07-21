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
        Schema::create('job_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_request_id')->index();
            $table->unsignedBigInteger('technician_id')->index();
            $table->decimal('price', 10, 2);
            $table->integer('estimated_days')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_quotes');
    }
};
