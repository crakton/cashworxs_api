<?php

namespace Database\Seeders;

use App\Models\InternalRevenueService;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all states and IRS services upfront to minimize queries
        $states = State::all();
        $irsServices = InternalRevenueService::with('state')->get();
        
        // Find FCT state once
        $fctState = $states->firstWhere('name', 'FCT');
        $lagosState = $states->firstWhere('name', 'Lagos');

        // Create the main admin user
        $admin = User::create([
            'full_name' => 'Crakton Admin',
            'phone_number' => '08081646633',
            'password' => Hash::make('Truth212.'),
            'is_admin' => true,
            'verified' => true,
            'phone_verified_at' => now(),
            'state_id' => $fctState->id,
        ]);

        // Assign admin role using relationship
        $admin->roles()->attach(
            Role::where('name', 'admin')->firstOrFail()
        );

        // Create IRS specialists for each state
        foreach ($irsServices as $irs) {
            $state = $irs->state;
            $stateCode = strtoupper(substr($state->name, 0, 3));
            
            $specialist = User::create([
                'full_name' => "IRS Specialist {$state->name}",
                'phone_number' => '080' . rand(10000000, 99999999),
                'password' => Hash::make('Specialist123!'),
                'is_admin' => false,
                'verified' => true,
                'phone_verified_at' => now(),
                'state_id' => $state->id,
            ]);

            $specialist->roles()->attach(
                Role::where('name', 'irs_specialist')->firstOrFail()
            );

            $this->command->info("Created IRS specialist for {$state->name}:");
            $this->command->info("Phone: {$specialist->phone_number}");
            $this->command->info("Password: Specialist123!");
        }

        // Create test operator user
        $operator = User::create([
            'full_name' => 'Test Operator',
            'phone_number' => '080' . rand(10000000, 99999999),
            'password' => Hash::make('Operator123!'),
            'is_admin' => false,
            'verified' => true,
            'phone_verified_at' => now(),
            'state_id' => $lagosState->id,
        ]);

        $operator->roles()->attach(
            Role::where('name', 'operator')->firstOrFail()
        );

        $this->command->info("\nCreated test operator:");
        $this->command->info("Phone: {$operator->phone_number}");
        $this->command->info("Password: Operator123!");
    }
}