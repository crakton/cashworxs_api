<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceTaxes extends Model
{
  use HasUlids;
  use HasFactory;

  protected $table = 'service_taxes';
  protected $fillable = [
    'id',
    'type',
    'state',
    'name',
    'amount',
    'status',
    'description',
    'metadata'
  ];
  protected $casts = [
    'metadata' => 'array',
  ];
}
