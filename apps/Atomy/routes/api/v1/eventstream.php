<?php

declare(strict_types=1);

use App\Http\Controllers\EventStreamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| EventStream API Routes
|--------------------------------------------------------------------------
|
| Event sourcing endpoints for appending and reading event streams.
| All routes require authentication and feature flag validation.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Append event to stream
    Route::post('/event-streams', [EventStreamController::class, 'append'])
        ->middleware('feature:features.eventstream.event.create')
        ->name('eventstream.append');
    
    // Read event stream for aggregate
    Route::get('/event-streams/{aggregateId}', [EventStreamController::class, 'read'])
        ->middleware('feature:features.eventstream.event.read')
        ->name('eventstream.read');
    
    // Read events by type
    Route::get('/event-streams/type/{eventType}', [EventStreamController::class, 'readByType'])
        ->middleware('feature:features.eventstream.event.read')
        ->name('eventstream.read-by-type');
});
