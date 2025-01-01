<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUlids;
    use HasFactory;

    protected $table = 'transactions';
    protected $fillable = [
        'transaction_status',
        'transaction_ref',
        'transaction_amount',
        'transaction_type',
        'transaction_name',
    ];
    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function taxes()
    {
        return $this->hasMany(Tax::class);
    }
}
