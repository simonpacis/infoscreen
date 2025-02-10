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
use App\Models\Unit;

class ProcessUnit implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $unit;
	/**
	 * Create a new job instance.
	 */
	public function __construct($unit_id)
	{
		$this->unit = Unit::where('id', $unit_id)->first();
	}

	/**
	 * Execute the job.
	 */
	public function handle(): void
	{
		$ffmpeg = escapeshellcmd(env('FFMPEG_BINARIES', '/usr/bin/ffmpeg')); // Get from .env
		$output = Storage::disk('local')->path('/units/' . $this->unit->location->id . '_' . $this->unit->unit_number . '.mp4');
		$list_file_path = '/filepaths/' . $this->unit->location->id . '_' . $this->unit->unit_number . '.txt';
		$absolute_list_file_path = Storage::disk('local')->path($list_file_path);
		$list_content = '';

		foreach ($this->unit->elementProcessedPaths() as $video_path) {
			$list_content .= "file '" . Storage::disk('local')->path($video_path) . "'\n";
		}

		Storage::disk('local')->put($list_file_path, $list_content);

		$base_cmd = "$ffmpeg -y -f concat -safe 0 -i $absolute_list_file_path "
			. "-an " // Mute audio
			. "-threads 1 -vcodec libx264 -b:v 1000k -profile:v high -level 4.1 " // Video codec settings
			. "-pix_fmt yuv420p -r 30 "; // Pixel format and frame rate

		if ($this->unit->getSetting('orientation', '0') == '0') {
			$cmd = $base_cmd . "-vf \"scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(1920-iw)/2:(1080-ih)/2:black\" "; // Scale and pad to 1920x1080
		} elseif ($this->unit->getSetting('orientation', '0') == '+90') {
			$cmd = $base_cmd . "-vf \"scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(1080-iw)/2:(1920-ih)/2:black,transpose=1\" ";
		} elseif ($this->unit->getSetting('orientation', '0') == '-90') {
			$cmd = $base_cmd . "-vf \"scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(1080-iw)/2:(1920-ih)/2:black,transpose=2\" ";
		} elseif ($this->unit->getSetting('orientation', '0') == '+180') {
			$cmd = $base_cmd . "-vf \"scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(1920-iw)/2:(1080-ih)/2:black,transpose=3\" ";
		}


		$cmd .= "$output 2>&1"; // Output file and log errors

		exec($cmd, $outputLog, $returnCode);

		// Clean up the temporary list file
		//		Storage::disk('local')->delete($list_file_path);

		if ($returnCode !== 0) {
			Log::error("FFmpeg failed: " . implode("\n", $outputLog));
		} else {
			$this->unit->processed();
			Log::info("Concatenated video successfully created: $output");
		}


	}
}
