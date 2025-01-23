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

	// // Don't disclose this
	protected $hidden = [
		'organization_id'
	];
	// Cast the metadata field to JSON
	protected $casts = [
		'metadata' => 'array',
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
