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
    public function run(): void
    {
        // Get all states and IRS services upfront to minimize queries
        $states = State::all();
        $irsServices = InternalRevenueService::with('state')->get();
        
        // Find FCT state once
        $fctState = $states->firstWhere('name', 'FCT');
        $lagosState = $states->firstWhere('name', 'Lagos');

        if (!$fctState || !$lagosState) {
            $this->command->error('Required states (FCT and Lagos) not found!');
            return;
        }

        // Create or update the main admin user
        $admin = User::firstOrCreate(
            ['phone_number' => '08081646633'],
            [
                'full_name' => 'Crakton Admin',
                'password' => Hash::make('Truth212.'),
                'is_admin' => true,
                'verified' => true,
                'phone_verified_at' => now(),
                'state_id' => $fctState->id,
            ]
        );

        // Assign admin role if not already assigned
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$admin->roles()->where('name', 'admin')->exists()) {
            $admin->roles()->attach($adminRole);
        }

        // Create IRS specialists for each state if they don't exist
        foreach ($irsServices as $irs) {
            $state = $irs->state;
            $stateCode = strtoupper(substr($state->name, 0, 3));
            
            $specialistPhone = '080' . rand(10000000, 99999999);
            $specialistName = "IRS Specialist {$state->name}";
            
            $specialist = User::firstOrCreate(
                ['full_name' => $specialistName],
                [
                    'phone_number' => $specialistPhone,
                    'password' => Hash::make('Specialist123!'),
                    'is_admin' => false,
                    'verified' => true,
                    'phone_verified_at' => now(),
                    'state_id' => $state->id,
                ]
            );

            $irsRole = Role::where('name', 'irs_specialist')->first();
            if ($irsRole && !$specialist->roles()->where('name', 'irs_specialist')->exists()) {
                $specialist->roles()->attach($irsRole);
                
                $this->command->info("IRS specialist for {$state->name}:");
                $this->command->info("Phone: {$specialist->phone_number}");
                $this->command->info("Password: Specialist123!");
            }
        }

        // Create test operator user if it doesn't exist
        $operatorPhone = '080' . rand(10000000, 99999999);
        $operator = User::firstOrCreate(
            ['full_name' => 'Test Operator'],
            [
                'phone_number' => $operatorPhone,
                'password' => Hash::make('Operator123!'),
                'is_admin' => false,
                'verified' => true,
                'phone_verified_at' => now(),
                'state_id' => $lagosState->id,
            ]
        );

        $operatorRole = Role::where('name', 'operator')->first();
        if ($operatorRole && !$operator->roles()->where('name', 'operator')->exists()) {
            $operator->roles()->attach($operatorRole);
            
            $this->command->info("\nTest operator:");
            $this->command->info("Phone: {$operator->phone_number}");
            $this->command->info("Password: Operator123!");
        }
    }
}