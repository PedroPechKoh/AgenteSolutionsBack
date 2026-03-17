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
    \DB::table('roles')->insertOrIgnore([
        ['id' => 0, 'created_at' => now(), 'updated_at' => now()], 
        ['id' => 1, 'created_at' => now(), 'updated_at' => now()], 
        ['id' => 2, 'created_at' => now(), 'updated_at' => now()], 
    ]);

    \App\Models\User::create([
        'name' => 'Pedro Root',
        'email' => 'root@agente.com',
        'password' => \Hash::make('root123'),
        'role_id' => 0, 
        'is_active' => 1,
    ]);
}
}
