<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Log;
use App\Models\User;

use App\Traits\LogsChanges;

class Client extends Model
{
    use SoftDeletes, LogsChanges;

    protected $fillable = ['user_id', 'client_name', 'username', 'password', 'address', 'contact_number', 'contact_person', 'email', 'invoice_terms', 'payment_terms', 'doc_1', 'doc_2', 'doc_3', 'contract_start', 'contract_end', 'company_id', 'guard_rate', 'office_rate', 'vat', 'manager_id'];

    public function site(): HasMany
    {
        return $this->hasMany(Site::class);
    }
    public function shift(): HasMany
    {
        return $this->hasMany(Shift::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }
}
