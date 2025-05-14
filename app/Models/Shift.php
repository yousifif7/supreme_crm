<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['client_id', 'site_id', 'company_id', 'staff_id', 'subcontractor_id', 'start_shift', 'end_shift', 'break-mins_shift', 'number_shift', 'office_rate', 'service_type_1', 'service_type_2', 'from_shift', 'to_shift', 'comments', 'days', 'guard_rate', 'start', 'end', 'po_number', 'lost_time', 'po_rate', 'manager_1_id', 'manager_2_id', 'restrict_start_time', 'enforce_picture_check', 'restrict_location_check'];
}
