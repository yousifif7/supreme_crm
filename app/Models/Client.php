<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = ['user_id', 'client_name', 'username', 'password', 'address', 'contact_number', 'fax', 'email', 'invoice_terms', 'payment_terms', 'doc_1', 'doc_2', 'doc_3', 'contract_start', 'contract_end', 'company_id', 'guard_rate', 'office_rate', 'vat'];

    public function site(): HasMany
    {
        return $this->hasMany(Site::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
