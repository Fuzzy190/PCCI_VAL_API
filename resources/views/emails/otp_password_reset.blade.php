<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@500;600;700&display=swap');
        body { font-family: 'DM Sans', Tahoma, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background-color: #A50034; color: #FFFFFF; padding: 30px 20px; text-align: center; }
        .header h1 { font-family: 'Poppins', sans-serif; margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px 40px; text-align: center; }
        .content h2 { font-family: 'Poppins', sans-serif; color: #A50034; font-size: 20px; margin-top: 0; }
        .otp-box { background-color: #F7C6C7; border: 2px dashed #D50032; color: #A50034; padding: 20px; margin: 30px auto; border-radius: 8px; max-width: 250px; }
        .otp-code { font-family: 'Poppins', sans-serif; font-size: 32px; font-weight: 700; letter-spacing: 4px; margin: 0; }
        .warning-text { font-size: 13px; color: #D50032; margin-top: 15px; font-weight: 500; }
        .footer { background-color: #f1f5f9; padding: 20px; text-align: center; font-size: 13px; color: #64748b; border-top: 1px solid #F7C6C7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $notifiable->name ?? 'Member' }},</h2>
            <p>We received a request to reset the password for your Philippine Chamber of Commerce and Industry (PCCI) account.</p>
            <p>Please use the following One-Time Password (OTP) to complete your password reset process:</p>
            <div class="otp-box">
                <p class="otp-code">{{ $otp }}</p>
            </div>
            <p class="warning-text">This code will expire in 10 minutes.</p>
            <p style="margin-top: 30px;">If you did not request a password reset, no further action is required. Please ignore this email or contact support if you have concerns.</p>
            <p>Best regards,<br><strong style="font-family: 'Poppins', sans-serif; color: #0f172a;">PCCI Administration</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Philippine Chamber of Commerce and Industry. All rights reserved.
        </div>
    </div>
</body>
</html>