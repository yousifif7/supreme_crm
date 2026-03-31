<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteStaffRate extends Model
{
    protected $table = 'site_staff_rates';

    protected $fillable = [
        'site_id',
        'user_id',
        'guard_rate',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
