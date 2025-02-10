<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ElementController extends Controller
{
	public function upload(Request $request)
	{
		$files = $request->file('files'); // Get the files
		$metadata = $request->input('metadata'); // Get the metadata

		foreach ($files as $file) {
			// Store the file with the new name
//			$filePath = $file->storeAs('uploads', $file->getClientOriginalName());
		}

		foreach ($metadata as $item) {
			$metadataArray = json_decode($item, true);
			dd($metadataArray);
			// Process the metadata, e.g., store in a database
			// Example: save to database
			// File::create([
			//     'name' => $metadataArray['name'],
			//     'filename' => $metadataArray['filename'],
			//     'order' => $metadataArray['order'],
			// ]);
		}

		return response()->json(['message' => 'Files and metadata uploaded successfully']);
	}

}
