<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentMedia extends Model
{
    //
    protected $fillable = ['incident_report_id','type', 'file_url'];

    public function report() {
        return $this->belongsTo(IncidentReport::class);
    }

}
