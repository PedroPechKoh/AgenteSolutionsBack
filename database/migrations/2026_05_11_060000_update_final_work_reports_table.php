<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_work_reports', function (Blueprint $table) {
            // Hacer service_id nullable para permitir reportes de WorkOrders
            $table->unsignedBigInteger('service_id')->nullable()->change();
            
            // Añadir columna para WorkOrder
            $table->unsignedBigInteger('work_order_id')->nullable()->after('service_id');
            
            $table->foreign('work_order_id')->references('id')->on('work_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('final_work_reports', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');
            $table->unsignedBigInteger('service_id')->nullable(false)->change();
        });
    }
};
