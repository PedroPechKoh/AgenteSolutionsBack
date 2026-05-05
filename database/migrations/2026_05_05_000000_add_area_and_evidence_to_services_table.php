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
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('property_area_id')->nullable()->after('property_id');
            $table->string('evidence_path')->nullable()->after('description');

            $table->foreign('property_area_id')->references('id')->on('property_areas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['property_area_id']);
            $table->dropColumn(['property_area_id', 'evidence_path']);
        });
    }
};
