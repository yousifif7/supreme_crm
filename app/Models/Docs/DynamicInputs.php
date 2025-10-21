<?php

namespace App\Models\Docs;

use Illuminate\Database\Eloquent\Model;

class DynamicInputs extends Model
{
    
    public function parent_digi()
{
    return $this->belongsTo(DigitalForm::class,'parent_id');
}
public function child()
{
    return $this->hasMany(DynamicInputs::class,'child_id');
}
}
