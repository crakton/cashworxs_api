<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Services\UserNotificationService;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    protected $notificationService;

    public function __construct(UserNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware(['auth:api', 'auth:admin'], ['except' => ['login', 'register', 'forgotPassword', 'resetPassword', 'sendOTP', 'verifyOTP']]);
    }

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:11|unique:users',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->numbers()->letters()->mixedCase()->symbols()
                ],
                // 'password_confirmation' => 'required|same:password', // optional to add here but needed for password confirmation
            ]);

            $user = User::create([
                'full_name' => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'password' => Hash::make($validated['password']),
                'verified' => false,
            ]);

            // Register user with OneSignal after successful creation
            try {
                $oneSignalRegistered = $this->notificationService->registerUserWithOneSignal($user);
                if ($oneSignalRegistered) {
                    \Log::info("User {$user->id} successfully registered with OneSignal during registration");
                } else {
                    \Log::warning("Failed to register user {$user->id} with OneSignal during registration");
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the registration process
                \Log::error("OneSignal registration failed for user {$user->id} during registration: " . $e->getMessage());
            }

            $token = JWTAuth::fromUser($user);

            // Refresh user data to include OneSignal ID if it was set
            $user->refresh();

            return $this->sendResponse([
                'token' => $token,
                'user' => $user,
                'onesignal_registered' => isset($user->onesignal_user_id),
            ], 'Registration successful', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function sendOTP(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string|max:11',
                'type' => 'required|string|in:sms,robo_call',
            ]);

            // Generate OTP
            $otp = rand(100000, 999999);

            // Store OTP and token in cache for 24 hours (in minutes)
            $token = Str::random(60);
            Cache::put(
                "otp_{$request->phone_number}",
                ['otp' => $otp, 'token' => $token],
                1440
            );

            // Prepare parameters
            $to = '+234' . ltrim($request->phone_number, '0');
            $url = env('TERMII_BASE_URI') . '/sms/send';
            $apiKey = env('TERMII_API_KEY');
            $message = "Your OTP is: $otp";

            if (!$url || !$apiKey) {
                throw new \Exception('TERMII_BASE_URI or TERMII_API_KEY is missing in the environment file.');
            }

            $payload = [
                'to' => $to,
                'sms' => $message,
                'api_key' => $apiKey,
                "type" => "plain",
                "channel" => "generic",
                "from" => "careposting"
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            \Log::info('Termii Response: ' . $response->body());

            if ($response->successful()) {
                return $this->sendResponse([
                    'token' => $token,
                    'debug_otp' => $otp // REMOVE IN PRODUCTION
                ], 'OTP sent successfully');
            } else {
                return $this->sendError('Failed to send OTP', [
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string|max:11',
                'otp' => 'required|string|size:6',
                'token' => 'required|string',
            ]);

            // Retrieve the cached OTP data
            $cached = Cache::get("otp_{$request->phone_number}");

            // Log cached data for debugging
            \Log::info('Cached OTP Data:', ['cached' => $cached, 'request' => $request->all()]);
            
            // Validate the OTP and token
            if (!$cached || (string)$cached['token'] !== $request->token || (string)$cached['otp'] !== $request->otp) {
                return $this->sendError('Invalid OTP', [], 400);
            }
            
            // Clear the OTP from cache
            Cache::forget("otp_{$request->phone_number}");

            // Update the user
            $user = User::where('phone_number', $request->phone_number)->first();

            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }

            $user->update([
                'verified' => true,
                'phone_verified_at' => now(),
            ]);

            // Try to register with OneSignal if not already registered
            if (!$user->onesignal_user_id) {
                try {
                    $oneSignalRegistered = $this->notificationService->registerUserWithOneSignal($user);
                    if ($oneSignalRegistered) {
                        \Log::info("User {$user->id} successfully registered with OneSignal during OTP verification");
                    } else {
                        \Log::warning("Failed to register user {$user->id} with OneSignal during OTP verification");
                    }
                } catch (\Exception $e) {
                    \Log::error("OneSignal registration failed for user {$user->id} during OTP verification: " . $e->getMessage());
                }
            }

            return $this->sendResponse([], 'OTP verified successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string|max:11'
            ]);
            
            $user = User::where('phone_number', $request->phone_number)->first();

            if (!$user) {
                return $this->sendError('User not found', [], 404);
            }

            // Generate reset token
            $token = Str::random(60);
            Cache::put("pwd_reset_{$request->phone_number}", $token, 3600);

            return $this->sendResponse([
                'token' => $token,
            ], 'Password reset token generated');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'new_password' => [
                    'required',
                    'confirmed',
                    Password::min(8)->numbers()->letters()->mixedCase()->symbols()
                ],
            ]);

            $phone = $request->phone_number;
            $cached_token = Cache::get("pwd_reset_{$phone}");

            if (!$cached_token || $cached_token !== $request->token) {
                return $this->sendError('Invalid token', [], 400);
            }

            $user = User::where('phone_number', $phone)->first();
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Clear cache storage
            Cache::forget("pwd_reset_{$phone}");

            return $this->sendResponse([], 'Password reset successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string|max:11',
                'password' => 'required|string'
            ]);

            $user = User::where('phone_number', $request->phone_number)->first();

            if (!$token = auth('api')->attempt(['phone_number' => $request->phone_number, 'password' => $request->password])) {
                return $this->sendError('Invalid credentials', [], 401);
            }

            $authenticatedUser = auth('api')->user();

            // Ensure user is registered with OneSignal on login
            if (!$authenticatedUser->onesignal_user_id) {
                try {
                    $oneSignalRegistered = $this->notificationService->registerUserWithOneSignal($authenticatedUser);
                    if ($oneSignalRegistered) {
                        \Log::info("User {$authenticatedUser->id} successfully registered with OneSignal during login");
                        // Refresh user data to include OneSignal ID
                        // $authenticatedUser->refresh();
                    } else {
                        \Log::warning("Failed to register user {$authenticatedUser->id} with OneSignal during login");
                    }
                } catch (\Exception $e) {
                    \Log::error("OneSignal registration failed for user {$authenticatedUser->id} during login: " . $e->getMessage());
                }
            }

            return $this->sendResponse([
                'token' => $token,
                'user' => $authenticatedUser,
            ], 'Login successful');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    public function logout()
    {
        try {
            auth('api')->logout();
            return $this->sendResponse([], 'Successfully logged out');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to manually register current user with OneSignal
     */
    public function registerOneSignal(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            if ($user->onesignal_user_id) {
                return $this->sendResponse([
                    'onesignal_user_id' => $user->onesignal_user_id,
                    'already_registered' => true
                ], 'User already registered with OneSignal');
            }

            $success = $this->notificationService->registerUserWithOneSignal($user);

            if ($success) {
                // $user->refresh();
                return $this->sendResponse([
                    'onesignal_user_id' => $user->onesignal_user_id,
                    'registered' => true
                ], 'User successfully registered with OneSignal');
            } else {
                return $this->sendError('Failed to register with OneSignal', [], 500);
            }
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API endpoint to update user's push subscription
     */
    public function updatePushSubscription(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return $this->sendError('User not authenticated', [], 401);
            }

            $request->validate([
                'endpoint' => 'required|string',
                'p256dh' => 'required|string',
                'auth' => 'required|string',
            ]);

            $subscriptionData = [
                'endpoint' => $request->endpoint,
                'p256dh' => $request->p256dh,
                'auth' => $request->auth,
            ];

            $success = $this->notificationService->updateUserPushSubscription($user->id, $subscriptionData);

            if ($success) {
                return $this->sendResponse([], 'Push subscription updated successfully');
            } else {
                return $this->sendError('Failed to update push subscription', [], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendError('Validation Error', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong', ['error' => $e->getMessage()], 500);
        }
    }
}