<?php
// app/Models/InvoiceItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'shift_id',
        'shift_date_id',
        'security_staff_id',
        'site_id',
        'date',
        'description',
        'start_time',
        'end_time',
        'hours',
        'break_hours',
        'book_on_hours',
        'book_off_hours',
        'rate',
        'amount'
    ];

    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function shiftDate()
    {
        return $this->belongsTo(ShiftDate::class);
    }

    public function securityStaff()
    {
        return $this->belongsTo(User::class, 'security_staff_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class,'site_id');
    }
}