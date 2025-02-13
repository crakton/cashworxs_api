<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'full_name' => 'Crakton ',
            'phone_number' => '09034847432',
            'password' => Hash::make('Truth212.'),
            'is_admin' => true,
            'verified' => true,
            'phone_verified_at' => now()
        ]);
    }
}
