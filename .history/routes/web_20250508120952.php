<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/upload', [App\Http\Controllers\UploadController::class, 'upload']);

Route::get('/uploads', [App\Http\Controllers\UploadController::class, 'index']);
