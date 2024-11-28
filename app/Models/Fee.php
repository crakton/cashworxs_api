<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasUlids;

    protected $table = 'fees';
    protected $fillable = [
        'user_id',
        'fee_type',
        'fee_name',
        'fee_amount',
        'fee_status',
        'fee_metadata'
    ];
}
