<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\ServiceFees;
use Illuminate\Support\Str;

class ServiceFeesSeeder extends Seeder
{
    public function run()
    {
        $organizations = [
            [
                'name' => 'Federal Road Safety Commission (FRSC)',
                'type' => 'Government',
                'services' => [
                    [
                        'name' => 'Driver’s License Renewal Fee',
                        'type' => 'Government',
                        'state' => 'Federal',
                        'amount' => 6500,
                        'status' => true,
                        'description' => 'Fee for renewing a driver’s license in Nigeria.',
                        'metadata' => [
                            'payment_support' => ['Bank Transfer', 'Card Payment', 'USSD'],
                            'payment_type' => 'One-time',
                        ],
                    ],
                ],
            ],
            // ... other organizations
        ];

        foreach ($organizations as $organizationData) {
            $services = $organizationData['services'];
            unset($organizationData['services']);

            $organization = Organization::firstOrCreate(
                ['name' => $organizationData['name']],
                $organizationData
            );

            foreach ($services as $service) {
                ServiceFees::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'name' => $service['name']
                    ],
                    $service
                );
            }
        }
    }
}