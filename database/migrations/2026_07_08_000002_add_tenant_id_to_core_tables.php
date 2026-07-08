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
        $tables = ['users', 'properties', 'quotes', 'services', 'work_orders', 'clients', 'technicians'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->index();
                });
            }
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'approval_status')) {
            Schema::table('users', function (Blueprint $table) {
                // approved, pending, rejected (los clientes son approved por defecto, técnicos se registran como pending)
                $table->string('approval_status', 50)->default('approved');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['users', 'properties', 'quotes', 'services', 'work_orders', 'clients', 'technicians'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('tenant_id');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'approval_status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('approval_status');
            });
        }
    }
};
