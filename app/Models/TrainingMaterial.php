<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingMaterial extends Model
{
    

    protected $fillable = [
    'title',
    'type',
    'content_url',
    'pdf_url',
    'required',
    'expiry_date',
];


    public function acknowledgements(){
  
     return $this->hasMany(TrainingAcknowledgement::class,'training_material_id');

    }
}
