<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'status' => $this->status,
            'state' => $this->state,
            'metadata' => $this->metadata,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i:s'),
            'sent_at' => $this->sent_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->full_name,
                    'email' => $this->sender->email,
                    'role' => $this->sender->role,
                ];
            }),
            
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->full_name,
                    'email' => $this->user->email,
                ];
            }),
            
            'state_info' => $this->whenLoaded('stateInfo', function () {
                return new StateResource($this->stateInfo);
            }),
            
            'recipients' => $this->whenLoaded('recipients', function () {
                return $this->recipients->map(function ($recipient) {
                    return [
                        'id' => $recipient->id,
                        'status' => $recipient->status,
                        'sent_at' => $recipient->sent_at?->format('Y-m-d H:i:s'),
                        'read_at' => $recipient->read_at?->format('Y-m-d H:i:s'),
                        'user' => $recipient->whenLoaded('user', [
                            'id' => $recipient->user->id,
                            'name' => $recipient->user->full_name,
                            'email' => $recipient->user->email,
                            'state' => $recipient->user->state,
                        ]),
                    ];
                });
            }),
            
            // Computed fields
            'recipient_count' => $this->when(
                $this->relationLoaded('recipients'), 
                $this->recipients->count()
            ),
            'read_count' => $this->when(
                $this->relationLoaded('recipients'), 
                $this->recipients->whereNotNull('read_at')->count()
            ),
            'sent_count' => $this->when(
                $this->relationLoaded('recipients'), 
                $this->recipients->where('status', 'sent')->count()
            ),
        ];
    }
}

