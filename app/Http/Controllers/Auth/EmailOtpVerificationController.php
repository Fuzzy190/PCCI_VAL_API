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
        // Require the frontend to send the email address
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        // Find the user by the provided email
        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Save or update the OTP for this email
        EmailOtp::updateOrCreate(
            ['email' => $user->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(10)
            ]
        );

        // Send Email using Gmail SMTP and the custom Blade template
        $emailData = ['otp' => $otp];
        
        Mail::send('emails.verify_email_otp', $emailData, function($message) use ($user) {
            $message->to($user->email)
                    ->subject('PCCI Valenzuela - Your Verification Code');
        });

        return response()->json([
            'message' => 'Verification code sent to your email.'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        // Require BOTH the email and the 6-digit OTP from the frontend
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => ['required', 'string', 'size:6']
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->hasVerifiedEmail()) {
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

        // Success! Mark email as verified and clean up the database
        $user->markEmailAsVerified();
        $otpRecord->delete();

        return response()->json([
            'message' => 'Email successfully verified!'
        ]);
    }
}