<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('specialties')) {
            Schema::create('specialties', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('icon')->nullable();
                $table->string('category')->nullable()->default('Mantenimiento');
                $table->timestamps();
            });

            // Poblar con el catálogo predeterminado
            $defaults = [
                ['name' => 'Electricidad', 'icon' => '⚡', 'category' => 'Instalación y Eléctrico'],
                ['name' => 'Plomería', 'icon' => '🚰', 'category' => 'Hidráulico y Plomería'],
                ['name' => 'Aire Acondicionado (HVAC)', 'icon' => '❄️', 'category' => 'Climatización'],
                ['name' => 'Pintura e Impermeabilización', 'icon' => '🎨', 'category' => 'Acabados y Pintura'],
                ['name' => 'Albañilería y Remodelación', 'icon' => '🧱', 'category' => 'Obra y Albañilería'],
                ['name' => 'Carpintería y Muebles', 'icon' => '🪚', 'category' => 'Carpintería'],
                ['name' => 'Cerrajería y Seguridad', 'icon' => '🔑', 'category' => 'Seguridad y Accesos'],
                ['name' => 'Limpieza y Mantenimiento', 'icon' => '🧹', 'category' => 'Limpieza'],
                ['name' => 'Multi-técnico / General', 'icon' => '🧰', 'category' => 'General'],
                ['name' => 'Electrodomésticos y Equipos', 'icon' => '🔌', 'category' => 'Equipos y Línea Blanca'],
                ['name' => 'Jardinería y Exteriores', 'icon' => '🪴', 'category' => 'Exteriores'],
                ['name' => 'Redes y CCTV', 'icon' => '🖥️', 'category' => 'Tecnología y Seguridad'],
            ];

            foreach ($defaults as $item) {
                DB::table('specialties')->insertOrIgnore([
                    'name' => $item['name'],
                    'icon' => $item['icon'],
                    'category' => $item['category'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!Schema::hasTable('technician_specialties')) {
            Schema::create('technician_specialties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('specialty_id');
                $table->string('custom_name')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'specialty_id']);
                $table->index('user_id');
                $table->index('specialty_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_specialties');
        Schema::dropIfExists('specialties');
    }
};
