<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    //
    protected $fillable = [
        'user_id',
        'document_type',
        'file_path',
        'expiry_date',
        'description',
        'status',
        'admin_comments',
    ];

    public function user()
    {
        return $this->belongsTo(Employee::class,'user_id');
    }
}
