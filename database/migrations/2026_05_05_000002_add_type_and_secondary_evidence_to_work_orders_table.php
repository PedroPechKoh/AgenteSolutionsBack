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
        Schema::table('work_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('work_orders', 'type')) {
                $table->string('type')->after('property_id')->nullable();
            }
            if (!Schema::hasColumn('work_orders', 'evidence_path_2')) {
                $table->string('evidence_path_2')->after('evidence_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['type', 'evidence_path_2']);
        });
    }
};
