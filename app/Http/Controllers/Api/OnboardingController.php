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
					'image_url' => 'https://cloud.appwrite.io/v1/storage/buckets/676f130100315c886d84/files/676f13a90012a577dc4e/view?project=676f12d0003a9fc65124&project=676f12d0003a9fc65124&mode=admin',
				],
				[
					'title' => 'Pay taxes and fees as simple as ABC',
					'description' => 'Easily manage your taxes and fees to government and companies from one platform.',
					'image_url' => 'https://cloud.appwrite.io/v1/storage/buckets/676f130100315c886d84/files/676f1402003c43c72296/view?project=676f12d0003a9fc65124&project=676f12d0003a9fc65124&mode=admin',
				],
				[
					'title' => 'Tax Education ',
					'description' => 'Get realtime updates on tax laws, governement fess, and private companies deligations.',
					'image_url' => 'https://cloud.appwrite.io/v1/storage/buckets/676f130100315c886d84/files/676f1432001a4aaf9f62/view?project=676f12d0003a9fc65124&project=676f12d0003a9fc65124&mode=admin',
				],
			];
			return $this->sendResponse($onboarding, 'Onboarding data');
		} catch (\Exception $e) {
			$this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}
}
