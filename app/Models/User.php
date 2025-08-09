<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Profile;
use App\Traits\LogsChanges;
use App\Models\Conversation;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsChanges, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'profile_picture',
        'status',
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     public function site()
    {
        return $this->hasMany(Site::class,'client_id');
    }

    public function deviceLogs()
    {
        return $this->hasMany(DeviceLog::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }


    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user', 'user_id', 'conversation_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function readMessages()
    {
        return $this->belongsToMany(Message::class, 'message_reads', 'user_id', 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function emergencyAlerts()
    {
        return $this->hasMany(EmergencyAlert::class);
    }


    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function alarms()
    {
        return $this->hasMany(Alarm::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    
    public function documents()
    {
        return $this->hasMany(Document::class);
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
