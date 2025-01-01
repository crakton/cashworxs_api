<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Api\BaseController;
use App\Models\Fee;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{
	public function greetUser(Request $request)
	{
		try {
			$user = $request->user();
			$currentTime = now();
			$hour = $currentTime->format('G');
			$isFirstLogin = is_null($user->last_login_at); // Assuming 'last_login_at' is tracked in the users table

			// Determine time-based greeting
			if ($hour >= 5 && $hour < 12) {
				$timeGreeting = "Good morning";
			} elseif ($hour >= 12 && $hour < 17) {
				$timeGreeting = "Good afternoon";
			} else {
				$timeGreeting = "Good evening";
			}

			// Construct greeting message
			if ($isFirstLogin) {
				$greeting = "Welcome, {$user->full_name}! We're glad to have you onboard.";
			} else {
				$greeting = "{$timeGreeting}, {$user->full_name}!";
			}

			// Update the user's last login timestamp
			// $user->update(['last_login_at' => $currentTime]);

			return $this->sendResponse([
				'greeting' => $greeting,
				'isFirstLogin' => $isFirstLogin,
				// 'lastLogin' => $user->last_login_at,
			], 'Greetings');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function getUser(Request $request, String $id)
	{
		try {
			$user = User::where('id', $id)->first();
			return $this->sendResponse(['user' => $user]);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 433);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function getAllUsers(Request $request)
	{
		try {
			return $this->sendResponse(['users' => User::all()]);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 433);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function dropUser(Request $request, String $id)
	{
		try {

			$user = User::where('id', $id)->delete();
			return $this->sendResponse('User deleted successfully');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 433);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function updateUser(Request $request, $id)
	{
		try {

			$user = User::where('id', $id)->first;
			$user->update($request->all());
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
	public function smackUserDB()
	{
		try {
			// remove all users on the the user table
			User::truncate();
			return $this->sendResponse('Users KO!');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function getAllTransactions(Request $request)
	{
		try {
			$user = $request->user();
			$taxes = Tax::all()->where('user_id', $user->id);
			$fees = Fee::all()->where('user_id', $user->id);
			$transactions = $taxes->merge($fees);
			return $this->sendResponse(['transactions' => $transactions], 'All transactions');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}
}
