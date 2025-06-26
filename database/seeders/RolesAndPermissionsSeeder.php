<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create or update roles using firstOrCreate
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'System administrator with full access',
                'is_active' => true,
            ]
        );

        $operatorRole = Role::firstOrCreate(
            ['name' => 'operator'],
            [
                'display_name' => 'Operator',
                'description' => 'System operator with limited access',
                'is_active' => true,
            ]
        );

        $irsSpecialistRole = Role::firstOrCreate(
            ['name' => 'irs_specialist'],
            [
                'display_name' => 'IRS Specialist',
                'description' => 'State IRS specialist with state-specific access',
                'is_active' => true,
            ]
        );

        $userRole = Role::firstOrCreate(
            ['name' => 'user'],
            [
                'display_name' => 'User',
                'description' => 'Regular application user',
                'is_active' => true,
            ]
        );

        // Create permissions grouped by features
        $permissions = [
            // User management
            ['name' => 'view_users', 'display_name' => 'View Users', 'group' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'group' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'group' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'group' => 'users'],
            
            // Transactions
            ['name' => 'view_transactions', 'display_name' => 'View Transactions', 'group' => 'transactions'],
            ['name' => 'create_transactions', 'display_name' => 'Create Transactions', 'group' => 'transactions'],
            
            // Services
            ['name' => 'manage_services', 'display_name' => 'Manage Services', 'group' => 'services'],
            
            // Organizations
            ['name' => 'manage_organizations', 'display_name' => 'Manage Organizations', 'group' => 'organizations'],
            
            // Notifications
            ['name' => 'manage_notifications', 'display_name' => 'Manage Notifications', 'group' => 'notifications'],
            
            // Settings
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'group' => 'settings'],
            
            // IRS specific
            ['name' => 'manage_state_taxes', 'display_name' => 'Manage State Taxes', 'group' => 'taxes'],
            ['name' => 'view_state_taxes', 'display_name' => 'View State Taxes', 'group' => 'taxes'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Assign permissions to roles
        $adminRole->permissions()->sync(Permission::pluck('id'));
        
        $operatorRole->permissions()->sync(Permission::whereIn('name', [
            'view_users',
            'view_transactions',
            'manage_services',
            'manage_notifications',
        ])->pluck('id'));
        
        $irsSpecialistRole->permissions()->sync(Permission::whereIn('name', [
            'view_users',
            'view_transactions',
            'manage_state_taxes',
            'view_state_taxes',
        ])->pluck('id'));
        
        $userRole->permissions()->sync(Permission::whereIn('name', [
            'view_transactions',
        ])->pluck('id'));
    }
}