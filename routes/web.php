<?php

use App\Http\Controllers\EnergyEstimationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('energy-estimation.index');
});

Route::resource('energy-estimations', EnergyEstimationController::class)
    ->names('energy-estimation');

Route::get('energy-estimations/export-csv', [EnergyEstimationController::class, 'exportCsv'])
    ->name('energy-estimation.export-csv');

Route::get('energy-estimations/{energyEstimation}/export-csv', [EnergyEstimationController::class, 'exportSingleCsv'])
    ->name('energy-estimation.export-single-csv');