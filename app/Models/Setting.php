<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{

	use HasUlids;
	use HasFactory;

	protected $table = 'settings';

	protected $fillable = [
		'user_id',
		'language',
	];
}
