<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'state_id' => $this->state_id,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Notification preferences
            'notification_preferences' => $this->when(
                isset($this->notification_preferences),
                $this->notification_preferences
            ),
            
            // Notification tokens
            'onesignal_user_id' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                $this->onesignal_user_id
            ),
            'fcm_token' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                $this->fcm_token
            ),
            
            // State relationship
            'state_info' => $this->whenLoaded('stateInfo', function () {
                return new StateResource($this->stateInfo);
            }),
        ];
    }
}

