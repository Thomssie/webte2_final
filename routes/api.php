<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CasController;
use App\Http\Middleware\ValidateApiKey;
use App\Http\Controllers\Api\BallBeamController;
use App\Http\Controllers\Api\InvertedPendulumController;
use App\Http\Controllers\Api\StatisticsController;






Route::middleware(ValidateApiKey::class)->group(function () {
    Route::post('/cas/execute', [CasController::class, 'execute']);
    Route::get('/cas/history', [CasController::class, 'history']);
    Route::post('/cas/history/reset', [CasController::class, 'resetHistory']);
    Route::get('/cas/logs', [CasController::class, 'logs']);
    Route::get('/cas/logs/export', [CasController::class, 'exportLogs']);
    Route::post('/simulations/ball-beam', [BallBeamController::class, 'simulate']);
    Route::post('/simulations/inverted-pendulum', [InvertedPendulumController::class, 'simulate']);
    Route::get('/statistics/animations', [StatisticsController::class, 'summary']);
    Route::get('/statistics/animations/{animationType}', [StatisticsController::class, 'details']);


});
