<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Api\BaseController;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends BaseController
{
    public function updateLanguage(Request $request)
    {
        try {
            $request->validate([
                'language' => 'required|string',
            ]);

            $user = $request->user();
            $settings = Setting::where('user_id', $user->id)->first();
            $settings->language = $request->language;
            $settings->save();

            return $this->sendResponse([], 'Language updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function getStates(Request $request)
    {
        try {
            // Simulated list of states/regions
            $states = [
                ['id' => 1, 'name' => 'Lagos', 'country_code' => 'NG'],
                ['id' => 2, 'name' => 'Abuja', 'country_code' => 'NG'],
                ['id' => 3, 'name' => 'Kaduna', 'country_code' => 'NG'],
            ];

            return $this->sendResponse([
                'states' => $states
            ], 'States', 200);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
