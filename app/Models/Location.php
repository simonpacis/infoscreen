<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Unit;

class Location extends Model
{
	public function units() {
		return $this->hasMany(Unit::class);
	}
}
