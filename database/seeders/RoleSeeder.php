<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
{
    \DB::table('roles')->insert([
        ['id' => 0, 'created_at' => now(), 'updated_at' => now()], 
        ['id' => 1, 'created_at' => now(), 'updated_at' => now()], 
        ['id' => 2, 'created_at' => now(), 'updated_at' => now()], 
    ]);
}
}
