<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceFees;

class ServiceFeesSeeder extends Seeder
{
    public function run()
    {
        $fees = [
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
        ];

        foreach ($fees as $fee) {
            ServiceFees::create($fee);
        }
    }
}
