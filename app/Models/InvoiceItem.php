<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'i_name',
        'i_code',
        'i_amount',
        'note'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
