<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\RoomOccupantController;
use Illuminate\Support\Facades\Route;

Route::post('/rooms/{room}/check-ins', [CheckInController::class, 'store']);
Route::post('/check-ins/{checkIn}/check-out', [CheckInController::class, 'checkOut']);
Route::get('/rooms/{room}/occupants', [RoomOccupantController::class, 'index']);