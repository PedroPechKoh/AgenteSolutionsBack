<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

  public function run(): void
{
    // 1. CREAR LOS ROLES PRIMERO
    \DB::table('roles')->insertOrIgnore([
        ['id' => 0, 'created_at' => now(), 'updated_at' => now()], // Root
        ['id' => 1, 'created_at' => now(), 'updated_at' => now()], // Admin
        ['id' => 2, 'created_at' => now(), 'updated_at' => now()], // Técnico
    ]);

    // 2. CREAR TU USUARIO (Asegúrate de borrar el User::factory() que viene por defecto)
    \App\Models\User::create([
        'name' => 'Pedro Root',
        'email' => 'root@agente.com',
        'password' => \Hash::make('root123'),
        'role_id' => 0, // Aquí le asignamos el ID que creamos arriba
        'is_active' => 1,
    ]);
}
}
