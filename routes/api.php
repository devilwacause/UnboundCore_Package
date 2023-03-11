<?php

use Illuminate\Support\Facades\Route;

Route::prefix('image')->controller(Devilwacause\UnboundCore\Http\Controllers\ImageController::class)->group( function() {
    Route::put('/update', 'update');
    Route::put('/change', 'change');
    Route::put('/move', 'move');
    Route::put('/copy', 'copy');
    Route::post('/upload', 'create');
    Route::post('/change', 'change');
    Route::post('/move', 'move');
    Route::post('/copy', 'copy');
    Route::post('/delete', 'remove');
    Route::get('/{fileUUID}', 'get');
});
