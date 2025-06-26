<?php

namespace Database\Seeders;

use App\Models\InternalRevenueService;
use App\Models\State;
use Illuminate\Database\Seeder;

class StatesAndIRSServicesSeeder extends Seeder
{
    public function run()
    {
        $statesData = [
            [
                'irs_name' => 'Abia State Internal Revenue Service',
                'short_name' => 'AIRS',
                'state_name' => 'Abia',
                'website' => 'https://tat.gov.ng/news?id=62&l=bank-is-not-liable-to-remit-stamp-duty-deduction-to-abia-state-internal-revenue-tax-tribunal-rules',
                'contacts' => [
                    'phone' => ['08032488625'],
                    'email' => ['support@abiairs.gov']
                ]
            ],
            // ... other states
        ];

        foreach ($statesData as $data) {
            $state = State::firstOrCreate(
                ['name' => $data['state_name']],
                [
                    'name' => $data['state_name'],
                    'code' => strtoupper(substr($data['state_name'], 0, 3)),
                ]
            );

            InternalRevenueService::firstOrCreate(
                ['state_id' => $state->id],
                [
                    'state_id' => $state->id,
                    'irs_name' => $data['irs_name'],
                    'short_name' => $data['short_name'],
                    'website' => $data['website'],
                    'contacts' => $data['contacts'],
                ]
            );
        }
    }
}