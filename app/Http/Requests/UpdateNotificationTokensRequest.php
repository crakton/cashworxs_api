<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTokensRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'onesignal_user_id' => 'nullable|string|max:255',
            'fcm_token' => 'nullable|string|max:500',
        ];
    }
}