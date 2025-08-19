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
    use LogsChanges;
    protected $fillable = ['invoice_number', 'client_id', 'security_staff_id ', 'due_date', 'notes','total_amount', 'invoice_title', 'date_from', 'date_to', 'invoice_date', 'site_group_id', 'total_shift_hours', 'total_duration_hours', 'total_deductions_hours', 'gross_amount', 'net_amount', 'payment_note', 'rate_per_hour', 'total_break_hours'];

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
            // Generate invoice number
            $latestInvoice = Invoice::latest('id')->first();
            $nextInvoiceNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_no, 4)) + 1 : 1;
            $invoice->invoice_no = 'INV-' . str_pad($nextInvoiceNumber, 5, '0', STR_PAD_LEFT);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
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
