<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Notifications\OtpPasswordResetNotification;
use App\Notifications\PasswordChangeConfirmationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Step 1: Request OTP for password change
     * POST /api/user/confirm-password-change
     */
    public function confirmPasswordChange(Request $request)
    {
        $user = $request->user();

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Cache the OTP for 10 minutes
        Cache::put('password_change_otp:' . $user->id, $otp, now()->addMinutes(10));

        // Send OTP via email
        $user->notify(new OtpPasswordResetNotification($otp));

        return response()->json([
            'message' => 'OTP sent to your email. Please submit this 6-digit code along with your current and new passwords to complete the request.',
            'expires_in' => 600,
        ]);
    }

    /**
     * Step 2: Submit OTP and new password to finalize the change
     * POST /api/user/request-password-change
     */
    public function requestPasswordChange(Request $request)
    {
        $user = $request->user();

        // 1. Validate that the OTP was provided FIRST
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        // 2. Verify the OTP is correct and hasn't expired BEFORE checking passwords
        $otpCacheKey = 'password_change_otp:' . $user->id;
        $cachedOtp = Cache::get($otpCacheKey);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return response()->json(['message' => 'Invalid or expired OTP.'], 422);
        }

        // 3. Now that OTP is confirmed, validate the password fields
        $request->validate([
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        // 4. Check if new password is the same as current password
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => 'New password cannot be the same as your current password.'], 422);
        }

        // 5. Update password in the database (Hash the new password!)
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Revoke all active tokens so the user is logged out everywhere
        $user->tokens()->delete();

        // Clear the cached OTP so it can't be reused
        Cache::forget($otpCacheKey);

        // Send confirmation email
        $user->notify(new PasswordChangeConfirmationNotification());

        return response()->json([
            'message' => 'Password changed successfully. You have been logged out from all devices. Please log in again with your new password.'
        ]);
    }

    /**
     * Update user information (first_name, last_name, email, and profile image)
     * PUT or POST /api/v1/user/change-info
     */
    public function changeInfo(Request $request)
    {
        $user = $request->user();

        // 1. Strict Validation: 'sometimes' + 'required' ensures that IF they send the field, it cannot be empty.
        $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name'  => ['sometimes', 'required', 'string', 'max:255'],
            'email'      => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'], 
        ]);

        // 2. Update fields using $request->has() so it explicitly checks the payload
        if ($request->has('first_name')) {
            $user->first_name = $request->first_name;
        }

        if ($request->has('last_name')) {
            $user->last_name = $request->last_name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        // 3. Handle Image Upload to Backblaze (S3)
        if ($request->hasFile('image')) {
            
            if (!empty($user->profile_photo_path)) {
                Storage::disk('s3')->delete($user->profile_photo_path);
            }

            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $filename = 'image_' . time() . '.' . $extension;

            $identifier = $user->member ? $user->member->id : $user->id;
            
            // Sanitize the full name securely
            $sanitizedName = Str::slug($user->first_name . ' ' . $user->last_name); 

            $folderPath = "member/{$identifier}.{$sanitizedName}";

            $path = $file->storeAs($folderPath, $filename, 's3');
            $user->profile_photo_path = $path; 
        }

        $user->save();

        return response()->json([
            'message' => 'User information updated successfully',
            'user' => new UserResource($user)
        ]);
    }
}