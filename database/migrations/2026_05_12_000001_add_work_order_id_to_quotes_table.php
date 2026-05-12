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
        Schema::table('quotes', function (Blueprint $table) {
            // Hacemos que service_id sea opcional si se liga a una work_order
            $table->unsignedBigInteger('service_id')->nullable()->change();
            
            // Añadimos la relación con work_orders
            $table->foreignId('work_order_id')
                  ->nullable()
                  ->after('service_id')
                  ->constrained('work_orders')
                  ->onDelete('cascade');
                  
            // Añadimos un campo para saber quién la originó (opcional pero útil)
            $table->string('created_by_role')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn(['work_order_id', 'created_by_role']);
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });
    }
};
