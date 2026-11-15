<?php

declare(strict_types=1);

use App\Features\Projects\Http\Controllers\CurrentProjectController;
use App\Features\Projects\Http\Controllers\ProjectController;
use App\Features\Projects\Http\Controllers\ProjectTokenController;
use Illuminate\Support\Facades\Route;

// Project management on behalf of a user.
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('projects', ProjectController::class);

    Route::apiResource('projects.tokens', ProjectTokenController::class)
        ->scoped(['project' => 'public_id', 'token' => 'id'])
        ->except('show');
});

// Requests on behalf of a project (machine-to-machine), authenticated by a project token.
Route::middleware(['project.token', 'throttle:600,1'])->group(function (): void {
    Route::get('project', CurrentProjectController::class);
});
