<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

// Controllers
use App\Http\Controllers\Api\V1\ApplicantController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\BoardOfTrusteeController;
use App\Http\Controllers\Api\V1\BoardPositionController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DuesPaymentController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MemberApplicationController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MembershipDueController;
use App\Http\Controllers\Api\V1\MembershipTypeController;
use App\Http\Controllers\Api\V1\PaymentChannelController; 
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PublicProductController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\FileUploadController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\NotificationController;

// Auth Controllers
use App\Http\Controllers\Auth\OtpPasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Resources\UserResource;

// =========================================================================
// PUBLIC ROUTES
// =========================================================================

// Post ==>> Applicants
Route::post('/v1/apply', [ApplicantController::class, 'store']);

//Get ==>> Activities   
Route::get('v1/activities', [ActivityController::class, 'index']);

//Get ==>> Events
Route::get('/v1/events', [EventController::class, 'index']);
Route::get('/v1/events/{event}', [EventController::class, 'show']);

//Get ==>> Board of Trustees
Route::get('v1/trustees',[BoardOfTrusteeController::class,'index']);

//Get ==>> Members (Business)
Route::get('v1/business',[BusinessController::class,'index']);

//Post ==>> Forgot password
Route::post('/forgot-password/send-otp', [OtpPasswordResetController::class, 'sendOtp']);
Route::post('/forgot-password/reset', [OtpPasswordResetController::class, 'resetWithOtp']);

// Public Products
Route::get('v1/products/active', [PublicProductController::class, 'index']);

// PUBLIC OR GENERAL ACCESS
Route::get('/v1/payment-channels', [PaymentChannelController::class, 'index']);

// MISC ROUTES 
Route::post('v1/upload', [FileUploadController::class, 'upload']);

// CLEAR CACHE
Route::post('/system/clear-cache', [SystemController::class, 'clearCache']);

