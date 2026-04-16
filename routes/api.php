<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\ApplicantController;
use App\Models\Applicant;
use App\Http\Controllers\Auth\OtpPasswordResetController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MembershipTypeController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\MembershipDueController;
use App\Http\Controllers\Api\V1\DuesPaymentController;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ExpiringMembershipNotificationController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\MemberApplicationController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\BoardOfTrusteeController;
use App\Http\Controllers\Api\V1\BoardPositionController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\MemberNotificationController;
use App\Http\Controllers\Api\V1\PublicProductController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Schema;

// PUBLIC 
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
// Route::get('v1/business',[MemberController::class,'index']);
Route::get('v1/business',[BusinessController::class,'index']);

//Post ==>> Forgot password
Route::post('/forgot-password/send-otp', [OtpPasswordResetController::class, 'sendOtp']);
Route::post('/forgot-password/reset', [OtpPasswordResetController::class, 'resetWithOtp']);

// Public
Route::get('v1/products/active', [PublicProductController::class, 'index']);

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




// SUPER ADMIN & ADMIN - MANAGE USERS, MEMBERSHIP TYPES, BOARD OF TRUSTEES, BOARD POSITIONS
Route::middleware(['auth:sanctum', 'role:super_admin|admin'])->group(function () {
    //Get, Post, Put, Delete ==>> Applicants
    // Route::get('/v1/applicants/', [ApplicantController::class, 'index']);
    // Route::get('/v1/applicants/{applicant}', [ApplicantController::class, 'show']);
    Route::post('/v1/applicants', [ApplicantController::class, 'store']);
    Route::put('/v1/applicants/{applicant}', [ApplicantController::class, 'update']);
    Route::delete('/v1/applicants/{applicant}', [ApplicantController::class, 'destroy']);
    
    //Get, Post, Put,  ==>> Membership Types
    Route::apiResource('v1/membership-types', MembershipTypeController::class)->except(['destroy']);

    //Get ==>> Users
    Route::get('/v1/users', [RegisteredUserController::class, 'index']); // all users
    Route::get('/v1/users/{user}', [RegisteredUserController::class, 'show']); // single user details
    Route::get('/v1/users/roles/{role}', [RegisteredUserController::class, 'getByRole']); // filter by role

    // Post, Put, Delete ==>> Activities
    Route::post('v1/activities', [ActivityController::class, 'store']);
    Route::put('v1/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('v1/activities/{activity}', [ActivityController::class, 'destroy']);

    // Post, Put, Delete ==>> Events, Categories
    Route::apiResource('/v1/categories', CategoryController::class)->except(['show']);
    Route::apiResource('/v1/events', EventController::class)->except(['index', 'show']);
    

    // Get, Post, Put ==>> Board Positions
    Route::get('v1/positions',[BoardPositionController::class,'index']);
    Route::post('v1/positions',[BoardPositionController::class,'store']);
    Route::put('v1/positions/{boardPosition}',[BoardPositionController::class,'update']);

    // Post, Put, ==>> Board of Trustees
    // get method is public
    Route::post('v1/trustees',[BoardOfTrusteeController::class,'store']);
    Route::put('v1/trustees/{boardOfTrustee}',[BoardOfTrusteeController::class,'update']);    
});


// SUPER ADMIN, ADMIN & TREASURER
Route::middleware(['auth:sanctum', 'role:super_admin|admin|treasurer'])->group(function(){
    
    // READ/WRITE PAYMENTS (Super Admin, Admin & Treasurer)
    Route::apiResource('v1/payments', PaymentController::class);

    // READ-ONLY MEMBERSHIP DUES (auto-generated from member activation)
    Route::apiResource('v1/membership-dues', MembershipDueController::class)->except(['store', 'destroy']);
    Route::get('v1/membership-dues/pending', [MembershipDueController::class, 'getPending']);
    Route::get('v1/membership-dues/overdue', [MembershipDueController::class, 'getOverdue']);
    Route::get('v1/membership-dues/stats', [MembershipDueController::class, 'getStats']);
    Route::get('v1/members/{member}/unpaid-dues', [MembershipDueController::class, 'getMemberUnpaidDues']);

    // READ/WRITE DUES PAYMENTS
    Route::apiResource('v1/dues-payments', DuesPaymentController::class)->only(['index', 'store', 'show']);
    Route::get('v1/dues-payments/by-year', [DuesPaymentController::class, 'getCollectionByYear']);
    Route::get('v1/dues-payments/treasurer-payments', [DuesPaymentController::class, 'getTreasurerPayments']);
    Route::get('v1/dues-payments/stats', [DuesPaymentController::class, 'getStats']);
    Route::get('v1/membership-dues/{membershipDue}/payments', [DuesPaymentController::class, 'getDuePayments']);

    //Get ==>> Membership Types (Super Admin & Treasurer)
    Route::get('v1/membership-types', [MembershipTypeController::class, 'index']); 
});


Route::middleware(['auth:sanctum', 'role:super_admin|admin|treasurer'])->group(function () {

    Route::get('/v1/applicants', [ApplicantController::class, 'index']);
    Route::get('/v1/applicants/{applicant}', [ApplicantController::class, 'show']);

    Route::get('v1/members', [MemberController::class, 'index']);
    Route::post('v1/members', [MemberController::class, 'store']);
    Route::put('v1/members/{member}', [MemberController::class, 'update']);

});


// SUPER ADMIN
Route::middleware(['auth:sanctum', 'role:super_admin'])->group(function () {

    //Delete ==>> Users 
    Route::delete('v1/trustees/{boardOfTrustee}',[BoardOfTrusteeController::class,'destroy']);
    Route::delete('v1/positions/{boardPosition}',[BoardPositionController::class,'destroy']);
});

//SUPER ADMIN and MEMBER
Route::middleware(['auth:sanctum', 'role:member|admin|super_admin'])->group(function () {
    // Post ==>> Products
    Route::apiResource('v1/products', ProductController::class);

   

});



Route::middleware(['auth:sanctum', 'role:member'])
    ->group(function () {
        // Get, Put ==>> Member Application
        Route::get('v1/application', [MemberApplicationController::class, 'show']);
        Route::put('v1/application', [MemberApplicationController::class, 'update']);

        // Change Password with OTP - Two step process (POST for security actions)
        Route::post('/user/confirm-password-change', [UserController::class, 'confirmPasswordChange']);
        Route::post('/user/request-password-change', [UserController::class, 'requestPasswordChange']); 

        // Get member's own dues and payments
        Route::get('v1/member/dues', [MemberController::class, 'getMyDues']);
        Route::get('v1/member/payments', [MemberController::class, 'getMyPayments']);

        // Member profile and membership status
        Route::get('v1/member/profile', [MemberController::class, 'getMyProfile']);
        Route::get('v1/member/renewal-status', [MemberController::class, 'getRenewalStatus']);

        // Member can request payment for their dues
        Route::post('v1/member/request-payment', [MemberController::class, 'requestPayment']);
    });

//READ CURRENT USER
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function(){
    Route::get('/v1/user', function (Request $request) {
        return new UserResource($request->user());
    });

    // Change user info (name and email)
    Route::put('/v1/user/change-info', [UserController::class, 'changeInfo']);
});


