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
        if (Schema::hasTable('network_quotes') && !Schema::hasColumn('network_quotes', 'chat_history')) {
            Schema::table('network_quotes', function (Blueprint $table) {
                $table->json('chat_history')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('network_quotes') && Schema::hasColumn('network_quotes', 'chat_history')) {
            Schema::table('network_quotes', function (Blueprint $table) {
                $table->dropColumn('chat_history');
            });
        }
    }
};