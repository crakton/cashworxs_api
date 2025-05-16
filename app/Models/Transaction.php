<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'user_id',
        'fullname',
        'transaction_type',
        'transaction_name',
        'transaction_amount',
        'transaction_status',
        'transaction_metadata'
    ];

    protected $casts = [
        'transaction_metadata' => 'array'
    ];
}
