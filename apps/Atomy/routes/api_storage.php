<?php

declare(strict_types=1);

use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storage API Routes
|--------------------------------------------------------------------------
|
| These routes provide RESTful API endpoints for file storage operations
| using the Nexus\Storage package.
|
*/

Route::prefix('api/storage')->middleware(['api'])->group(function () {
    // File upload
    Route::post('/files', [StorageController::class, 'upload'])->name('storage.upload');

    // File download
    Route::get('/files/{path}', [StorageController::class, 'download'])
        ->where('path', '.*')
        ->name('storage.download');

    // File metadata
    Route::get('/files/{path}/metadata', [StorageController::class, 'metadata'])
        ->where('path', '.*')
        ->name('storage.metadata');

    // File existence check
    Route::head('/files/{path}', [StorageController::class, 'exists'])
        ->where('path', '.*')
        ->name('storage.exists');

    // File deletion
    Route::delete('/files/{path}', [StorageController::class, 'delete'])
        ->where('path', '.*')
        ->name('storage.delete');

    // Generate temporary URL
    Route::post('/files/{path}/temporary-url', [StorageController::class, 'temporaryUrl'])
        ->where('path', '.*')
        ->name('storage.temporary-url');

    // List files in directory
    Route::get('/directories/{path}', [StorageController::class, 'listFiles'])
        ->where('path', '.*')
        ->name('storage.list');

    // Create directory
    Route::post('/directories', [StorageController::class, 'createDirectory'])
        ->name('storage.directory.create');

    // Copy file
    Route::post('/files/{path}/copy', [StorageController::class, 'copy'])
        ->where('path', '.*')
        ->name('storage.copy');

    // Move file
    Route::post('/files/{path}/move', [StorageController::class, 'move'])
        ->where('path', '.*')
        ->name('storage.move');

    // Set file visibility
    Route::patch('/files/{path}/visibility', [StorageController::class, 'setVisibility'])
        ->where('path', '.*')
        ->name('storage.visibility');
});
