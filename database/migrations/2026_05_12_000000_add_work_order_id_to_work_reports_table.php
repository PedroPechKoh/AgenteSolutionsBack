<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->change();
            $table->foreignId('work_order_id')->nullable()->after('service_id')->constrained('work_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');
            $table->foreignId('service_id')->nullable(false)->change();
        });
    }
};
