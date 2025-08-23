<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use LogsChanges;
    
    protected $fillable = ['invoice_number', 'client_id', 'security_staff_id', 'due_date', 'notes','total_amount', 'invoice_title', 'date_from', 'date_to', 'invoice_date', 'site_group_id', 'total_shift_hours', 'total_duration_hours', 'total_deductions_hours', 'gross_amount', 'net_amount', 'payment_note', 'rate_per_hour', 'total_break_hours'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'security_staff_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_group_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            // Generate invoice number
            $latestInvoice = Invoice::latest('id')->first();
            $nextInvoiceNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_number, 4)) + 1 : 1;
            $invoice->invoice_number = 'INV-' . str_pad($nextInvoiceNumber, 5, '0', STR_PAD_LEFT);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shiftdates()
    {
        return $this->hasMany(ShiftDate::class);
    }

    public function adminReview()
    {
        return $this->hasOne(InvoiceReview::class);
    }

    protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
    'submitted_at' => 'datetime',
    'paid_at' => 'datetime',
];
}
