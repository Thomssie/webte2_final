<?php

use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\ApiDocumentationPdfController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/cas', function () {
    return Inertia::render('CasConsole');
})->name('cas.console');

Route::get('/logs', function () {
    return Inertia::render('Logs');
})->name('logs');

Route::get('/animations/ball-beam', function () {
    return Inertia::render('BallBeam');
})->name('animations.ball-beam');


Route::get('/animations/inverted-pendulum', function () {
    return Inertia::render('InvertedPendulum');
})->name('animations.inverted-pendulum');

Route::get('/statistics', function () {
    return Inertia::render('Statistics');
})->name('statistics');

Route::get('/api-docs', function () {
    return Inertia::render('ApiDocs');
})->name('api.docs');

Route::get('/openapi.json', OpenApiController::class)->name('openapi.json');

Route::get('/api-docs/pdf', ApiDocumentationPdfController::class)->name('api.docs.pdf');
