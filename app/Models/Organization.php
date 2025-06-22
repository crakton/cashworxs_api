<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;
    use HasUlids;

    // Specify the primary key type as ULID
    protected $keyType = 'string';

    // Disable auto-incrementing as ULIDs are not integers
    public $incrementing = false;

    // Specify the fillable fields
    protected $fillable = [
        'id',
        'name',
        'type',
    ];

    /**
     * Define the relationship between Organization and Service.
     * An organization can have many services.
     */
    public function services()
    {
        return $this->hasMany(ServiceFees::class);
    }

     // Relationship with IdConfig
    public function idConfigs()
    {
        return $this->hasMany(IdConfig::class);
    }

    // Active ID configurations only
    public function activeIdConfigs()
    {
        return $this->hasMany(IdConfig::class)->where('is_active', true);
    }

    // Ordered ID configurations
    public function orderedIdConfigs()
    {
        return $this->hasMany(IdConfig::class)
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc');
    }

    // Relationship with State (if applicable)
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByState($query, $stateId)
    {
        return $query->where('state_id', $stateId);
    }
}
