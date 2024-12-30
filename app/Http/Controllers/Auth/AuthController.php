<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'resetPassword', 'sendOTP', 'verifyOTP']]);
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

            $token = JWTAuth::fromUser($user);

            return $this->sendResponse([
                'token' => $token,
                'user' => $user,
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

            // Store OTP and token in cache for 5 minutes
            $token = Str::random(60);
            Cache::put(
                "otp_{$request->phone_number}",
                ['otp' => $otp, 'token' => $token],
                300
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

            $curl = curl_init();

            $post_data = json_encode($payload);

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.ng.termii.com/api/sms/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $post_data,
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json"
                ),
            ));

            $response = curl_exec($curl);

            \Log::info('Termii Response: ' . $response);

            curl_close($curl);

            if ($response) {
                return $this->sendResponse([
                    'token' => $token,
                    // 'debug_otp' => $otp // REMOVE IN PRODUCTION
                ], 'OTP sent successfully');
            } else {
                return $this->sendError('Failed to send OTP');
            }


            // $response = Http::withHeaders([
            //     'Content-Type' => 'application/json',
            // ])->post($url, $payload);

            // \Log::info('Termii Response: ' . $response->body());

            // if ($response->successful()) {
            //     return $this->sendResponse([
            //         'token' => $token,
            //         // 'debug_otp' => $otp // REMOVE IN PRODUCTION
            //     ], 'OTP sent successfully');
            // } else {
            //     return $this->sendError('Failed to send OTP', [
            //         'error' => $response->body()
            //     ], $response->status());
            // }
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

            return $this->sendResponse([
                'token' => $token,
                'user' => auth('api')->user(),
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
}
