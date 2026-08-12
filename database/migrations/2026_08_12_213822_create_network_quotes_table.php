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
        Schema::create('network_quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_id')->index();
            $table->unsignedBigInteger('technician_id')->index();
            $table->decimal('price', 10, 2);
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_quotes');
    }
};
