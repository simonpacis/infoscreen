<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Unit;

class VideoController extends Controller
{
	public function status($location, $unit) {
		$location = Location::where('id', $location)->first();
		$unit = Unit::where('unit_number', $unit)->first();

		return response()->json([
			'timestamp' => $unit->updated_at
		]);

	}

	public function downloadwebm()
	{
		return response()->file(storage_path('app/BigBuckBunny.webm'), 'video.webm', 'application/octet-stream');
	}

	public function download($location, $unit) {
		$location = Location::where('id', $location)->first();
		$unit = Unit::where('unit_number', $unit)->first();

		return response()->file(storage_path('app/' . $location->id . '/' . $unit->unit_number . '.mp4'), 'video.mp4', 'application/octet-stream');

	}
}
