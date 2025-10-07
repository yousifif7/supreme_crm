<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DobMedia extends Model
{

    protected $table = 'dob_media';
    
    protected $fillable= [
        'dob_entry_id',
        'file_url',

    ] ;

    public function dobEntry()
    {
        return $this->belongsTo(DobEntry::class);
    }
}
