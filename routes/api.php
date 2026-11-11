<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'name' => config('app.name', 'MENUJU'),
    'status' => 'ok',
    'version' => 'foundation',
]));

// First-party SPA requests use Laravel's web session and CSRF protection.
Route::middleware('web')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
        Route::middleware('auth')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::get('/invitations/{token}', [WorkspaceController::class, 'invitation'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function (): void {
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
        Route::get('/workspaces/{workspace}/home', [HomeController::class, 'show']);
        Route::get('/workspaces/{workspace}/tasks', [TaskController::class, 'index']);
        Route::get('/workspaces/{workspace}/milestones', [MilestoneController::class, 'index']);
        Route::post('/workspaces/{workspace}/milestones', [MilestoneController::class, 'store']);
        Route::patch('/workspaces/{workspace}/milestones/{milestone}', [MilestoneController::class, 'update']);
        Route::delete('/workspaces/{workspace}/milestones/{milestone}', [MilestoneController::class, 'destroy']);
        Route::get('/workspaces/{workspace}/guests', [GuestController::class, 'index']);
        Route::post('/workspaces/{workspace}/guests', [GuestController::class, 'store']);
        Route::get('/guests/{guest}', [GuestController::class, 'show']);
        Route::patch('/guests/{guest}', [GuestController::class, 'update']);
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy']);
        Route::post('/workspaces/{workspace}/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('/workspaces/{workspace}/complete', [WorkspaceController::class, 'complete']);
        Route::post('/workspaces/{workspace}/invites', [WorkspaceController::class, 'invite'])->middleware('throttle:5,1');
        Route::post('/invitations/{token}/accept', [WorkspaceController::class, 'acceptInvitation'])->middleware('throttle:5,1');
    });
});
