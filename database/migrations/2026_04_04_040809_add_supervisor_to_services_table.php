<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up()
{
    if (!Schema::hasColumn('services', 'supervisor_name')) {
        Schema::table('services', function (Blueprint $table) {
            $table->string('supervisor_name')->nullable()->after('status');
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            //
        });
    }
};
