<?php

use App\Http\Controllers\Api\LogbookAccessController;
use Illuminate\Support\Facades\Route;

Route::post('/logbook-access/validate', [LogbookAccessController::class, 'validate']);
