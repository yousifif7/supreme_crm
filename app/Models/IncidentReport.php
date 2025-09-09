<?php

namespace App\Models;

use App\Services\GeoService;
use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    //

    protected $casts = [
        'location' => 'array',
    ];
    protected $appends = ['formatted_address'];

    protected $fillable = [
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
                    $incident->formatted_address = $geo->getAddressFromCoordinates(
                        $location['latitude'],
                        $location['longitude']
                    );
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
            return app(GeoService::class)
                ->getAddressFromCoordinates($location['latitude'], $location['longitude']);
        }
        return null;
    }
}
