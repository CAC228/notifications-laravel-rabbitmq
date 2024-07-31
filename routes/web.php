<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/notifications', [NotificationController::class, 'create'])    
->withoutMiddleware([VerifyCsrfToken::class]);
;

Route::get('/notifications', [NotificationController::class, 'index']);