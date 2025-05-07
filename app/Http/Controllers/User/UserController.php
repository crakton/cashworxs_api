<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Api\BaseController;
use App\Models\Fee;
use App\Models\Tax;
use App\Models\User;
use App\Models\Activities;
use Illuminate\Http\Request;

class UserController extends BaseController
{
	public function greetUser(Request $request)
	{
		try {
			$user = $request->user();
			$currentTime = now();
			$hour = $currentTime->format('G');
			$isFirstLogin = is_null($user->last_login_at);

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

			return $this->sendResponse([
				'greeting' => $greeting,
				'isFirstLogin' => $isFirstLogin,
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
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function getAllUsers(Request $request)
	{
		try {
			return $this->sendResponse(['users' => User::all()]);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
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
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function updateUser(Request $request, $id)
	{
		try {
			// Fixed missing parentheses
			$user = User::where('id', $id)->first();

			if (!$user) {
				return $this->sendError('User not found', [], 404);
			}

			$user->update($request->all());

			return $this->sendResponse(['user' => $user], 'User updated successfully');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function getActivities()
	{
		try {
			// get activities from the database
			$activities = Activities::all();
			return $this->sendResponse(['activities' => $activities], 'Current taxes and fees regulations retrieved', 200);
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

	public function smackUser(Request $request)
	{
		try {
			// remove user record on the the users table
			$request->validate([
				'user_id' => 'required|string'
			]);

			User::where('id', $request->user_id)->delete();
			return $this->sendResponse('User KO!');
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

			// Fetch taxes and fees for the user
			$taxes = Tax::where('user_id', $user->id)->get();
			$fees = Fee::where('user_id', $user->id)->get();

			// Log the user ID and fetched data
			\Log::info('User ID:', ['user_id' => $user->id]);
			\Log::info('Taxes:', $taxes->toArray());
			\Log::info('Fees:', $fees->toArray());

			// Merge the collections
			$transactions = $taxes->concat($fees);

			// Log the merged transactions
			\Log::info('Merged Transactions:', $transactions->toArray());

			return $this->sendResponse(['transactions' => $transactions], 'All transactions');
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function createActivity(Request $request)
	{
		try {
			$request->validate([
				'type' => 'required|string',
				'description' => 'required|string',
				'title' => 'required|string',
				'meta_info' => 'required|array'
			]);

			// Create the activity with properly formatted data
			$activity = Activities::create([
				'type' => $request->type,
				'description' => $request->description,
				'title' => $request->title,
				'meta_info' => $request->meta_info // This will be automatically converted to JSON
			]);

			return $this->sendResponse(
				['activity' => $activity],
				'Activity created successfully',
				201
			);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function updateActivity(Request $request, $id)
	{
		try {
			$request->validate([
				'type' => 'string|nullable',
				'description' => 'string|nullable',
				'title' => 'string|nullable',
				'meta_info' => 'array|nullable'
			]);

			// Find the activity
			$activity = Activities::find($id);

			if (!$activity) {
				return $this->sendError('Activity not found', [], 404);
			}

			// Update the activity
			$activity->update([
				'type' => $request->type,
				'description' => $request->description,
				'title' => $request->title,
				'meta_info' => $request->meta_info // This will be automatically converted to JSON
			]);

			return $this->sendResponse(
				['activity' => $activity],
				'Activity updated successfully'
			);
		} catch (\Illuminate\Validation\ValidationException $e) {
			return $this->sendError('Validation Error', $e->errors(), 422);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}

	public function deleteActivity(Request $request, $id)
	{
		try {
			$activity = Activities::find($id);

			if (!$activity) {
				return $this->sendError('Activity not found', [], 404);
			}

			$activity->delete();

			return $this->sendResponse(
				[],
				'Activity deleted successfully'
			);
		} catch (\Exception $e) {
			return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
		}
	}
}
