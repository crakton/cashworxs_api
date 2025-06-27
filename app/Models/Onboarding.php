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
        'onboarding_data',
    ];


    // Remove onboarding_data from hidden if you want to see it in responses
    protected $hidden = [
        'onboarding_stat',
    ];
}