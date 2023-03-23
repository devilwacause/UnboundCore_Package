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

Route::prefix('file-manager')->controller(Devilwacause\UnboundCore\Http\Controllers\FileManagerController::class)->group( function() {
    Route::get('/', function() { echo "File-Manager";});
    Route::get('showdir/{folder_id?}', 'showdir');
});
