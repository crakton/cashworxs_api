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
}
