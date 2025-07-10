<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('upload');
});

Route::post('/upload', [App\Http\Controllers\UploadController::class, 'upload']);

Route::get('/uploads', [App\Http\Controllers\UploadController::class, 'index']);

// Route::get('/upload-ui', function () {
//     return view('upload');
// });
