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
				['id' => 1, 'type' => 'Pay Taxes', 'description' => 'Taxes are a mandatory contribution levied on corporation or individuals to finance government activities and public service', 	'title' => 'Types of Taxes Individuals Pay: A Simple Guide', 'meta_info' => [

					[
						'name' => 'Income Tax:',
						'description' => '• This is the tax you pay on the money you earn from jobs, freelancing, investments, or any other sources of income. If you\'re an employee, this is usually deducted from your salary by your employer (Pay-As-You-Earn or PAYE). If you\'re self-employed, you calculate and pay it yourself.'
					],
					[
						'name' => 'Property Tax:',
						'description' => '• If you own a house or land, you may need to pay property tax. This is usually based on the value of your property and helps fund local services like schools, police, and fire departments.'
					],
					[
						'name' => 'Capital Gains Tax:',
						'description' => '• This tax applies when you sell an asset like a house, shares, or other investments for more than you paid for it. The profit (or "gain") is what\'s taxed.'
					],

				]],
				['id' => 2, 'type' => 'Pay Fees', 'description' => 'Fees are payment made to a professional person or to a private or Government body in exchange for a service', 	'title' => 'Types of Fees Individuals Pay: A Simple Guide', 'meta_info' => [

					[
						'name' => 'Application Fees:',
						'description' => '•  Fees paid when applying for various services or permits, such as business registration or immigration services.'
					],
					[
						'name' => 'Permit and License Fees:',
						'description' => '• Charges for obtaining necessary licenses and permits to operate businesses or undertake specific activities, including trade licenses, construction permits, and environmental permits.'
					],
					[
						'name' => 'Registration Fees:',
						'description' => '• Fees associated with registering a business name, a company, or property with government authorities.'
					],
					[
						'name' => 'Renewal Fees:',
						'description' => '• Charges for renewing licenses, permits, or registrations periodically, ensuring compliance with regulatory standards.'
					],
					[
						'name' => 'Utility Fees:',
						'description' => '•  Costs incurred for essential services like electricity, water, and waste management, which often include service connection fees.'
					],
					[
						'name' => 'Court Fees:',
						'description' => '• Payments required for filing legal documents in court, which vary based on the nature of the case'
					],

				]],

			];

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
}
