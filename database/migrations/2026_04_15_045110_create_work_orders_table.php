<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('work_orders')) {
            Schema::create('work_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id'); // ¿De qué casa es?

                // Campos de tu modal
                $table->string('zone'); // Ej: 'cocina', 'sala'
                $table->string('equipment')->nullable(); // Ej: 'refrigerador', 'otro' (Opcional)
                $table->text('description'); // El problema que describe el cliente
                $table->string('evidence_path')->nullable(); // Para guardar la foto si la suben

                // El motor de tu Tablero de Control
                // Usaremos 'Por Hacer' como default cuando el cliente apenas lo envía.
                $table->enum('status', ['Por Hacer', 'En Proceso', 'Listo'])->default('Por Hacer');

                // SOS lo manejaremos como una prioridad
                $table->enum('priority', ['Normal', 'Urgente'])->default('Normal');

                $table->unsignedBigInteger('tecnico_id')->nullable(); // A quién se le asigna después

                $table->timestamps();

                $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
                // $table->foreign('tecnico_id')->references('id')->on('users'); // Si tienes tabla users
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
