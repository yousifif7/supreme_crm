<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestrictionOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity_id',
        'entity_type',
        'restriction_type',
        'reason',
    ];

    /**
     * User who overrode the restriction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Polymorphic relation to the entity being overridden (e.g., Employee, ShiftDate, etc.)
     */
    public function entity()
    {
        return $this->morphTo();
    }
}
