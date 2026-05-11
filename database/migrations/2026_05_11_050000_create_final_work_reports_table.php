<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_work_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->string('folio')->nullable();
            $table->string('report_date')->nullable();
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->text('description')->nullable();
            $table->json('materials')->nullable();
            $table->text('observations')->nullable();
            $table->json('selected_images')->nullable();
            $table->timestamps();

            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_work_reports');
    }
};
