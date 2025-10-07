<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DobEntry extends Model
{

    protected $table = 'dob_entries';

    protected $fillable= [
        'user_id',
        'shift_id',
        'entry_type',
        'title',
        'description',
        'description',
        'location',
        'timestamp',
        'admin_comments',
        'edit_requested',
    ];

    public function media()
    {
        return $this->hasMany(DobMedia::class);
    }
    
    public function user()
    {
        return $this->hasMany(User::class, 'user_id');
    }

    public function shiftdate()
    {
        return $this->hasMany(ShiftDate::class);
    }
}
