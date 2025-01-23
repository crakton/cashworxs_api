<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Service;
use App\Models\ServiceFees;

class ServiceFeesSeeder extends Seeder
{
    public function run()
    {
        $organizations = [
            [
                'id' => \Illuminate\Support\Str::ulid(),
                'name' => 'Federal Road Safety Commission (FRSC)',
                'type' => 'Government',
                'services' => [
                    [
                        'id' => \Illuminate\Support\Str::ulid(),
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
            [
                'id' => \Illuminate\Support\Str::ulid(),
                'name' => 'Lagos State Vehicle Licensing Office',
                'type' => 'Government',
                'services' => [
                    [
                        'id' => \Illuminate\Support\Str::ulid(),
                        'name' => 'Vehicle Registration Fee',
                        'type' => 'Government',
                        'state' => 'Lagos',
                        'amount' => 20000,
                        'status' => true,
                        'description' => 'Fee for registering a vehicle in Lagos state.',
                        'metadata' => [
                            'payment_support' => ['POS', 'Card Payment'],
                            'payment_type' => 'Recurring',
                        ],
                    ],
                ],
            ],
            [
                'id' => \Illuminate\Support\Str::ulid(),
                'name' => 'ABC Insurance Company',
                'type' => 'Private',
                'services' => [
                    [
                        'id' => \Illuminate\Support\Str::ulid(),
                        'name' => 'Comprehensive Insurance Fee',
                        'type' => 'Private',
                        'state' => 'Nationwide',
                        'amount' => 50000,
                        'status' => true,
                        'description' => 'Fee for comprehensive vehicle insurance.',
                        'metadata' => [
                            'payment_support' => ['Bank Transfer', 'Card Payment'],
                            'payment_type' => 'Annual',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($organizations as $organizationData) {
            $services = $organizationData['services'];
            unset($organizationData['services']);

            $organization = Organization::create($organizationData);

            foreach ($services as $service) {
                $service['organization_id'] = $organization->id;
                ServiceFees::create($service);
            }
        }
    }
}
