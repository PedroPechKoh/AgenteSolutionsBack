<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
 public function run(): void
{
    \DB::table('users')->insert([
        'role_id' => 0, // Nivel Root
        'name' => 'Pedro Root',
        'email' => 'root@agente.com',
        'password' => \Hash::make('root123'), 
        'is_active' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
}
