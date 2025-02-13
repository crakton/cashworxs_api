<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_number',
        'tdate',
        'amount',
        'receipt_no',
        'status'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_number', 'invoice_number');
    }
}
