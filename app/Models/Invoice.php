<?php
// app/Models/Invoice.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'type',
        'client_id',
        'subcontractor_id',
        'security_staff_id',
        'site_id',
        'issue_date',
        'due_date',
        'date_from',
        'date_to',
        'total_amount',
        'tax_amount',
        'status',
        'notes',
        'payment_note',
        'rate_per_hour',
        'total_shift_hours',
        'total_duration_hours',
        'total_break_hours',
        'total_deductions_hours',
        'gross_amount',
        'net_amount'
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function subcontractor()
    {
        return $this->belongsTo(User::class, 'subcontractor_id');
    }

    public function securityStaff()
    {
        return $this->belongsTo(User::class, 'security_staff_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // Scopes
    public function scopeClientInvoices($query)
    {
        return $query->where('type', 'client');
    }

    public function scopeSubcontractorInvoices($query)
    {
        return $query->where('type', 'subcontractor');
    }

    public function scopeSecurityStaffInvoices($query)
    {
        return $query->where('type', 'security_staff');
    }

    // Generate invoice number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->invoice_number = static::generateInvoiceNumber($invoice->type);
        });
    }

    public static function generateInvoiceNumber($type = 'client')
    {
        $prefix = match($type) {
            'client' => 'CLI-INV',
            'subcontractor' => 'SUB-INV',
            'security_staff' => 'STAFF-INV',
            default => 'INV'
        };

        $latest = static::where('type', $type)->latest()->first();
        $number = $latest ? (int) substr($latest->invoice_number, -6) + 1 : 1;
        
        return $prefix . '-' . date('Ymd') . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}