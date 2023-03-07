<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/test', function(Request $request) {
    dd("HERE");
});
Route::get('/img/{fileUUID}', [Devilwacause\UnboundCore\Http\Controllers\ImageController::class, 'show']);