<?php

namespace Database\Seeders;

use App\Models\Activities;
use Illuminate\Database\Seeder;

class ActivitiesSeeder extends Seeder
{
    public function run()
    {
        $activities = [
            [
                'type' => 'taxes',
                'title' => 'Pay Taxes',
                'description' => 'Manage and pay various taxes, including federal and state taxes.',
                'meta_info' => [
                    // ... meta info
                ],
            ],
            // ... other activities
        ];

        foreach ($activities as $activity) {
            Activities::firstOrCreate(
                ['title' => $activity['title']],
                $activity
            );
        }
    }
}