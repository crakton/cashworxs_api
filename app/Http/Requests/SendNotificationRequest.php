<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'type' => 'required|in:admin,state,personal',
            'state' => 'required_if:type,state|string|exists:states,code',
            'user_id' => 'required_if:type,personal|string|exists:users,id',
            'scheduled_at' => 'nullable|date|after:now',
            'metadata' => 'nullable|array',
            'metadata.image_url' => 'nullable|url',
            'metadata.action_url' => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages()
    {
        return [
            'state.required_if' => 'State is required for state-based notifications.',
            'user_id.required_if' => 'User ID is required for personal notifications.',
            'scheduled_at.after' => 'Scheduled time must be in the future.',
        ];
    }
}