// DEV-ONLY: Refresh DB (Super Admin Only)
Route::get('/refresh-db', function () {
    try {
        Log::info('Refresh DB started');

        Schema::disableForeignKeyConstraints();

        Artisan::call('migrate:fresh', [
            '--force' => true,
            '--seed' => true
        ]);

        Schema::enableForeignKeyConstraints();

        return response()->json([
            'status' => 'success',
            'output' => Artisan::output()
        ]);

    } catch (\Throwable $e) {
        Log::error('Refresh DB failed: ' . $e->getMessage());

        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// =========================================================================
// AUTHENTICATED ROUTES
// =========================================================================
Route::middleware(['auth:sanctum'])->group(function() {
    
    // --- EXEMPT FROM PASSWORD ENFORCEMENT ---
    Route::get('/v1/user', function (Request $request) {
        return new UserResource($request->user());
    });

    // GENERAL NOTIFICATIONS (The New System)
    Route::get('/v1/notifications', [NotificationController::class, 'index']);
    Route::patch('/v1/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/v1/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/v1/notifications/clear', [NotificationController::class, 'clearAll']);

    // THIS IS THE ONLY ACTION THEY CAN PERFORM IF requires_password_change IS TRUE
    Route::post('/v1/user/first-time-password-change', [UserController::class, 'firstTimePasswordChange']);
    
    // =========================================================================
    // ENFORCE PASSWORD MIDDLEWARE
    // =========================================================================
    Route::middleware(['enforce.password.change'])->group(function() {
        
        // ---------------------------------------------------------------------
        // GENERAL ACCOUNT ACTIONS 
        // ---------------------------------------------------------------------
        Route::middleware(['throttle:api'])->group(function(){
            Route::put('/v1/user/change-info', [UserController::class, 'changeInfo']);
            Route::post('/user/confirm-password-change', [UserController::class, 'confirmPasswordChange']);
            Route::post('/user/request-password-change', [UserController::class, 'requestPasswordChange']); 
        });

        Route::post('/v1/members/trigger-expiry-check', [\App\Http\Controllers\Api\V1\MemberController::class, 'triggerExpiryCheck']);

        // ---------------------------------------------------------------------
        // SUPER ADMIN & ADMIN
        // ---------------------------------------------------------------------
        Route::middleware(['role:super_admin|admin'])->group(function () {
            
            Route::post('/v1/payment-channels', [PaymentChannelController::class, 'store']);
            Route::put('/v1/payment-channels/{paymentChannel}', [PaymentChannelController::class, 'update']);
            Route::delete('/v1/payment-channels/{paymentChannel}', [PaymentChannelController::class, 'destroy']);

            Route::post('/v1/applicants', [ApplicantController::class, 'store']);
            Route::put('/v1/applicants/{applicant}', [ApplicantController::class, 'update']);
            Route::delete('/v1/applicants/{applicant}', [ApplicantController::class, 'destroy']);
            
            Route::apiResource('v1/membership-types', MembershipTypeController::class)->except(['destroy']);

            Route::get('/v1/users', [RegisteredUserController::class, 'index']); 
            Route::get('/v1/users/{user}', [RegisteredUserController::class, 'show']); 
            Route::get('/v1/users/roles/{role}', [RegisteredUserController::class, 'getByRole']); 

            Route::post('v1/activities', [ActivityController::class, 'store']);
            Route::put('v1/activities/{activity}', [ActivityController::class, 'update']);
            Route::delete('v1/activities/{activity}', [ActivityController::class, 'destroy']);

            Route::apiResource('/v1/categories', CategoryController::class)->except(['show']);
            Route::apiResource('/v1/events', EventController::class)->except(['index', 'show']);
            
            Route::get('v1/positions',[BoardPositionController::class,'index']);
            Route::post('v1/positions',[BoardPositionController::class,'store']);
            Route::put('v1/positions/{boardPosition}',[BoardPositionController::class,'update']);

            Route::post('v1/trustees',[BoardOfTrusteeController::class,'store']);
            Route::put('v1/trustees/{boardOfTrustee}',[BoardOfTrusteeController::class,'update']);    
        });

        // ---------------------------------------------------------------------
        // SUPER ADMIN, ADMIN & TREASURER
        // ---------------------------------------------------------------------
        Route::middleware(['role:super_admin|admin|treasurer'])->group(function(){
            
            Route::apiResource('v1/payments', PaymentController::class);

            Route::apiResource('v1/membership-dues', MembershipDueController::class)->except(['store', 'destroy']);
            Route::get('v1/membership-dues/pending', [MembershipDueController::class, 'getPending']);
            Route::get('v1/membership-dues/overdue', [MembershipDueController::class, 'getOverdue']);
            Route::get('v1/membership-dues/stats', [MembershipDueController::class, 'getStats']);
            Route::get('v1/members/{member}/unpaid-dues', [MembershipDueController::class, 'getMemberUnpaidDues']);

            Route::patch('v1/payments/{applicant}/reject', [ApplicantController::class, 'reject']);

            Route::apiResource('v1/dues-payments', DuesPaymentController::class)->only(['index', 'store', 'show']);
            Route::get('v1/dues-payments/by-year', [DuesPaymentController::class, 'getCollectionByYear']);
            Route::get('v1/dues-payments/treasurer-payments', [DuesPaymentController::class, 'getTreasurerPayments']);
            Route::get('v1/dues-payments/stats', [DuesPaymentController::class, 'getStats']);
            Route::get('v1/membership-dues/{membershipDue}/payments', [DuesPaymentController::class, 'getDuePayments']);

            Route::get('v1/membership-types', [MembershipTypeController::class, 'index']); 

            Route::get('/v1/applicants', [ApplicantController::class, 'index']);
            Route::get('/v1/applicants/{applicant}', [ApplicantController::class, 'show']);

            Route::get('v1/members', [MemberController::class, 'index']);
            Route::post('v1/members', [MemberController::class, 'store']);
            Route::put('v1/members/{member}', [MemberController::class, 'update']);
        });

        // ---------------------------------------------------------------------
        // SUPER ADMIN EXCLUSIVES
        // ---------------------------------------------------------------------
        Route::middleware(['role:super_admin'])->group(function () {
            Route::delete('v1/trustees/{boardOfTrustee}',[BoardOfTrusteeController::class,'destroy']);
            Route::delete('v1/positions/{boardPosition}',[BoardPositionController::class,'destroy']);
        });

        // ---------------------------------------------------------------------
        // SUPER ADMIN, ADMIN, AND MEMBERS
        // ---------------------------------------------------------------------
        Route::middleware(['role:member|admin|super_admin'])->group(function () {
            Route::apiResource('v1/products', ProductController::class);
        });

        // ---------------------------------------------------------------------
        // MEMBER ONLY EXCLUSIVES
        // ---------------------------------------------------------------------
        Route::middleware(['role:member'])->group(function () {
            Route::get('v1/application', [MemberApplicationController::class, 'show']);
            Route::put('v1/application', [MemberApplicationController::class, 'update']);
            Route::get('v1/member/dues', [MemberController::class, 'getMyDues']);
            Route::get('v1/member/payments', [MemberController::class, 'getMyPayments']);
            Route::get('v1/member/profile', [MemberController::class, 'getMyProfile']);
            Route::get('v1/member/renewal-status', [MemberController::class, 'getRenewalStatus']);
            Route::post('v1/member/request-payment', [MemberController::class, 'requestPayment']);
        });

        // ---------------------------------------------------------------------
        // MISC AUTHENTICATED ROUTES
        // ---------------------------------------------------------------------
        Route::get('/v1/applicants/{applicant}/download/{type}', [ApplicantController::class, 'downloadDocument'])->name('applicants.download');
    });
});

require __DIR__.'/auth.php';