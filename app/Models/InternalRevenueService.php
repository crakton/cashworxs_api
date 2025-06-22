<?php

namespace App\Models;

use App\Models\State;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalRevenueService extends Model
{
    use HasFactory;
    use HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'irs_name',
        'short_name',
        'state_id',
        'website',
        'contacts',
        'is_active',
    ];

    protected $casts = [
        'contacts' => 'json',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
