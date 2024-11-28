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
                    'title' => 'Onboarding 1',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'image' => 'https://via.placeholder.com/150',
                ],
                [
                    'title' => 'Onboarding 2',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'image' => 'https://via.placeholder.com/150',
                ],
                [
                    'title' => 'Onboarding 3',
                    'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
                    'image' => 'https://via.placeholder.com/150',
                ],
            ];
            return $this->sendResponse($onboarding, 'Onboarding data');
        } catch (\Exception $e) {
            $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
