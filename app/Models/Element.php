<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Unit;

class Element extends Model
{
	public function unit()
	{
		return $this->belongsTo(Unit::class);
	}

	public function getUrlAttribute()
	{
		return asset('storage/' . $this->filepath);
	}
}
