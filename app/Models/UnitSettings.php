<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Unit;

class UnitSettings extends Model
{
    protected $fillable = ['unit_id', 'key', 'value'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
