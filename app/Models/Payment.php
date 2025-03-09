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
        'tdate',
        'amount',
        'note',
        'status',
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
     * Get the invoice associated with the payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_number', 'invoice_number');
    }
}
