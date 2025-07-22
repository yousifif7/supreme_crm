<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceReview extends Model
{
    protected $fillable = [
        'invoice_id',
        'revised_amount',
        'revision_reason',
        'requires_confirmation',
        'accepted',
        'notes'
    ];

    protected $casts = [
        'revised_amount' => 'float',
        'requires_confirmation' => 'boolean',
        'accepted' => 'boolean'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
