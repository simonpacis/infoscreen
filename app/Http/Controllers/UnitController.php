<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Element;
use App\Models\UnitCommand;
use App\Models\UnitSettings;
use App\Jobs\ProcessElement;
use App\Jobs\ProcessUnit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class UnitController extends Controller
{


	public function commands($location_id, $unit_number)
	{
		$unit = Unit::where('location_id',$location_id)->where('unit_number',$unit_number)->first();
		if(!$unit)
		{
			return response()->json(['error' => 'Unit not found'], 404);
		}
		$commands = $unit->commands()
									 ->where('executed', 0)
									 ->get(['id', 'command', 'arguments']);
		return response()->json($commands);
	}

	public function commandExecuted($command_id)
	{
		$command = UnitCommand::where('id', $command_id)->first();
		if(!$command)
		{
			return response()->json(['error' => 'Command not found'], 404);
		}
		$command->executed = true;
		$command->save();
		return response()->json(['success' => true]);
	}

	public function getVideo($location_id, $unit_number)
	{
		$unit = Unit::where('location_id',$location_id)->where('unit_number',$unit_number)->first();
		if(!$unit)
		{
			return response()->json(['error' => 'Unit not found'], 404);
		}
		$file_path = Storage::disk('local')->path("units/{$unit->location_id}_{$unit->unit_number}.mp4");

		if (!file_exists($file_path)) {
			abort(404, 'File not found');
		}

		$file = fopen($file_path, 'rb');
		$headers = [
			'Content-Type' => 'video/mp4',
			'Content-Length' => filesize($file_path),
		];

		return response()->stream(function () use ($file) {
			fpassthru($file);
		}, 200, $headers);
	}


	public function view($location_id, $unit_number)
	{

		$unit = Unit::where('location_id',$location_id)->where('unit_number',$unit_number)->first();
		$elements = $unit->elements()->orderBy('order')->get()->map->only([
			'generated_id', 'name', 'order', 'unit_id', 'filepath', 'type', 'duration', 'created_at', 'updated_at', 'url'
		]);
		return view('unit.view')->with('unit', $unit)->with('elements', $elements);
	}


	public function settings($unit_id)
	{
		$unit = Unit::where('id', $unit_id)->first();
		if(!$unit)
		{
			return response()->json(['error' => 'Unit not found'], 404);
		}

		return view('unit.settings')->with('unit', $unit);
	}

	public function settingsPost(Request $request)
	{
		$unit = Unit::where('id', $request->input('unit_id'))->first();
		if(!$unit)
		{
			return response()->json(['error' => 'Unit not found'], 404);
		}
		$orientation = $unit->getSetting('orientation', '0');
		$unit->setSetting('orientation', $request->input('orientation'));
		$unit->name = $request->input('name');
		$unit->save();
		if($request->input('orientation') != $orientation)
		{
			$unit->processing = 1;
			$unit->finalizing = 0;
			$unit->save();
			foreach($unit->elements as $element)
			{
				$element->processed = 0;
				$element->save();
				ProcessElement::dispatch($element->id);
			}
		}
		return redirect()->route('view.unit', ['location_id' => $unit->location_id, 'unit_number' => $unit->unit_number]);


	}


	public function purge(Request $request)
	{
		$unit = Unit::where('id', $request->input('unit_id'))->first();

		if (!$unit) {
			return response()->json(['error' => 'Unit not found'], 404);
		}

		$element_list = json_decode($request->input('element_list'), true);

		// Extract the list of generated_id values from the request
		$new_element_ids = array_map(fn($el) => $el['generated_id'], $element_list);

		// Get the current elements linked to this unit
		$current_element_list = $unit->elements()->pluck('generated_id')->toArray();

		// Delete elements that are in current_element_list but NOT in new_element_ids
		Element::whereIn('generated_id', array_diff($current_element_list, $new_element_ids))->delete();

		return response()->json(['success' => true]);
	}

	public function upload(Request $request)
	{
		// Get the uploaded file
		$file = $request->file('file');
		// Get the metadata
		$metadata = json_decode($request->input('metadata'));

		if (!$metadata) {
			return response()->json(['error' => 'File or metadata missing'], 400);
		}


		$unit = Unit::where('id', $metadata->unit_id)->first();
		$unit->processing = true;
		$unit->save();

		$element = Element::where('unit_id', $unit->id)->where('generated_id', $metadata->generated_id)->first();

		if($element)
		{
			if($element->duration != $metadata->duration)
			{
				$element->delete();
				$element = null;
			} else {
				$element->order = $metadata->order;
				$element->save();
			}

		}

		if(!$element)
		{
			if($metadata->location == "memory")
			{
				$filePath = $file->storeAs('uploads', $metadata->filename, 'public');
			} else {
				$filePath = $metadata->filename;
			}
			$element = new Element;
			$element->generated_id = $metadata->generated_id;
			$element->name = $metadata->name;
			$element->duration = $metadata->duration;
			$element->order = $metadata->order;
			$element->type = $metadata->type;
			$element->filename = $metadata->filename;
			$element->filepath = $filePath;
			$element->unit_id = $metadata->unit_id;
			$element->save();
			$unit->finalizing = 0;
			$unit->save();
		}

		if($element->processed == 0)
		{
			ProcessElement::dispatch($element->id);
		}
		// Use a lock to prevent race conditions
		$lock = Cache::lock('unit_finalizing_' . $unit->id, 10); // Lock for 10 seconds

		$lock->block(5, function () use ($unit) { // Wait up to 5 seconds for the lock
			DB::transaction(function () use ($unit) {
				// Reload the unit to get the latest state
				$unit->refresh();

				// Check if all elements are processed and finalizing is not set
				if ($unit->processingCount()->elements_processed == $unit->processingCount()->total_elements && $unit->finalizing == 0) {
					$unit->finalizing = 1;
					$unit->save();
					ProcessUnit::dispatch($unit->id);
				}
			});
		});


		//		$element->processFile();
		return response()->json(['message' => 'File and metadata uploaded successfully']);
	}
}
