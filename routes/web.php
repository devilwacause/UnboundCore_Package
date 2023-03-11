<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/img/{fileUUID}', [Devilwacause\UnboundCore\Http\Controllers\ImageController::class, 'show']);