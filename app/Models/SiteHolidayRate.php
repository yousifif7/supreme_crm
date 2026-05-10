<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteHolidayRate extends Model
{
    protected $fillable = [
        'site_id',
        'holiday_name',
        'holiday_date',
        'site_rate',
        'guard_rate',
    ];

    protected $casts = [
        'holiday_date' => 'date',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
