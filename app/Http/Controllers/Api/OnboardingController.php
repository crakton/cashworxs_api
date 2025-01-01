<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Onboarding;
use Request;

class OnboardingController extends BaseController
{
	public function index()
	{
		try {
			$onboarding = Onboarding::select('onboarding_data')->first();

			if (!$onboarding) {
				return $this->sendError('No onboarding data found', [], 404);
			}

			return $this->sendResponse(json_decode($onboarding->onboarding_data), 'Onboarding data retrieved successfully');
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function addChecklist(Request $request)
	{
		$validatedData = $request->validate([
			'user_id' => 'required|exists:users,id',
			'income' => 'nullable|numeric',
			'bvn' => 'nullable|string|max:11',
			'nin' => 'nullable|string|max:11',
		]);

		try {
			$onboarding = Onboarding::create($validatedData);

			return $this->sendResponse($onboarding, 'Onboarding checklist created successfully');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}
	public function updateChecklist(Request $request, $id)
	{
		$validatedData = $request->validate([
			'income' => 'nullable|numeric',
			'bvn' => 'nullable|string|max:11',
			'nin' => 'nullable|string|max:11',
		]);

		try {
			$onboarding = Onboarding::findOrFail($id);
			$onboarding->update($validatedData);

			return $this->sendResponse($onboarding, 'Onboarding checklist updated successfully');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}
}
