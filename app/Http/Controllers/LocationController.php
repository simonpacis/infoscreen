<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class LocationController extends Controller
{
	public function view($location_id)
	{
		$location = Location::where('id',$location_id)->first();
		return view('location.view')->with('location', $location);
	}
}
