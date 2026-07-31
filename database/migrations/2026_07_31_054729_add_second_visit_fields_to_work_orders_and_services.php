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
            $table->string('second_visit_proposed_date')->nullable();
            $table->text('second_visit_reason')->nullable();
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('second_visit_proposed_date')->nullable();
            $table->text('second_visit_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn('second_visit_proposed_date');
            $table->dropColumn('second_visit_reason');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('second_visit_proposed_date');
            $table->dropColumn('second_visit_reason');
        });
    }
};
