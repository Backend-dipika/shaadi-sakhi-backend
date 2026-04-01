<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Shadhi Sakhi Admin',
            'email' => 'admin1@shadhisakhi.com',
            'password' => 'Password123', // auto hashed (your model handles it)
        ]);
    }
}
