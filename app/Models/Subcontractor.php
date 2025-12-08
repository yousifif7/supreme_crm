<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subcontractor extends Model
{
    use SoftDeletes, LogsChanges;
    protected $table = "sub_contractors";
    protected $fillable = [
        'user_id',
        'company_name',
        'company_address',
        'contact_person',
        'contact_number',
        'email',
        'invoice_terms',
        'payment_terms',
        'department',
        'vat_registered',
        'vat_number',
        'commission',
        'pay_rate',
        'pmva_trained_officer',
        'is_active',
    ];

    // Optionally hide sensitive fields
    protected $hidden = [
        'password',
    ];

    /**
     * Get the user that owns the subcontractor
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the username (email from users table)
     */
    public function getUsernameAttribute()
    {
        return $this->user ? $this->user->email : null;
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
