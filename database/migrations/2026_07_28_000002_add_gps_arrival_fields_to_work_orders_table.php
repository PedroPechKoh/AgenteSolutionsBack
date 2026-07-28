<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('work_orders', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('work_orders', 'arrived_latitude')) {
                $table->decimal('arrived_latitude', 10, 7)->nullable()->after('arrived_at');
            }
            if (!Schema::hasColumn('work_orders', 'arrived_longitude')) {
                $table->decimal('arrived_longitude', 10, 7)->nullable()->after('arrived_latitude');
            }
            if (!Schema::hasColumn('work_orders', 'arrival_status')) {
                $table->string('arrival_status', 30)->default('PENDIENTE')->after('arrived_longitude');
            }
            if (!Schema::hasColumn('work_orders', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('arrival_status');
            }
        });

        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('services', 'arrived_latitude')) {
                $table->decimal('arrived_latitude', 10, 7)->nullable()->after('arrived_at');
            }
            if (!Schema::hasColumn('services', 'arrived_longitude')) {
                $table->decimal('arrived_longitude', 10, 7)->nullable()->after('arrived_latitude');
            }
            if (!Schema::hasColumn('services', 'arrival_status')) {
                $table->string('arrival_status', 30)->default('PENDIENTE')->after('arrived_longitude');
            }
            if (!Schema::hasColumn('services', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('arrival_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'arrived_at',
                'arrived_latitude',
                'arrived_longitude',
                'arrival_status',
                'reminder_sent_at'
            ]);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'arrived_at',
                'arrived_latitude',
                'arrived_longitude',
                'arrival_status',
                'reminder_sent_at'
            ]);
        });
    }
};
