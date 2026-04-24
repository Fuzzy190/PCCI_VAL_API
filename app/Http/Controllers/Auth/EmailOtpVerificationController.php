<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EmailOtpVerificationController extends Controller
{
    public function sendOtp(Request $request)
    {
        // 1. Removed 'exists:users,email' so new emails can receive an OTP
        $request->validate([
            'email' => 'required|email'
        ]);

        // Find the user if they exist
        $user = User::where('email', $request->email)->first();

        // 2. Added a safety check: Only check for verification IF the user exists
        if ($user && $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save or update the OTP for this email
        EmailOtp::updateOrCreate(
            ['email' => $request->email], // 3. Changed from $user->email to $request->email
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10)
            ]
        );

        // Send Email using Gmail SMTP and the custom Blade template
        $emailData = ['otp' => $otp];
        
        Mail::send('emails.verify_email_otp', $emailData, function($message) use ($request) {
            // 4. Changed from $user->email to $request->email
            $message->to($request->email)
                    ->subject('PCCI Valenzuela - Your Verification Code');
        });

        return response()->json([
            'message' => 'Verification code sent to your email.'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        // 5. Removed 'exists' rule here as well
        $request->validate([
            'email' => 'required|email',
            'otp' => ['required', 'string', 'size:6']
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        $otpRecord = EmailOtp::where('email', $request->email)->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'No verification code requested for this email.'], 404);
        }

        if ($otpRecord->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid verification code.'], 400);
        }

        if (Carbon::now()->greaterThan($otpRecord->expires_at)) {
            $otpRecord->delete();
            return response()->json(['message' => 'Verification code has expired. Please request a new one.'], 400);
        }

        // 6. Only attempt to update the database IF the user account exists
        if ($user) {
            $user->markEmailAsVerified();
        }
        
        // Clean up the OTP
        $otpRecord->delete();

        return response()->json([
            'message' => 'Email successfully verified!'
        ]);
    }
}