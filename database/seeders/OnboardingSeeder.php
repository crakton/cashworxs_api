<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Onboarding;

class OnboardingSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'title' => 'Simplifying payments',
                'description' => 'From taxes to fees',
                'image_url' => 'https://cloud.appwrite.io/v1/storage/buckets/676f130100315c886d84/files/676f13a90012a577dc4e/view?project=676f12d0003a9fc65124&project=676f12d0003a9fc65124&mode=admin',
            ],
            [
                'title' => 'Your financial Asistance',
                'description' => 'We keep you informed and onschedule',
                'image_url' => 'https://cloud.appwrite.io/v1/storage/buckets/676f130100315c886d84/files/676f1402003c43c72296/view?project=676f12d0003a9fc65124&project=676f12d0003a9fc65124&mode=admin',
            ],
            [
                'title' => 'Let get you started!',
                'description' => 'As simple as ABC...',
                'image_url' => 'https://cloud.appwrite.io/v1/storage/buckets/676f130100315c886d84/files/676f1432001a4aaf9f62/view?project=676f12d0003a9fc65124&project=676f12d0003a9fc65124&mode=admin',
            ],
        ];

        // Create or update onboarding data
        Onboarding::updateOrCreate(
            ['id' => 1], // Assuming you want a single onboarding record with ID 1
            ['onboarding_data' => json_encode($data)]
        );
    }
}