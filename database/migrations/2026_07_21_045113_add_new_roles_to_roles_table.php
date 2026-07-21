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
        \DB::table('roles')->insertOrIgnore([
            ['id' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        // No down migration logic for basic inserts, typically left empty
    }
};
