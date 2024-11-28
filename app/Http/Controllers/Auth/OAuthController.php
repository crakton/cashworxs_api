<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends BaseController
{
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider)
    {
        try {
            $this->redirectToProvider($provider);
            $oauthUser = Socialite::driver($provider)->stateless()->user();
            $user = User::firstOrCreate([
                'email' => $oauthUser->getEmail(),
                [
                    'full_name' => $oauthUser->getName(),
                    'provider_id' => $oauthUser->getId(),
                    'provider' => $provider,
                    'email_verified_at' => now()
                ]
            ]);
            // Generate JWT token
            $token = auth('api')->login($user);

            return $this->sendResponse([
                'token' => $token,
                'user' => $user,
            ], 'Signin successful');
        } catch (\Exception $e) {
            return $this->sendError('Unable to authenticate', ['error' => $e->getMessage()]);
        }
    }
}
