<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsChanges;
use App\Traits\BelongsToAdmin;

class Shift extends Model
{
    // use LogsChanges;
    use BelongsToAdmin;

    protected $fillable = ['admin_id', 'client_id', 'site_id','checkpoints','user_id', 'company_id', 'staff_id', 'subcontractor_id', 'start_shift', 'end_shift', 'break-mins_shift', 'number_shift', 'site_rate', 'service_type_1', 'service_type_2', 'from_shift', 'to_shift', 'comments', 'days', 'employee_rate', 'start', 'end', 'po_number', 'lost_time', 'po_rate', 'manager_1_id', 'manager_2_id', 'restrict_start_time', 'enforce_picture_check', 'restrict_location_check', 'is_assign', 'book_in_time', 'book_off_time'];

    public function client()
    {
        return $this->belongsTo(User::class,'client_id');
    }
    public function site()
    {
        return $this->belongsTo(Site::class);
    }
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id')->with('subcontractor');
    }
    public function subcontractor()
    {
        return $this->belongsTo(Subcontractor::class, 'subcontractor_id'); // or Employee::class based on your DB
    }
    public function shiftDates()
    {
        return $this->hasMany(ShiftDate::class);
    }
    
}
