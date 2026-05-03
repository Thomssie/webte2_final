<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CasController;
use App\Http\Middleware\ValidateApiKey;




Route::middleware(ValidateApiKey::class)->group(function () {
    Route::get('/cas/ping', [CasController::class, 'ping']);
    Route::post('/cas/execute', [CasController::class, 'execute']);
    Route::get('/cas/history', [CasController::class, 'history']);
    Route::post('/cas/history/reset', [CasController::class, 'resetHistory']);
});




Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


