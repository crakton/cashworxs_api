<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationRecipientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'notification_id' => $this->notification_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
            'read_at' => $this->read_at?->format('Y-m-d H:i:s'),
            'error_message' => $this->error_message,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'notification' => $this->whenLoaded('notification', function () {
                return [
                    'id' => $this->notification->id,
                    'title' => $this->notification->title,
                    'message' => $this->notification->message,
                    'type' => $this->notification->type,
                    'status' => $this->notification->status,
                    'metadata' => $this->notification->metadata,
                ];
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->full_name,
                    'email' => $this->user->email,
                    'state' => $this->user->state,
                ];
            }),
            
            // Computed fields
            'is_read' => !is_null($this->read_at),
            'is_sent' => $this->status === 'sent',
            'has_error' => !is_null($this->error_message),
        ];
    }
}