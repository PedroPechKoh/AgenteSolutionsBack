<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            if (!Schema::hasColumn('quotes', 'related_service_ids')) {
                $table->json('related_service_ids')->nullable();
            }
            if (!Schema::hasColumn('quotes', 'is_unified_batch')) {
                $table->boolean('is_unified_batch')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['related_service_ids', 'is_unified_batch']);
        });
    }
};
