<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasUlids;

    protected $table = 'taxes';
    protected $fillable = [
        'user_id',
        'tax_type',
        'tax_name',
        'tax_year',
        'tax_amount',
        'tax_rate',
        'tax_status',
        'tax_metadata',
        'gross_income'
    ];

    protected $hidden = ['tax_rate'];
}
