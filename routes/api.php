<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\NeedController;
use App\Http\Controllers\Api\NeedCategoryController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\PublicInvitationController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\FinancialAccountController;
use App\Http\Controllers\Api\FinancialTransactionController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\SavingsController;
use App\Http\Controllers\Api\PaymentObligationController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/health', function () {
    try {
        DB::connection()->select('SELECT 1');
    } catch (Throwable) {
        return response()->json([
            'name' => config('app.name', 'MENUJU'),
            'status' => 'unavailable',
        ], 503);
    }

    return response()->json([
        'name' => config('app.name', 'MENUJU'),
        'status' => 'ok',
        'checks' => ['database' => 'ok'],
    ]);
});

// First-party SPA requests use Laravel's web session and CSRF protection.
Route::middleware('web')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/register/verify', [AuthController::class, 'verifyRegistration'])->middleware('throttle:10,1');
        Route::post('/register/resend-code', [AuthController::class, 'resendRegistrationCode'])->middleware('throttle:3,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
        Route::middleware('auth')->group(function (): void {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    Route::get('/invitations/{token}', [WorkspaceController::class, 'invitation'])->middleware('throttle:10,1');
    Route::get('/public/invitations/{invitation:slug}', [PublicInvitationController::class, 'show'])->middleware('throttle:60,1');
    Route::get('/public/invitations/{invitation:slug}/cover', [PublicInvitationController::class, 'cover'])->middleware('throttle:60,1');
    Route::get('/public/invitations/{invitation:slug}/gallery/{galleryImage}', [PublicInvitationController::class, 'galleryImage'])->middleware('throttle:60,1');
    Route::post('/public/invitations/{invitation:slug}/rsvp', [PublicInvitationController::class, 'rsvp'])->middleware('throttle:8,1');

    Route::middleware('auth')->group(function (): void {
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
        Route::patch('/workspaces/{workspace}/settings', [SettingsController::class, 'updateWorkspace']);
        Route::patch('/profile', [SettingsController::class, 'updateProfile']);
        Route::delete('/workspaces/{workspace}/invitations/pending', [SettingsController::class, 'cancelInvitation']);
        Route::delete('/workspaces/{workspace}/members/{member}', [SettingsController::class, 'revokePartner']);
        Route::get('/workspaces/{workspace}/home', [HomeController::class, 'show']);
        Route::get('/workspaces/{workspace}/tasks', [TaskController::class, 'index']);
        Route::get('/workspaces/{workspace}/milestones', [MilestoneController::class, 'index']);
        Route::post('/workspaces/{workspace}/milestones', [MilestoneController::class, 'store']);
        Route::patch('/workspaces/{workspace}/milestones/{milestone}', [MilestoneController::class, 'update']);
        Route::delete('/workspaces/{workspace}/milestones/{milestone}', [MilestoneController::class, 'destroy']);
        Route::get('/workspaces/{workspace}/guests', [GuestController::class, 'index']);
        Route::post('/workspaces/{workspace}/guests', [GuestController::class, 'store']);
        Route::post('/workspaces/{workspace}/guests/import/preview', [GuestController::class, 'previewImport']);
        Route::post('/workspaces/{workspace}/guests/import', [GuestController::class, 'import']);
        Route::get('/guests/{guest}', [GuestController::class, 'show']);
        Route::patch('/guests/{guest}', [GuestController::class, 'update']);
        Route::delete('/guests/{guest}', [GuestController::class, 'destroy']);
        Route::get('/workspaces/{workspace}/events', [EventController::class, 'index']);
        Route::post('/workspaces/{workspace}/events', [EventController::class, 'store']);
        Route::get('/workspaces/{workspace}/events/{event}', [EventController::class, 'show']);
        Route::patch('/workspaces/{workspace}/events/{event}', [EventController::class, 'update']);
        Route::delete('/workspaces/{workspace}/events/{event}', [EventController::class, 'destroy']);
        Route::post('/events/{event}/agenda-items', [EventController::class, 'storeAgenda']);
        Route::patch('/events/{event}/agenda-items/{agendaItem}', [EventController::class, 'updateAgenda']);
        Route::delete('/events/{event}/agenda-items/{agendaItem}', [EventController::class, 'destroyAgenda']);
        Route::get('/workspaces/{workspace}/needs', [NeedController::class, 'index']);
        Route::post('/workspaces/{workspace}/needs', [NeedController::class, 'store']);
        Route::post('/workspaces/{workspace}/need-categories', [NeedCategoryController::class, 'store']);
        Route::get('/needs/{need}', [NeedController::class, 'show']);
        Route::patch('/needs/{need}', [NeedController::class, 'update']);
        Route::post('/needs/{need}/purchase', [NeedController::class, 'purchase']);
        Route::delete('/needs/{need}', [NeedController::class, 'destroy']);
        Route::get('/workspaces/{workspace}/invitation', [InvitationController::class, 'index']);
        Route::post('/workspaces/{workspace}/invitation', [InvitationController::class, 'store']);
        Route::patch('/workspaces/{workspace}/invitation/{invitation}', [InvitationController::class, 'update']);
        Route::post('/workspaces/{workspace}/invitation/{invitation}/cover', [InvitationController::class, 'uploadCover']);
        Route::delete('/workspaces/{workspace}/invitation/{invitation}/cover', [InvitationController::class, 'removeCover']);
        Route::post('/workspaces/{workspace}/invitation/{invitation}/gallery', [InvitationController::class, 'uploadGallery']);
        Route::delete('/workspaces/{workspace}/invitation/{invitation}/gallery/{galleryImage}', [InvitationController::class, 'removeGalleryImage']);
        Route::post('/workspaces/{workspace}/invitation/{invitation}/publish', [InvitationController::class, 'publish']);
        Route::post('/invitations/{invitation}/guests/{guest}/link', [InvitationController::class, 'guestLink']);
        Route::get('/workspaces/{workspace}/finance', [FinanceController::class, 'show']);
        Route::get('/workspaces/{workspace}/accounts', [FinancialAccountController::class, 'index']);
        Route::post('/workspaces/{workspace}/accounts', [FinancialAccountController::class, 'store']);
        Route::patch('/accounts/{account}', [FinancialAccountController::class, 'update']);
        Route::get('/workspaces/{workspace}/transactions', [FinancialTransactionController::class, 'index']);
        Route::post('/workspaces/{workspace}/transactions', [FinancialTransactionController::class, 'store']);
        Route::patch('/transactions/{transaction}', [FinancialTransactionController::class, 'update']);
        Route::post('/transactions/{transaction}/void', [FinancialTransactionController::class, 'void']);
        Route::get('/workspaces/{workspace}/budgets', [BudgetController::class, 'index']);
        Route::post('/workspaces/{workspace}/budgets', [BudgetController::class, 'store']);
        Route::get('/budget-categories/{category}', [BudgetController::class, 'show']);
        Route::patch('/budget-categories/{category}', [BudgetController::class, 'update']);
        Route::delete('/budget-categories/{category}', [BudgetController::class, 'destroy']);
        Route::get('/workspaces/{workspace}/savings', [SavingsController::class, 'index']);
        Route::post('/workspaces/{workspace}/savings', [SavingsController::class, 'store']);
        Route::get('/savings-goals/{goal}', [SavingsController::class, 'show']);
        Route::patch('/savings-goals/{goal}', [SavingsController::class, 'update']);
        Route::post('/savings-goals/{goal}/deposits', [SavingsController::class, 'deposit']);
        Route::patch('/savings-goals/{goal}/deposits/{deposit}', [SavingsController::class, 'updateDeposit']);
        Route::delete('/savings-goals/{goal}/deposits/{deposit}', [SavingsController::class, 'reverseDeposit']);
        Route::get('/workspaces/{workspace}/payment-obligations', [PaymentObligationController::class, 'index']);
        Route::post('/workspaces/{workspace}/payment-obligations', [PaymentObligationController::class, 'store']);
        Route::patch('/payment-obligations/{obligation}', [PaymentObligationController::class, 'update']);
        Route::post('/payment-obligations/{obligation}/payments', [PaymentObligationController::class, 'pay']);
        Route::delete('/payment-obligations/{obligation}', [PaymentObligationController::class, 'destroy']);
        Route::post('/workspaces/{workspace}/tasks', [TaskController::class, 'store']);
        Route::get('/tasks/{task}', [TaskController::class, 'show']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('/workspaces/{workspace}/complete', [WorkspaceController::class, 'complete']);
        Route::post('/workspaces/{workspace}/invites', [WorkspaceController::class, 'invite'])->middleware('throttle:5,1');
        Route::post('/invitations/{token}/accept', [WorkspaceController::class, 'acceptInvitation'])->middleware('throttle:5,1');
    });
});
