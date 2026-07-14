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
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'max_properties')) {
                $table->integer('max_properties')->default(3)->after('membership_type');
            }
            if (!Schema::hasColumn('tenants', 'max_clients')) {
                $table->integer('max_clients')->default(0)->after('max_properties');
            }
            if (!Schema::hasColumn('tenants', 'extra_properties_count')) {
                $table->integer('extra_properties_count')->default(0)->after('max_clients');
            }
            if (!Schema::hasColumn('tenants', 'billing_cycle')) {
                $table->string('billing_cycle')->default('trial')->after('extra_properties_count');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'subscription_status')) {
                $table->string('subscription_status')->default('exempt')->after('is_active');
            }
            if (!Schema::hasColumn('users', 'subscription_start')) {
                $table->timestamp('subscription_start')->nullable()->after('subscription_status');
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_start');
            }
            if (!Schema::hasColumn('users', 'subscription_amount')) {
                $table->decimal('subscription_amount', 10, 2)->default(0.00)->after('subscription_expires_at');
            }
            if (!Schema::hasColumn('users', 'subscription_mp_payment_id')) {
                $table->string('subscription_mp_payment_id')->nullable()->after('subscription_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['max_properties', 'max_clients', 'extra_properties_count', 'billing_cycle']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'subscription_start', 'subscription_expires_at', 'subscription_amount', 'subscription_mp_payment_id']);
        });
    }
};
