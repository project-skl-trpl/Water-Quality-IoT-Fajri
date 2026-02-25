<?php

use App\Http\Controllers\MonitoringController2;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
// Route::get('/monitoring/send', [MonitoringController::class, 'sendSensor']); // simulasi kirim
// Route::get('/monitoring/data', [MonitoringController::class, 'getData']);
// Route::get('/monitoring/logs', [MonitoringController::class, 'getLogs']);

Route::get('/monitoring', [MonitoringController2::class, 'index']);
Route::get('/monitoring/data', [MonitoringController2::class, 'getData']);
Route::get('/monitoring/logs', [MonitoringController2::class, 'getLogs']);

// Route::get('/kirim-sensor', function () {

//     $data = [
//         "humidity" => rand(70,90),
//         "temperature" => rand(25,35),
//         "timestamp" => time()
//     ];

//     $baseUrl = "https://monitoring-help-trpl-c-default-rtdb.asia-southeast1.firebasedatabase.app";

//     // Update current
//     Http::put("$baseUrl/sensors/device_01/current.json", $data);

//     // Simpan logs
//     Http::post("$baseUrl/sensors/device_01/logs.json", $data);

//     return "Data terkirim + tersimpan di logs";
// });
