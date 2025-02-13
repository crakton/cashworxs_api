<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements JWTSubject
{
	use HasUlids;
	use HasFactory;
	use Notifiable;

	protected $table = 'users';

	protected $fillable = [
		'is_admin',
		'full_name',
		'phone_number',
		'password',
		'phone_verified_at',
		'verified',
		'email',
		'email_verified_at',
		'provider',
		'provider_id',
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $casts = [
		'phone_verified_at' => 'datetime',
		'email_verified_at' => 'datetime',
		'verified' => 'boolean',
		'is_admin' => 'boolean'
	];


	// Get the identifier that will be stored in the subject claim of the JWT
	public function getJWTIdentifier()
	{
		return $this->getKey();
	}


	// Return a key value array with any custom claims added to the JWT
	public function getJWTCustomClaims()
	{
		return [];
	}
}
