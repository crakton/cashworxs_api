<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'invoice_number',
        'mda_id',
        'mda_code',
        'tdate',
        'amount',
        'c_code',
        'c_name',
        'c_address',
        'c_phone',
        'c_number',
        'c_email',
        'client_invoice_number',
        'status',
        'note',
        'log_time',
        // Gateway fields
        'gateway',
        'gateway_invoice_id',
        'gateway_response',
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
        'year_of_assessment' => 'integer'
    ];

    /**
     * Get the items for the invoice.
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the payment for the invoice.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class, 'invoice_number', 'invoice_number');
    }

    /**
     * Get the transactions for the invoice.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'user_id')
            ->whereJsonContains('transaction_metadata->invoice_number', $this->invoice_number);
    }

    /**
     * Check if invoice has been paid
     */
    public function isPaid()
    {
        return $this->status == 1;
    }

    /**
     * Get the gateway used for this invoice
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
     * Scope to filter unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', '!=', 1);
    }

    /**
     * Scope to filter paid invoices
     */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }
}