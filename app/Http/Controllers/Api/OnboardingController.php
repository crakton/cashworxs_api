<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;

class OnboardingController extends BaseController
{
    public function index()
    {
        try {
            $onboarding = [
                [
                    'title' => 'AI to simplify your tax journey',
                    'description' => 'New to taxes and fees management? Our AI will guide you every step of the way.',
                    'image_url' => 'https://via.placeholder.com/150',
                ],
                [
                    'title' => 'Pay taxes and fees as simple as ABC',
                    'description' => 'Easily manage your taxes and fees to government and companies from one platform.',
                    'image_url' => 'https://via.placeholder.com/150',
                ],
                [
                    'title' => 'Tax Education ',
                    'description' => 'Get realtime updates on tax laws, governement fess, and private companies deligations.',
                    'image_url' => 'https://via.placeholder.com/150',
                ],
            ];
            return $this->sendResponse($onboarding, 'Onboarding data');
        } catch (\Exception $e) {
            $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
