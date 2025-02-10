<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use App\Models\Element;
use App\Jobs\ProcessUnit;
use App\Models\UnitSettings;
use App\Models\UnitCommand;

class Unit extends Model
{
	public function location() {
		return $this->belongsTo(Location::class);
	}

	public function elements() {
		return $this->hasMany(Element::class);
	}

	public function processIfReady()
	{
		if($this->readyForProcessing())
		{
			$this->process();
		}
	}

	public function elementProcessedPaths()
	{
		$paths = [];
		foreach ($this->elements()->get()->sortBy('order') as $element) {
			if($element->processed == 1)
			{
				$paths[] = '/elements/' . pathinfo($element->filename, PATHINFO_FILENAME) . '.mp4';
			}
		}
		return $paths;
	}

	public function readyForProcessing() {
		if($this->processingCount()->elements_processed == $this->processingCount()->total_elements && $this->finalizing == 0)
		{
			return true;
		} else {
			return false;
		}
	}


	public function addCommand($command, $arguments = "")
	{
		$command = new UnitCommand;
		$command->unit_id = $this->id;
		$command->command = $command;
		if($command == "download" && $arguments == "")
		{
			$arguments = $this->location_id . '/' . $this->unit_number;
		}
		$command->arguments = $arguments;
		$command->save();
	}


	public function processed()
	{
		$this->processed_at = now();
		$this->processing = 0;
		$this->finalizing = 0;
		$this->addCommand('download');
		$this->save();
	}

	public function process()
	{
		$this->finalizing = 1;
		$this->save();
		ProcessUnit::dispatch($this->id);
	}


	public function processingCount() {
		$elements_processed = Element::where('unit_id', $this->id)->where('processed', 1)->count();
		$total_elements = Element::where('unit_id', $this->id)->count();
		return json_decode(json_encode([
			'elements_processed' => $elements_processed,
			'total_elements' => $total_elements
		]));
	}

	public function settings()
	{
		return $this->hasMany(UnitSettings::class);
	}


	public function setSetting($key, $value) {
		$setting = $this->settings()->where('key', $key)->first();
		if($setting)
		{
			$setting->value = $value;
			$setting->save();
		} else {
			UnitSettings::create([
				'unit_id' => $this->id,
				'key' => $key,
				'value' => $value
			]);
		}
	}


	public function getSetting($key, $default) {
		$setting = $this->settings()->where('key', $key)->first();
		if($setting)
		{
			return $setting->value;
		} else {
			return $default;
		}
	}


    public function commands()
    {
        return $this->hasMany(UnitCommand::class);
    }





}
