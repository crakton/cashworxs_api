<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceTaxes;
use Illuminate\Support\Str;

class ServiceTaxesSeeder extends Seeder
{
    public function run()
    {
        $taxes = [
            [
                'name' => 'Value Added Tax (VAT)',
                'type' => 'Federal Tax',
                'state' => 'Federal',
                'amount' => 7.5,
                'status' => true,
                'description' => 'Standard VAT applied to goods and services.',
                'metadata' => [
                    'payment_support' => ['Bank Transfer', 'Card Payment', 'USSD'],
                    'payment_type' => 'Recurring',
                ],
            ],
            // ... other taxes
        ];

        foreach ($taxes as $tax) {
            ServiceTaxes::firstOrCreate(
                ['name' => $tax['name']],
                $tax
            );
        }
    }
}