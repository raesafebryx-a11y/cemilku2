<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Cemilku',
            'email' => 'admin@cemilku.com',
            'phone' => '081234567890',
            'role' => 'admin',
            'password' => Hash::make('password123'),
        ]);
    }
}
