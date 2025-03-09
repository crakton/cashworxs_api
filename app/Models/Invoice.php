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
        'log_time'
    ];
    // Explicitly set the primary key to 'id'
    protected $primaryKey = 'id';

    // Specify that the primary key is a string
    protected $keyType = 'number';

    // Disable auto-incrementing
    public $incrementing = false;

    protected $hidden = ['user_id'];

    protected $casts = [
        'tdate' => 'datetime',
        'log_time' => 'datetime',
        'amount' => 'decimal:2',
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
     * Check if invoice has been paid
     */
    public function isPaid()
    {
        return $this->status == 1;
    }
}
