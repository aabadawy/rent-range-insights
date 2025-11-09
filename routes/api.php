<?php

use App\Http\Controllers\RentInsightsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/rent-insights', RentInsightsController::class)->name('rent-insights');
