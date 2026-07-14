<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'subscription_status')) {
                // Estado: 'active', 'pending_payment', 'expired', 'suspended'
                $table->string('subscription_status', 30)->default('active')->after('membership_type');
            }
            if (!Schema::hasColumn('tenants', 'subscription_start')) {
                $table->timestamp('subscription_start')->nullable()->after('subscription_status');
            }
            if (!Schema::hasColumn('tenants', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_start');
            }
            if (!Schema::hasColumn('tenants', 'subscription_amount')) {
                $table->decimal('subscription_amount', 10, 2)->nullable()->after('subscription_expires_at');
            }
            if (!Schema::hasColumn('tenants', 'subscription_mp_payment_id')) {
                $table->string('subscription_mp_payment_id', 100)->nullable()->after('subscription_amount');
            }
        });

        // Asegurar que role_id 5 existe en la tabla roles
        \DB::table('roles')->insertOrIgnore([
            'id'         => 5,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'subscription_start',
                'subscription_expires_at',
                'subscription_amount',
                'subscription_mp_payment_id',
            ]);
        });
    }
};
