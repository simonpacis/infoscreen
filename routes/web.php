<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;


Route::get('/run-queue', function () {
		Artisan::call('queue:work --once');
    return response()->json(['status' => 'Queue processed']);
});

Route::get('/commands/{location_id}/{unit_number}', [UnitController::class, 'commands'])->name('unit.commands');
Route::get('/command/execute/{command_id}', [UnitController::class, 'commandExecuted'])->name('command.executed');

Route::get('/video/{location_id}/{unit_number}', [UnitController::class, 'getVideo'])->name('video.get');

Route::get('/', function () {
	$locations = App\Models\Location::all();
    return view('dashboard')->with('locations', $locations);
})->middleware(['auth'])->name('dashboard');

//Route::get('/video/{location}/{unit}/status', [VideoController::class, 'status'])->name('video.status');
//Route::get('/video/{location}/{unit}/download', [VideoController::class, 'download'])->name('video.download');

Route::get('/downloadwebm', [VideoController::class, 'downloadwebm'])->name('downloadwebm');


		Route::post('/upload', [UnitController::class, 'upload']);
		Route::post('/purge', [UnitController::class, 'purge']);

Route::middleware('auth')->group(function () {
		Route::get('/location/{location_id}', [LocationController::class, 'view'])->name('view.location');
		Route::get('/location/{location_id}/{unit_number}', [UnitController::class, 'view'])->name('view.unit');
		Route::get('/unit/{unit_id}/settings', [UnitController::class, 'settings'])->name('unit.settings');
		Route::post('/unit/settings', [UnitController::class, 'settingsPost'])->name('unit.settings_post');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
