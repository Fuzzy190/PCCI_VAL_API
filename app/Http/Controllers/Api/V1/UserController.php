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
     * POST /api/v1/user/first-time-password-change
     * Specialized endpoint ONLY for the forced first-time change.
     */
    public function firstTimePasswordChange(Request $request)
    {
        $user = $request->user();

        if (!$user->requires_password_change) {
            return response()->json(['message' => 'Your password has already been updated.'], 400);
        }

        $request->validate([
            'new_password' => ['required', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()],
        ]);

        if (\Illuminate\Support\Facades\Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => 'New password cannot be the same as your current generated password.'], 422);
        }

        // Update password and lift the restriction
        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->requires_password_change = false;
        $user->save();

        return response()->json([
            'message' => 'Password successfully changed. You can now access the dashboard!'
        ]);
    }

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
        try {
            $user = $request->user();

            // ... (Your validation code)

            if ($request->hasFile('image')) {
                // Log the attempt
                \Log::info('Attempting image upload for user: ' . $user->id);

                $path = $request->file('image')->store('avatars', 's3');
                $user->profile_photo_path = $path;
            }

            $user->save();

            return response()->json(['message' => 'Success', 'user' => new UserResource($user)]);
        } catch (\Exception $e) {
            // This stops the 500 error crash and gives you the specific message
            \Log::error('Profile Update Failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            // Delete the file from Backblaze
            \Illuminate\Support\Facades\Storage::disk('s3')->delete($user->profile_photo_path);

            // Remove path from DB
            $user->profile_photo_path = null;
            $user->save();
        }

        return response()->json(['message' => 'Avatar removed successfully']);
    }

    /**
     * Update an Admin User (Super Admin only)
     */
    public function update(Request $request, $id)
    {
        $userToEdit = \App\Models\User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            // Ensure email is unique, but skip checking the current user's own email!
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $userToEdit->id],
            'role' => ['required', 'string']
        ]);

        // Update basic info
        $userToEdit->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ]);

        // Sync Spatie Permissions (This removes old roles and applies the new one)
        if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
            $userToEdit->syncRoles([$validated['role']]);
        }

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $userToEdit
        ]);
    }

    /**
     * Delete an Admin User (Super Admin only)
     */
    public function destroy(Request $request, $id) // <-- ADDED 'Request $request' HERE
    {
        $userToDelete = \App\Models\User::findOrFail($id);

        // Security check: Prevent the Super Admin from accidentally deleting themselves!
        if ($request->user()->id === $userToDelete->id) {
            return response()->json(['message' => 'You cannot delete your own active account.'], 403);
        }

        $userToDelete->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
