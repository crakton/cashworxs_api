<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Onboarding extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'onboarding';

    protected $fillable = [
        'user_id',
        'income',
        'bvn',
        'nin',
    ];
    protected $hidden = [
        'onboarding_data',
        'onboarding_stat',
    ];
}
