<?php

use App\Http\Controllers\Api\AccommodationSearchController;
use Illuminate\Support\Facades\Route;

Route::post('/ai-search', [AccommodationSearchController::class, 'search'])
    ->middleware('throttle:30,1');
