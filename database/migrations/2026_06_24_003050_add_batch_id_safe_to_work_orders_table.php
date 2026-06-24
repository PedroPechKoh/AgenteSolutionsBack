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
        if (!Schema::hasColumn('work_orders', 'batch_id')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->string('batch_id')->nullable()->after('property_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('work_orders', 'batch_id')) {
            Schema::table('work_orders', function (Blueprint $table) {
                $table->dropColumn('batch_id');
            });
        }
    }
};
