<?php

use App\Http\Controllers\Api\PhotoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('photo', PhotoController::class);
Route::post('save-photo/{phone}', [PhotoController::class, 'storePhoto']);
