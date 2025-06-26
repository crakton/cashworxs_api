<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServiceTaxes;

class ServiceTaxesSeeder extends Seeder
{
    public function run()
    {
        $taxes = [
            [
                'id' => \Illuminate\Support\Str::ulid(),
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
            [
                'id' => \Illuminate\Support\Str::ulid(),
                'name' => 'Personal Income Tax',
                'type' => 'State Tax',
                'state' => 'Oyo',
                'amount' => 10,
                'status' => true,
                'description' => 'Tax on personal income for residents of Oyo state.',
                'metadata' => [
                    'payment_support' => ['POS', 'Card Payment'],
                    'payment_type' => 'Recurring',
                ],
            ],
        ];

        foreach ($taxes as $tax) {
            ServiceTaxes::create($tax);
        }
    }
}
