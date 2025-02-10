<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Models\Element;
use App\Models\Unit;

class ProcessElement implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $element;

	/**
	 * Create a new job instance.
	 */
	public function __construct($element_id)
	{
		$this->element = Element::where('id',$element_id)->first();;
	}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		$ffmpeg = escapeshellcmd(env('FFMPEG_BINARIES', '/usr/bin/ffmpeg')); // Get from .env
		$input = Storage::disk('public')->path($this->element->filepath);
		$output = Storage::disk('local')->path('/elements/' . pathinfo($this->element->filepath, PATHINFO_FILENAME) . '.mp4');

		if($this->element->type == "video")
		{
			$base_cmd = "$ffmpeg -y -i $input "
				. "-an " // Mute audio
				. "-threads 1 -vcodec libx264 -b:v 1000k -profile:v high -level 4.1 " // Video codec settings
				. "-pix_fmt yuv420p -r 30 " // Pixel format and frame rate
				. "-coder 1 -sc_threshold 40 -flags +loop -me_range 16 -subq 7 -i_qfactor 0.71 -qcomp 0.6 -qdiff 4 -trellis 1 "; // Advanced encoding options

		} else {
			$duration = escapeshellarg($this->element->duration);
			$base_cmd = "$ffmpeg -y -loop 1 -framerate 30 -t $duration -i $input "
				. "-an -threads 1 -vcodec libx264 -acodec aac -b:v 1000k -profile:v high -level 4.1 "
				. "-pix_fmt yuv420p -r 30 "
				. "-coder 1 -sc_threshold 40 -flags +loop -me_range 16 -subq 7 -i_qfactor 0.71 -qcomp 0.6 -qdiff 4 -trellis 1 -b:a 128k ";
		}

		if(($this->element->unit->getSetting('orientation', '0') == '0') || ($this->element->unit->getSetting('orientation', '0') == '+180'))
		{
			$cmd = $base_cmd . "-vf \"scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2:black\" "; // Scale and pad to 1920x1080
		} elseif (($this->element->unit->getSetting('orientation', '0') == '+90') || ($this->element->unit->getSetting('orientation', '0') == '-90'))

		{
			$cmd = $base_cmd . "-vf \"scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(1080-iw)/2:(1920-ih)/2:black\" ";
		}

		$cmd .= "$output 2>&1"; // Output file and log errors
		Log::info('Command being run is:\n' . $cmd);
		exec($cmd, $outputLog, $returnCode);

		if ($returnCode !== 0) {
			Log::error("FFmpeg failed: " . implode("\n", $outputLog));
		} else {
			$this->element->processed = 1;
			$this->element->save();
			$this->element->unit->processIfReady();
			Log::info("Video successfully created: $output");
		}

	}
}
