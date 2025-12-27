<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin KP Usahatama',
            'email' => 'admin@kpusahatama.com',
            'password' => Hash::make('admin123'),
        ]);

        User::create([
            'name' => 'Demo User',
            'email' => 'demo@kpusahatama.com',
            'password' => Hash::make('demo123'),
        ]);
    }
}
