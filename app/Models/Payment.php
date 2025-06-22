<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'invoice_number',
        'receipt_no',
        'payment_reference',
        'tdate',
        'amount',
        'note',
        'status',
        'log_time',
        // Gateway fields
        'gateway',
        'gateway_payment_id',
        'gateway_response',
        'verification_status',
        // Custom fields
        'year_of_assessment',
        'irs_id',
        'irs_name',
        'tax_type',
        'fullname',
        'custom_fields'
    ];

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'tdate' => 'datetime',
        'log_time' => 'datetime',
        'amount' => 'decimal:2',
        'custom_fields' => 'array',
        'gateway_response' => 'array',
        'year_of_assessment' => 'integer',
        'verification_status' => 'boolean'
    ];

    /**
     * Get the invoice for this payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_number', 'invoice_number');
    }

    /**
     * Get the user for this payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transactions for this payment.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'user_id')
            ->where('transaction_type', 'payment')
            ->where(function($query) {
                $query->whereJsonContains('transaction_metadata->receipt_no', $this->receipt_no)
                      ->orWhereJsonContains('transaction_metadata->reference', $this->payment_reference);
            });
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted()
    {
        return $this->status == 1 || $this->status === 'completed';
    }

    /**
     * Check if payment is verified
     */
    public function isVerified()
    {
        return $this->verification_status === true;
    }

    /**
     * Get the gateway used for this payment
     */
    public function getGatewayAttribute()
    {
        return $this->attributes['gateway'] ?? 'local';
    }

    /**
     * Scope to filter by gateway
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope to filter completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 1)->orWhere('status', 'completed');
    }

    /**
     * Scope to filter pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 0)->orWhere('status', 'pending');
    }

    /**
     * Scope to filter failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to filter verified payments
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', true);
    }

    /**
     * Scope to filter unverified payments
     */
    public function scopeUnverified($query)
    {
        return $query->where('verification_status', false)->orWhereNull('verification_status');
    }
}