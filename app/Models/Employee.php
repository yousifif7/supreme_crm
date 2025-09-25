<?php

namespace App\Models;

use App\Traits\LogsChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes, LogsChanges;

    protected $fillable = [
        'user_id',
        'status',
        'fore_name',
        'sur_name',
        'email',
        'gender',
        'ni_number',
        'sia_licence',
        'sia_expiry',
        'licence_type',
        'entry_date',
        'dob',
        'employment_start_date',
        'employment_end_date',
        'service_type',
        'visa_type',
        'visa_expiry',
        'place_work',
        'hour_per_week',
        'passport_no',
        'passport_expiry',
        'address_group',
        'address_group_additional',
        'contact',
        'emergency_contact',
        'job_title',
        'nationality_id',
        'nationality',
        'pin',
        'reference_to_emp',
        'kin_id',
        'next_kin',
        'relation_with_kin',
        'kin_address',
        'kin_number',
        'kin_work_tel',
        'kin_mobile',
        'share_code',
        'settlement',
        'biometric_residence_permit',
        'biometric_residence_permit_expiry',
        'brp_status',
        'gourd_rate',
        'department_id',
        'subcontractor',
        'tags',
        'additional_sia_number',
        'license_expiry',
        'license_number',
        'dbs_confirmed',
        'prfoile_picture',
        'employee_type',
        'current_endorsement',
        'driving_license',
        'vehicle_in_use',
        'visa_to_work',
        'collar',
        'waist',
        'jacket',
        'shoe',
        'inseam',
        'signature',
        'guard_rate',
        'payment_period',
        'fixed_pay',
        'account_name',
        'account_number',
        'sort_code',
        'bank_name',
        'bank_branch',
        'other_info',
        'holidays_entitlement',
        'holiday_from',
        'holiday_to',
        'holidays_entitlement_additional',
        'holiday_from_additional',
        'holiday_to_additional',
        'profile_picture',
        'sia_licence_file',
        'passport_file',
        'proof_of_address_file',
        'ni_letter_file',
        'first_aid_certificate_file',
        'act_certificate_file',
        'driving_licence_number',
        'driving_licence_expiry',
        'driving_licence_file',
        'additional_files',
        'reference_number'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'sia_expiry' => 'date',
        'dob' => 'date',
        'visa_expiry' => 'date',
        'passport_expiry' => 'date',
        'driving_licence_expiry' => 'date',
        'biometric_residence_permit_expiry' => 'date',
        'license_expiry' => 'date',
        'holiday_from' => 'date',
        'holiday_to' => 'date',
        'holiday_from_additional' => 'date',
        'holiday_to_additional' => 'date',
        'additional_files' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($employee) {
            if (!$employee->reference_number) {
                do {
                    $ref = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (Employee::where('reference_number', $ref)->exists());

                $employee->reference_number = $ref;
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function visatype(): BelongsTo
    {
        return $this->belongsTo(VisaType::class, 'visa_type');
    }
    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'employee_id');
    }
    public function terms()
    {
        return $this->hasMany(EmployeeTerm::class, 'employee_id');
    }

    public function subcontractorDetails()
    {
        return $this->belongsTo(User::class, 'subcontractor');
    }

    public function profilePictureUrl()
    {
        return $this->profile_picture ? '/uploads/profile_pics/' . $this->profile_picture : 'uploads/no.png';
    }
    public function fileUrl($file_name, $preview_only = false)
    {
        // Fixed documents
        $documents = [
            'sia_licence_file',
            'passport_file',
            'proof_of_address_file',
            'ni_letter_file',
            'first_aid_certificate_file',
            'act_certificate_file',
            'driving_licence_file'
        ];

        // 🔹 Handle standard documents
        if (in_array($file_name, $documents)) {
            $file = $this->$file_name;

            if (!$file) {
                return '/uploads/no.png';
            }

            if (str_starts_with($file, 'documents/') || str_starts_with($file, 'uploads/')) {
                if ($preview_only && str_ends_with($file, '.pdf')) {
                    return '/uploads/PDF_file_icon.svg';
                }
                return asset($file);
            }

            $path = 'uploads/' . $file_name . '/' . $file;

            if (!file_exists(public_path($path))) {
                $path = 'documents/' . $file;
            }

            if ($preview_only && str_ends_with($file, '.pdf')) {
                return '/uploads/PDF_file_icon.svg';
            }

            return asset($path);
        }

        // 🔹 Handle "Other" / additional files
        if ($this->additional_files && is_array($this->additional_files)) {
            // Search for a match by filename
            foreach ($this->additional_files as $file) {
                // Use partial match (optional) or exact match
                if ($file === $file_name || str_contains($file, $file_name)) {
                    // Determine path
                    if (str_starts_with($file, 'documents/') || str_starts_with($file, 'uploads/')) {
                        if ($preview_only && str_ends_with($file, '.pdf')) {
                            return '/uploads/PDF_file_icon.svg';
                        }
                        return asset($file);
                    }

                    $path = 'uploads/other/' . $file; // store "other" uploads in uploads/other
                    if (!file_exists(public_path($path))) {
                        $path = 'documents/' . $file;
                    }

                    if ($preview_only && str_ends_with($file, '.pdf')) {
                        return '/uploads/PDF_file_icon.svg';
                    }

                    return asset($path);
                }
            }
        }

        // 🔹 Fallback if nothing matches
        return '/uploads/no.png';
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'staff_id');
    }

    public function book()
    {
        return $this->hasMany(BookingAlarm::class, 'staff_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function checkcalls()
    {
        return $this->hasMany(CheckCall::class);
    }


    public function dobEntries()
    {
        return $this->hasMany(DobEntry::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function invoices()
    {

        return $this->hasMany(Invoice::class);
    }

    public function logs()
{
    return $this->morphMany(Log::class, 'loggable');
}
}
