<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternalRevenueServiceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'irs_name' => $this->irs_name,
            'short_name' => $this->short_name,
            'website' => $this->website,
            'contacts' => $this->contacts,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Relationships
            'state' => $this->whenLoaded('state', function () {
                return [
                    'id' => $this->state->id,
                    'name' => $this->state->name,
                    'code' => $this->state->code,
                    'capital' => $this->state->capital,
                    'zone' => $this->state->zone,
                    'region' => $this->state->region,
                    'is_active' => $this->state->is_active,
                ];
            }),
            
            // Computed fields
            'contacts_count' => is_array($this->contacts) ? count($this->contacts) : 0,
            'primary_contact' => $this->when(
                is_array($this->contacts) && count($this->contacts) > 0,
                $this->contacts[0] ?? null
            ),
        ];
    }
}
