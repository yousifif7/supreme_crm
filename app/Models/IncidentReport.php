<?php

namespace App\Models;

use App\Services\GeoService;
use App\Traits\BelongsToAdmin;
use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    use BelongsToAdmin;

    protected $casts = [
        'location' => 'array',
    ];
    protected $appends = ['formatted_address'];

    protected $fillable = [
        'admin_id',
        'user_id',
        'shift_id',
        'category',
        'severity',
        'title',
        'description',
        'location',
        'police_notified',
        'police_reference',
        'immediate_action_taken',
        'status',
        'formatted_address', // add this
    ];

    public function media()
    {
        return $this->hasMany(IncidentMedia::class);
    }

    public function people()
    {
        return $this->hasMany(IncidentPerson::class);
    }

    protected static function booted()
    {
        static::saving(function ($incident) {
            if ($incident->location) {
                $location = is_string($incident->location)
                    ? json_decode($incident->location, true)
                    : $incident->location;

                if (!empty($location['latitude']) && !empty($location['longitude'])) {
                    $geo = app(GeoService::class);
                    $geoResult = $geo->getAddressFromCoordinates(
                        $location['latitude'],
                        $location['longitude']
                    );
                    // store only the human-readable formatted address (string) to avoid array=>string conversion
                    $incident->formatted_address = is_array($geoResult) ? ($geoResult['formatted_address'] ?? null) : $geoResult;
                }
            }
        });
    }

    public function getFormattedAddressAttribute()
    {
        $location = $this->location;
        if (!empty($location['address'])) {
            return $location['address'];
        }
        if (!empty($location['latitude']) && !empty($location['longitude'])) {
            $geoResult = app(GeoService::class)
                ->getAddressFromCoordinates($location['latitude'], $location['longitude']);
            return is_array($geoResult) ? ($geoResult['formatted_address'] ?? null) : $geoResult;
        }
        return null;
    }
    public function logs()
{
    return $this->morphMany(Log::class, 'loggable');
}
}
