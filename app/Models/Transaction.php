<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Transaction extends Model
{
    use HasUlids, HasFactory;

    protected $fillable = [
        'user_id',
        'fullname',
        'transaction_type',
        'transaction_name',
        'transaction_amount',
        'transaction_status',
        'transaction_metadata',
        'gateway',
        'reference_id'
    ];

    protected $casts = [
        'transaction_metadata' => 'array',
        'transaction_amount' => 'decimal:2'
    ];

    /**
     * Get the user for this transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related invoice if applicable.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'reference_id', 'invoice_number')
                    ->where('transaction_type', 'invoice');
    }

    /**
     * Get the related payment if applicable.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'reference_id', 'receipt_no')
                    ->where('transaction_type', 'payment');
    }

    /**
     * Scope to filter by transaction type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope to filter by transaction status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('transaction_status', $status);
    }

    /**
     * Scope to filter by gateway
     */
    public function scopeByGateway($query, $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope to filter completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('transaction_status', 'completed');
    }

    /**
     * Scope to filter pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('transaction_status', 'pending');
    }

    /**
     * Scope to filter failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('transaction_status', 'failed');
    }

    /**
     * Get formatted transaction amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->transaction_amount, 2);
    }

    /**
     * Get transaction gateway or default
     */
    public function getGatewayAttribute()
    {
        return $this->attributes['gateway'] ?? 'local';
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful()
    {
        return $this->transaction_status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending()
    {
        return $this->transaction_status === 'pending';
    }

    /**
     * Check if transaction has failed
     */
    public function hasFailed()
    {
        return $this->transaction_status === 'failed';
    }
}