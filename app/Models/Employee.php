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
        'act_certificate_file'
    ];

    protected $casts = [
        'entry_date' => 'date',
        'sia_expiry' => 'date',
        'dob' => 'date',
        'visa_expiry' => 'date',
        'passport_expiry' => 'date',
        'biometric_residence_permit_expiry' => 'date',
        'license_expiry' => 'date',
        'holiday_from' => 'date',
        'holiday_to' => 'date',
        'holiday_from_additional' => 'date',
        'holiday_to_additional' => 'date',
    ];

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
    public function logs()
    {
        return $this->morphMany(Log::class, 'loggable');
    }

    public function profilePictureUrl()
    {
        return $this->profile_picture ? '/uploads/profile_pics/' . $this->profile_picture : 'uploads/no.png';
    }

    public function fileUrl($file_name, $preview_only = false)
    {
        $documents = ['sia_licence_file', 'passport_file', 'proof_of_address_file', 'ni_letter_file', 'first_aid_certificate_file', 'act_certificate_file'];
        if(in_array($file_name, $documents)) {
            if($this->$file_name) {
                // checkif ends with .pdf
                if ($preview_only && str_ends_with($this->$file_name, '.pdf')) {
                    return '/uploads/PDF_file_icon.svg';
                }
                return '/uploads/' . $file_name . '/' . $this->$file_name;
            }
            return '/uploads/no.png';
        }
    }
}
