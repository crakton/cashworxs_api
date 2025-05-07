<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceFees extends Model
{
	use HasUlids;
	use HasFactory;

	protected $table = 'service_fees';
	protected $fillable = [
		'id',
		'name',
		'type',
		'state',
		'amount',
		'status',
		'description',
		'metadata',
		'organization_id',
	];

	// Remove organization_id from hidden
	// We need it to be visible for proper relationship handling
	protected $hidden = [];

	// Cast the metadata field to JSON and status to boolean
	protected $casts = [
		'status' => 'boolean',
		'metadata' => 'array',
		'amount' => 'float'
	];

	/**
	 * Define the relationship between Service and Organization.
	 * A service belongs to an organization.
	 */
	public function organization()
	{
		return $this->belongsTo(Organization::class);
	}
}
