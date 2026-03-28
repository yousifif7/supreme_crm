<?php

namespace App\Models;

use App\Traits\LogsChanges;
use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentationUpload extends Model
{
    use SoftDeletes, LogsChanges, BelongsToAdmin;
    protected $fillable = [
        'admin_id',
        'vehicle_id',
        'mot_certificate_path',
        'insurance_certificate_path',
        'v5c_logbook_path',
        'tax_confirmation_path',
        'tachograph_certificate_path',
        'service_report_path',
        'inspection_report_path',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
