<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Esquema de pago elegido por el cliente
            $table->string('payment_scheme')->default('full')->after('mp_payment_data'); // 'full', 'split', 'cash'

            // Anticipo (60%)
            $table->decimal('advance_amount', 10, 2)->nullable()->after('payment_scheme');
            $table->boolean('advance_paid')->default(false)->after('advance_amount');
            $table->timestamp('advance_paid_at')->nullable()->after('advance_paid');
            $table->json('advance_mp_data')->nullable()->after('advance_paid_at');

            // Finiquito / Restante (40%)
            $table->decimal('remaining_amount', 10, 2)->nullable()->after('advance_mp_data');
            $table->boolean('remaining_paid')->default(false)->after('remaining_amount');
            $table->timestamp('remaining_paid_at')->nullable()->after('remaining_paid');
            $table->json('remaining_mp_data')->nullable()->after('remaining_paid_at');

            // Pago en Efectivo
            $table->boolean('cash_requested')->default(false)->after('remaining_mp_data');
            $table->string('cash_amount_type')->nullable()->after('cash_requested'); // 'advance' o 'full'
            $table->string('cash_timing')->nullable()->after('cash_amount_type');    // 'immediate' o 'on_completion'
            $table->boolean('cash_confirmed')->default(false)->after('cash_timing');
            $table->timestamp('cash_confirmed_at')->nullable()->after('cash_confirmed');
            $table->unsignedBigInteger('cash_confirmed_by')->nullable()->after('cash_confirmed_at');
            $table->foreign('cash_confirmed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['cash_confirmed_by']);
            $table->dropColumn([
                'payment_scheme',
                'advance_amount', 'advance_paid', 'advance_paid_at', 'advance_mp_data',
                'remaining_amount', 'remaining_paid', 'remaining_paid_at', 'remaining_mp_data',
                'cash_requested', 'cash_amount_type', 'cash_timing',
                'cash_confirmed', 'cash_confirmed_at', 'cash_confirmed_by',
            ]);
        });
    }
};
