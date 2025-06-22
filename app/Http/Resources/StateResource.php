<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class StateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'capital' => $this->capital,
            'zone' => $this->zone,
            'region' => $this->region,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'internal_revenue_service' => $this->whenLoaded('internalRevenueService', function () {
                return new InternalRevenueServiceResource($this->internalRevenueService);
            }),
            
            'users' => $this->whenLoaded('users', function () {
                return $this->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                    ];
                });
            }),
            
            // Computed fields
            'users_count' => $this->when(
                $this->relationLoaded('users'), 
                $this->users->count()
            ),
            'active_users_count' => $this->when(
                $this->relationLoaded('users'), 
                $this->users->where('is_active', true)->count()
            ),
            'has_irs' => $this->when(
                $this->relationLoaded('internalRevenueService'), 
                !is_null($this->internalRevenueService)
            ),
        ];
    }
}