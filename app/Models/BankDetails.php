<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDetails extends Model
{
    //
    protected $fillable = [
        'profile_id',
        'account_name',
        'account_number',
        'sort_code',
        'bank_name',
    ];
}
