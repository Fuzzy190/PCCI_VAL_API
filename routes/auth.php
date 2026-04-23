<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\EmailOtpVerificationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:super_admin'])
    ->name('register');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

// PUBLIC OTP Email Verification Routes (No token required)
Route::post('/email/send-otp', [EmailOtpVerificationController::class, 'sendOtp'])
    ->middleware('throttle:6,1')
    ->name('verification.send.otp');

Route::post('/email/verify-otp', [EmailOtpVerificationController::class, 'verifyOtp'])
    ->middleware('throttle:6,1')
    ->name('verification.verify.otp');

// PROTECTED Routes
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');