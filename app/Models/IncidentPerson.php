<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentPerson extends Model
{
    //
    protected $fillable = ['incident_report_id','name', 'role', 'contact', 'description'];

    public function report() {
        return $this->belongsTo(IncidentReport::class);
    }
}
