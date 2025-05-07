<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activities extends Model
{
    use HasUlids;
    use HasFactory;

    protected $table = 'activities';
    protected $fillable = [
        'type',
        'description',
        'title',
        'meta_info'
    ];

    // Add casts to automatically handle JSON conversion
    protected $casts = [
        'meta_info' => 'array',
    ];
}
