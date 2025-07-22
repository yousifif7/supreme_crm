<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\EmergencyContacts;
use App\Models\BankDetails;

class Profile extends Model
{
    //
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'address',
        'face_data'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function emergencyContact() {
        return $this->hasOne(EmergencyContacts::class);
    }

    public function bankDetail() {
        return $this->hasOne(BankDetails::class);
    }
}
