<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdConfig extends Model
{
    use HasFactory, HasUlids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'organization_id',
        'field_name',
        'field_label',
        'field_type',
        'is_required',
        'validation_rules',
        'help_text',
        'sort_order',
        'is_active',
    ];

     protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'validation_rules' => 'json',
        'sort_order' => 'integer',
    ];

    // Field type constants
    const FIELD_TYPE_TEXT = 'text';
    const FIELD_TYPE_NUMBER = 'number';
    const FIELD_TYPE_EMAIL = 'email';
    const FIELD_TYPE_PHONE = 'phone';
    const FIELD_TYPE_FILE = 'file';

    // Available field types
    public static function getFieldTypes()
    {
        return [
            self::FIELD_TYPE_TEXT => 'Text',
            self::FIELD_TYPE_NUMBER => 'Number',
            self::FIELD_TYPE_EMAIL => 'Email',
            self::FIELD_TYPE_PHONE => 'Phone',
            self::FIELD_TYPE_FILE => 'File',
        ];
    }

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

     public function scopeByFieldType($query, $fieldType)
    {
        return $query->where('field_type', $fieldType);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }


    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'asc');
    }
}