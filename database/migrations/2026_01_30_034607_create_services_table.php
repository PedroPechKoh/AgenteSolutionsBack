<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id(); 
            
           
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable(); e
            $table->unsignedBigInteger('assigned_to')->nullable();  
            $table->unsignedBigInteger('service_category_id')->nullable(); 
            
            $table->string('service_type')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->default('Asignado'); 
            $table->string('title');
            $table->text('description')->nullable();
            
         
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->dateTime('real_start')->nullable();
            $table->dateTime('real_end')->nullable();
            
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};