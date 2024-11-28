<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function greetings(Request $request)
    {
        try {
            $user = $request->user();
            $hour = date('G');
            if ($hour >= 5 && $hour < 12) {
                $greeting = "Good morning, {$user->full_name}!";
            } elseif ($hour >= 12 && $hour < 17) {
                $greeting = "Good afternoon, {$user->full_name}!";
            } else {
                $greeting = "Good evening, {$user->full_name}!";
            }
            return $this->sendResponse([
                'greeting' => $greeting,
            ], 'Greetings');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function getActivities()
    {
        try {
            $activities = [
                ['id' => 1, 'type' => 'Pay Taxes', 'description' => 'Tax payment activity', 'meta_info' => null],
                ['id' => 2, 'type' => 'Pay Fees', 'description' => 'Fee payment activity', 'meta_info' => null],
            ];

            return response()->json(['activities' => $activities], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}
