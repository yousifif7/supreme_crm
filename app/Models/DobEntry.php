<?php

namespace App\Models;

use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;

class DobEntry extends Model
{
    use BelongsToAdmin;

    protected $table = 'dob_entries';

    protected $casts = [
        'location' => 'array',
    ];

    protected $appends = ['formatted_address'];

    protected $fillable = [
        'admin_id',
        'user_id',
        'shift_id',
        'entry_type',
        'title',
        'description',
        'location',
        'timestamp',
        'admin_comments',
        'edit_requested',
        'formatted_address',
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

    protected static function booted()
    {
        static::saving(function ($entry) {
            if ($entry->location) {
                $location = is_string($entry->location) ? json_decode($entry->location, true) : $entry->location;

                if (!empty($location['latitude']) && !empty($location['longitude'])) {
                    $geo = app(\App\Services\GeoService::class);
                    $geoResult = $geo->getAddressFromCoordinates($location['latitude'], $location['longitude']);
                    // store only the human-readable formatted address (string) to avoid array=>string conversion
                    $entry->formatted_address = is_array($geoResult) ? ($geoResult['formatted_address'] ?? null) : $geoResult;
                }
            }
        });
    }

    public function getFormattedAddressAttribute()
    {
        // If the column is present (persisted), return it; otherwise, try to compute from location
        if (!empty($this->attributes['formatted_address'])) {
            return $this->attributes['formatted_address'];
        }

        $location = $this->location ?? [];
        if (!empty($location['address'])) {
            return $location['address'];
        }
        if (!empty($location['latitude']) && !empty($location['longitude'])) {
            $geoResult = app(\App\Services\GeoService::class)->getAddressFromCoordinates($location['latitude'], $location['longitude']);
            return is_array($geoResult) ? ($geoResult['formatted_address'] ?? null) : $geoResult;
        }
        return null;
    }
}
