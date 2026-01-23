<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EmployeeBan extends Model
{
    protected $table = 'employee_bans';

    protected $fillable = [
        'employee_id',
        'site_id',
        'client_id',
        'reason',
        'created_by'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function site()
    {
        return $this->belongsTo(\App\Models\Site::class, 'site_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Check if an employee is banned for a given site or client.
     * Search order: if $siteId provided, check site-specific bans first,
     * then client/global bans. If no $siteId, check client-wide then global bans.
     *
     * @param int $employeeId
     * @param int|null $siteId
     * @param int|null $clientId
     * @return bool
     */
    public static function isBannedFor(int $employeeId, ?int $siteId = null, ?int $clientId = null): bool
    {
        // Always check for a global ban (both site_id and client_id NULL)
        $global = self::where('employee_id', $employeeId)
            ->whereNull('site_id')
            ->whereNull('client_id')
            ->exists();

        if ($global) return true;

        if ($siteId) {
            // Site-specific ban
            $siteBan = self::where('employee_id', $employeeId)
                ->where('site_id', $siteId)
                ->exists();
            if ($siteBan) return true;

            // Client-wide ban (site_id NULL but client_id matches)
            if ($clientId) {
                $clientBan = self::where('employee_id', $employeeId)
                    ->whereNull('site_id')
                    ->where('client_id', $clientId)
                    ->exists();
                if ($clientBan) return true;
            }

            return false;
        }

        // No site provided: check client-level ban (including global handled above)
        if ($clientId) {
            $clientBan = self::where('employee_id', $employeeId)
                ->whereNull('site_id')
                ->where('client_id', $clientId)
                ->exists();
            if ($clientBan) return true;
        }

        return false;
    }
}
