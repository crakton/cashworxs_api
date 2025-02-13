<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'tdate',
        'note',
        'amount',
        'c_code',
        'c_name',
        'c_address',
        'c_phone',
        'c_number',
        'c_email',
        'client_invoice_number',
        'status'
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