//GET FILES - ADMINS
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v1/applicants/{applicant}/download/{type}', 
        [ApplicantController::class, 'downloadDocument'])
        ->name('applicants.download');
});


//this can be deleted ata
use App\Http\Controllers\Api\V1\FileUploadController;
Route::post('v1/upload', [FileUploadController::class, 'upload']);
Route::get('/v1/notifications', [ExpiringMembershipNotificationController::class, 'index']);
Route::patch('/v1/notifications/{id}/read', [ExpiringMembershipNotificationController::class, 'markAsRead']);

// DUES NOTIFICATIONS - Member can view own, Admins can view all
Route::middleware(['auth:sanctum'])->group(function () {
    // Member notifications
    Route::get('v1/members/{member}/notifications', [MemberNotificationController::class, 'index']);
    Route::get('v1/members/{member}/notifications/unread', [MemberNotificationController::class, 'unread']);
    Route::get('v1/members/{member}/notifications/by-type', [MemberNotificationController::class, 'filterByType']);
    Route::get('v1/members/{member}/notifications/stats', [MemberNotificationController::class, 'stats']);
    
    // Single notification actions
    Route::put('v1/notifications/{notificationId}/mark-as-read', [MemberNotificationController::class, 'markAsRead']);
    Route::put('v1/notifications/{notificationId}/mark-as-unread', [MemberNotificationController::class, 'markAsUnread']);
    Route::put('v1/members/{member}/notifications/mark-all-read', [MemberNotificationController::class, 'markAllAsRead']);
    Route::delete('v1/notifications/{notificationId}', [MemberNotificationController::class, 'destroy']);
});

require __DIR__.'/auth.php';