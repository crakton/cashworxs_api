<?php

namespace Database\Seeders;

use App\Models\InternalRevenueService;
use App\Models\Role;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
      /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Get all states and IRS services
        $states = State::all();
        $irsServices = InternalRevenueService::with('state')->get();

        // Find FCT and Lagos states
        $fctState = $states->firstWhere('name', 'FCT');
        $lagosState = $states->firstWhere('name', 'Lagos');

        // Verify roles exist
        $adminRole = Role::where('name', 'admin')->first();
        $irsSpecialistRole = Role::where('name', 'irs_specialist')->first();
        $operatorRole = Role::where('name', 'operator')->first();

        if (!$adminRole || !$irsSpecialistRole || !$operatorRole) {
            $this->command->error("One or more required roles are missing. Please run RoleSeeder first.");
            return;
        }

        $this->command->info("Found all required roles:");
        $this->command->info("- Admin Role ID: {$adminRole->id}");
        $this->command->info("- IRS Specialist Role ID: {$irsSpecialistRole->id}");
        $this->command->info("- Operator Role ID: {$operatorRole->id}\n");

        // Create the main admin user
        $admin = User::create([
            'full_name' => 'Crakton Admin',
            'phone_number' => '08081646633',
            'password' => Hash::make('Truth212.'),
            'is_admin' => true,
            'verified' => true,
            'phone_verified_at' => now(),
            'state_id' => $fctState->id,
            'role' => $adminRole->name, // Assign role directly
        ]);

        // Assign admin role
        $admin->roles()->attach($adminRole->id);
        
        $this->command->info("✓ Created Crakton Admin:");
        $this->command->info("  Phone: {$admin->phone_number}");
        $this->command->info("  Password: Truth212.");
        $this->command->info("  Role: admin");
        $this->command->info("  Assigned Role ID: {$adminRole->id}\n");

        // Create IRS specialists for each state (37 total)
        $specialistCount = 0;
        foreach ($irsServices as $irs) {
            $state = $irs->state;
            
            $specialist = User::create([
                'full_name' => "IRS Specialist {$state->name}",
                'phone_number' => '080' . rand(10000000, 99999999),
                'password' => Hash::make('Specialist123!'),
                'is_admin' => false,
                'verified' => true,
                'phone_verified_at' => now(),
                'state_id' => $state->id,
                'role' => $irsSpecialistRole->name, // Assign role directly
            ]);

            // Assign IRS specialist role
            $specialist->roles()->attach($irsSpecialistRole->id);
            $specialistCount++;

            $this->command->info("✓ Created IRS Specialist #{$specialistCount} for {$state->name}:");
            $this->command->info("  Phone: {$specialist->phone_number}");
            $this->command->info("  Password: Specialist123!");
            $this->command->info("  Role: irs_specialist");
            $this->command->info("  Assigned Role ID: {$irsSpecialistRole->id}");
        }

        $this->command->info("\nTotal IRS Specialists created: {$specialistCount}");

        // Create test operator user
        $operator = User::create([
            'full_name' => 'Test Operator',
            'phone_number' => '080' . rand(10000000, 99999999),
            'password' => Hash::make('Operator123!'),
            'is_admin' => false,
            'verified' => true,
            'phone_verified_at' => now(),
            'state_id' => $lagosState->id,
            'role' => $operatorRole->name, // Assign role directly
        ]);

        // Assign operator role
        $operator->roles()->attach($operatorRole->id);

        $this->command->info("\n✓ Created Test Operator:");
        $this->command->info("  Phone: {$operator->phone_number}");
        $this->command->info("  Password: Operator123!");
        $this->command->info("  Role: operator");
        $this->command->info("  Assigned Role ID: {$operatorRole->id}");

        // Verification: Check role assignments
        $this->command->info("\n=== ROLE ASSIGNMENT VERIFICATION ===");
        
        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->count();
        
        $specialistUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'irs_specialist');
        })->count();
        
        $operatorUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'operator');
        })->count();
        
        $usersWithoutRoles = User::doesntHave('roles')->count();

        $this->command->info("Users with admin role: {$adminUsers}");
        $this->command->info("Users with irs_specialist role: {$specialistUsers}");
        $this->command->info("Users with operator role: {$operatorUsers}");
        $this->command->info("Users without any role: {$usersWithoutRoles}");
        
        if ($usersWithoutRoles > 0) {
            $this->command->error("WARNING: {$usersWithoutRoles} users have no roles assigned!");
        } else {
            $this->command->info("✓ All users have been assigned roles correctly!");
        }
    }
}